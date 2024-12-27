<?php

use PHPUnit\Framework\TestCase;
use Colibri\Utils\Logs\FileLogger;
use Colibri\IO\FileSystem\File;

class FileLoggerTest extends TestCase
{
    private string $logFile = '/tmp/test.log';

    protected function setUp(): void
    {
        if (File::Exists($this->logFile)) {
            File::Delete($this->logFile);
        }
    }

    public function testWriteLine()
    {
        $logger = new FileLogger(7, $this->logFile);
        $logger->WriteLine(1, 'Test message');
        $content = File::Read($this->logFile);
        $this->assertStringContainsString('Test message', $content);
    }

    public function testContent()
    {
        $logger = new FileLogger(7, $this->logFile);
        $logger->WriteLine(1, 'Test message');
        $content = $logger->Content();
        $this->assertStringContainsString('Test message', $content);
    }
}
