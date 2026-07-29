<?php

use PHPUnit\Framework\TestCase;
use Colibri\Web\StringStream;

class StringStreamTest extends TestCase
{
    private StringStream $stream;

    protected function setUp(): void
    {
        $this->stream = new StringStream('hello world');
    }

    public function testToString(): void
    {
        $this->assertEquals('hello world', (string)$this->stream);
    }

    public function testAppend(): void
    {
        $this->stream->Append('!');
        $this->assertEquals('hello world!', (string)$this->stream);
    }

    public function testGetSize(): void
    {
        $this->assertEquals(11, $this->stream->getSize());
    }

    public function testGetContents(): void
    {
        $this->assertEquals('hello world', $this->stream->getContents());
    }

    public function testIsReadable(): void
    {
        $this->assertTrue($this->stream->isReadable());
    }

    public function testIsWritable(): void
    {
        $this->assertTrue($this->stream->isWritable());
    }

    public function testIsSeekable(): void
    {
        $this->assertTrue($this->stream->isSeekable());
    }

    public function testTell(): void
    {
        $this->assertEquals(0, $this->stream->tell());
    }

    public function testEofFalseAtStart(): void
    {
        $this->assertFalse($this->stream->eof());
    }

    public function testEofTrueAtEnd(): void
    {
        $this->stream->read(100);
        $this->assertTrue($this->stream->eof());
    }

    public function testRead(): void
    {
        $data = $this->stream->read(5);
        $this->assertEquals('hello', $data);
    }

    public function testReadAdvancesPointer(): void
    {
        $this->stream->read(6);
        $this->assertEquals(6, $this->stream->tell());
    }

    public function testRewind(): void
    {
        $this->stream->read(5);
        $this->stream->rewind();
        $this->assertEquals(0, $this->stream->tell());
    }

    public function testSeek(): void
    {
        $this->stream->seek(5);
        $this->assertEquals(5, $this->stream->tell());
    }

    public function testWrite(): void
    {
        $bytes = $this->stream->write(' there');
        $this->assertEquals(6, $bytes);
    }

    public function testDetachReturnsNull(): void
    {
        $this->assertNull($this->stream->detach());
    }

    public function testGetMetadataReturnsNull(): void
    {
        $this->assertNull($this->stream->getMetadata('key'));
    }

    public function testClose(): void
    {
        $this->stream->close(); // Should not throw
        $this->assertTrue(true);
    }

    public function testEmptyStream(): void
    {
        $stream = new StringStream('');
        $this->assertEquals(0, $stream->getSize());
        $this->assertTrue($stream->eof());
    }
}
