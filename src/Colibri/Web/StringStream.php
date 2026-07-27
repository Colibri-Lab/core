<?php

/**
 * Web
 *
 * This abstract class represents a template for web content generation.
 *
 * @package Colibri\Web
 * @author Vahan P. Grigoryan
 * @copyright 2020 ColibriLab
 */

namespace Colibri\Web;

use Psr\Http\Message\StreamInterface;

/**
 * String stream class  
 * @author Vahan P. Grigoryan
 * @package Colibri\Web
 * @property-readonly string $sid
 * @property int $ttl
 */
class StringStream implements StreamInterface
{
    /**
     * @var string The content of the stream.
     */
    private string $content;

    /**
     * @var int The current position of the pointer in the stream.
     */
    private int $pointer = 0;

    /** 
     * Constructs a new StringStream object with the given content.
     */
    public function __construct(string $content)
    {
        $this->content = $content;
    }

    /**
     * Appends content to the stream.
     */
    public function Append(string $content) {
        $this->content .= $content;
    }

    /**
     * Returns the string content of the stream.
     */
    public function __toString()
    {
        return $this->content;
    }

    /**
     * Closes the stream.
     */
    public function close() {}

    /**
     * Detaches the stream from the underlying resource.
     *
     * @return null
     */
    public function detach() { return null; }

    /**
     * Gets the size of the stream.
     *
     * @return int The size of the stream.
     */
    public function getSize()
    {
        return strlen($this->content);
    }

    /**
     * Gets the current position of the pointer in the stream.
     *
     * @return int The current position of the pointer.
     */
    public function tell()
    {
        return $this->pointer;
    }

    /**
     * Checks if the pointer is at the end of the stream.
     *
     * @return bool True if the pointer is at the end, false otherwise.
     */
    public function eof()
    {
        return $this->pointer >= strlen($this->content);
    }

    /**
     * Checks if the stream is seekable.
     *
     * @return bool True if the stream is seekable, false otherwise.
     */
    public function isSeekable()
    {
        return true;
    }

    /**
     * Seeks to a specific position in the stream.
     *
     * @param int $offset The offset to seek to.
     * @param int $whence The reference point for the offset.
     */
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

    /**
     * Rewinds the stream to the beginning.
     * @return void
     */
    public function rewind()
    {
        $this->pointer = 0;
    }

    /**
     * Checks if the stream is writable.
     * @return bool True if the stream is writable, false otherwise.
     */
    public function isWritable()
    {
        return false;
    }

    /**
     * Writes data to the stream.
     * @param string $string The data to write.
     */
    public function write($string)
    {
        return 0;
    }
    /**
     * Checks if the stream is readable.
     * @return bool True if the stream is readable, false otherwise.
     */
    public function isReadable()
    {
        return true;
    }

    /**
     * Reads data from the stream.
     * @param int $length The number of bytes to read.
     * @return string The data read from the stream.
     */
    public function read($length)
    {
        $result = substr($this->content, $this->pointer, $length);
        $this->pointer += strlen($result);
        return $result;
    }

    /**
     * Returns the remaining contents of the stream.
     * @return string The remaining contents of the stream.
     */
    public function getContents()
    {
        return substr($this->content, $this->pointer);
    }

    /**
     * Returns the metadata of the stream.
     * @param string|null $key The metadata key to retrieve. If null, returns all metadata.
     * @return mixed The metadata value for the specified key, or an array of all metadata if key is null.
     */
    public function getMetadata($key = null)
    {
        return null;
    }
}