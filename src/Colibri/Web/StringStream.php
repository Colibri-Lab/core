<?php

namespace Colibri\Web;

use Psr\Http\Message\StreamInterface;

class StringStream implements StreamInterface
{
    private string $content;
    private int $pointer = 0;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function Append(string $content) {
        $this->content .= $content;
    }

    public function __toString()
    {
        return $this->content;
    }

    public function close() {}
    public function detach() { return null; }

    public function getSize()
    {
        return strlen($this->content);
    }

    public function tell()
    {
        return $this->pointer;
    }

    public function eof()
    {
        return $this->pointer >= strlen($this->content);
    }

    public function isSeekable()
    {
        return true;
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        if ($whence === SEEK_SET) {
            $this->pointer = $offset;
        } elseif ($whence === SEEK_CUR) {
            $this->pointer += $offset;
        } elseif ($whence === SEEK_END) {
            $this->pointer = strlen($this->content) + $offset;
        }
    }

    public function rewind()
    {
        $this->pointer = 0;
    }

    public function isWritable()
    {
        return false;
    }

    public function write($string)
    {
        return 0;
    }

    public function isReadable()
    {
        return true;
    }

    public function read($length)
    {
        $result = substr($this->content, $this->pointer, $length);
        $this->pointer += strlen($result);
        return $result;
    }

    public function getContents()
    {
        return substr($this->content, $this->pointer);
    }

    public function getMetadata($key = null)
    {
        return null;
    }
}