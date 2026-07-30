<?php

declare(strict_types=1);

namespace Colibri\Tests;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class SourceFilesTest extends TestCase
{
    public function testEverySourceFileHasValidPhpSyntax(): void
    {
        $sourcePath = dirname(__DIR__) . '/src';
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourcePath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            self::assertInstanceOf(SplFileInfo::class, $file);
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $command = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file->getPathname());
            exec($command, $output, $exitCode);
            self::assertSame(0, $exitCode, implode(PHP_EOL, $output));
        }
    }

    public function testEveryDeclaredSourceTypeCanBeAutoloaded(): void
    {
        foreach ($this->sourceFiles() as $file) {
            foreach ($this->declaredTypes($file->getPathname()) as $type) {
                self::assertTrue(
                    class_exists($type) || interface_exists($type) || trait_exists($type),
                    sprintf('Unable to autoload %s from %s', $type, $file->getPathname())
                );
            }
        }
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function sourceFiles(): iterable
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(dirname(__DIR__) . '/src', RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                yield $file;
            }
        }
    }

    /**
     * @return list<string>
     */
    private function declaredTypes(string $file): array
    {
        $namespace = '';
        $types = [];
        $tokens = token_get_all((string) file_get_contents($file));
        $count = count($tokens);

        for ($index = 0; $index < $count; $index++) {
            $token = $tokens[$index];
            if (!is_array($token)) {
                continue;
            }

            if ($token[0] === T_NAMESPACE) {
                $namespace = $this->readName($tokens, $index + 1);
            }

            if (in_array($token[0], [T_CLASS, T_INTERFACE, T_TRAIT], true)) {
                $name = $this->readName($tokens, $index + 1);
                if ($name !== '') {
                    $types[] = $namespace . '\\' . $name;
                }
            }
        }

        return $types;
    }

    /**
     * @param list<array|string> $tokens
     */
    private function readName(array $tokens, int $index): string
    {
        $name = '';
        $count = count($tokens);

        for (; $index < $count; $index++) {
            $token = $tokens[$index];
            if (is_string($token)) {
                return $token === ';' || $token === '{' ? $name : '';
            }

            if (in_array($token[0], [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR], true)) {
                $name .= $token[1];
            } elseif (!in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                return $name;
            }
        }

        return $name;
    }
}
