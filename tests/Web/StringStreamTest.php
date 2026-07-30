<?php

declare(strict_types=1);

namespace Colibri\Tests\Web;

use Colibri\Web\StringStream;
use Psr\Http\Message\StreamInterface;
use PHPUnit\Framework\TestCase;

final class StringStreamTest extends TestCase
{
    public function testImplementsStreamInterfaceAndReadsContent(): void
    {
        $stream = new StringStream('colibri');

        self::assertInstanceOf(StreamInterface::class, $stream);
        self::assertSame(7, $stream->getSize());
        self::assertSame('col', $stream->read(3));
        self::assertSame(3, $stream->tell());
        self::assertSame('ibri', $stream->getContents());
        self::assertFalse($stream->eof());
    }

    public function testSeekingAndAppendingChangeAvailableContent(): void
    {
        $stream = new StringStream('core');
        $stream->seek(-1, SEEK_END);
        self::assertSame('e', $stream->read(1));
        self::assertTrue($stream->eof());

        $stream->Append(' tests');
        $stream->rewind();
        self::assertSame('core tests', (string) $stream);
    }
}
