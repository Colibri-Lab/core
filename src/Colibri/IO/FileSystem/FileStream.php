<?php

/**
 * FileSystem
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\IO\FileSystem
 */

namespace Colibri\IO\FileSystem;

/**
 * Class for working with file streams.
 * 
 * @class
 * @extends Stream
 * @example
 * ```
 * $fileStream = new FileStream('/path/to/file.txt');
 * $fileStream->Write('Hello, World!');
 * $fileStream->Seek(0);
 * echo $fileStream->Read(0, 13); // Outputs: Hello, World
 * $fileStream->Close();
 * ```
 */
class FileStream extends Stream
{
    /**
     * Indicates if the file is virtual.
     *
     * @var bool
     * @protected
     */
    protected bool $_virtual;

    /**
     * Constructor.
     *
     * @param string $source The file source.
     * @param bool $virtual Indicates if the file is virtual.
     * @constructor
     * @public
     * @example
     * ```
     * $fileStream = new FileStream('/path/to/file.txt');
     * ```
     */
    public function __construct(string $source, bool $virtual = false)
    {
        $this->_virtual = $virtual;
        $this->_stream = fopen($source, "rw+");
        if (!$this->_virtual) {
            $this->_length = filesize($source);
        } else {
            $this->_length = -1;
        }
    }

    /**
     * Moves the position within the stream.
     *
     * @param int $offset The offset to move the position.
     * @return void
     * @public
     * @example
     * ```
     * $fileStream = new FileStream('/path/to/file.txt');
     * $fileStream->Seek(10); // Moves the position to offset 10
     * ```
     */
    public function Seek(int $offset = 0): void
    {
        if ($offset == 0) {
            return;
        }

        fseek($this->_stream, $offset);
    }

    /**
     * Reads from the stream.
     *
     * @param int|null $offset The offset from where to start reading.
     * @param int|null $count The number of bytes to read.
     * @return string|bool The content read from the stream.
     * @public
     * @example
     * ```
     * $fileStream = new FileStream('/path/to/file.txt');
     * $content = $fileStream->Read(0, 100); // Reads 100 bytes from the stream starting at offset 0
     * ```
     */
    public function Read(?int $offset = 0, ?int $count = 0): bool|string
    {
        $this->Seek($offset);
        return fread($this->_stream, $count);
    }

    /**
     * Writes to the stream.
     *
     * @param string $buffer The content to write.
     * @param int|null $offset The position from where to write.
     * @return bool|int The number of bytes written or false on failure.
     * @public
     * @example
     * ```
     * $fileStream = new FileStream('/path/to/file.txt');
     * $bytesWritten = $fileStream->Write('Hello, World!', 0); // Writes 'Hello, World!' to the stream starting at offset 0
     * ```
     */
    public function Write(string $buffer, ?int $offset = 0): bool|int
    {
        $this->Seek($offset);
        return fwrite($this->_stream, $buffer);
    }

    /**
     * Flushes the stream.
     *
     * @return void
     * @public
     * @example
     * ```
     * $fileStream = new FileStream('/path/to/file.txt');
     * $fileStream->Write('Hello, World!');
     * $fileStream->Flush(); // Flushes the stream to ensure all data is written
     * ```
     */
    public function Flush(): void
    {
        fflush($this->_stream);
    }

    /**
     * Closes the stream.
     *
     * @return void
     * @public
     * @example
     * ```
     * $fileStream = new FileStream('/path/to/file.txt');
     * $fileStream->Close(); // Closes the stream
     * ```
     */
    public function Close(): void
    {
        $this->Flush();
        fclose($this->_stream);
        $this->_stream = false;
    }

    /**
     * Getter.
     *
     * @param string $property The property name.
     * @return mixed|null The value of the property.
     * @magic
     * @public
     * @example
     * ```
     * $fileStream = new FileStream('/path/to/file.txt');
     * echo $fileStream->stream; // Outputs the stream resource
     * echo $fileStream->length; // Outputs the length of the file
     * ```
     */
    public function __get(string $property): mixed
    {
        if ($property == 'stream') {
            return $this->_stream;
        } else if($property === 'length') {
            return $this->_length;
        }
        return null;
    }

    /**
     * Reads a line from the stream.
     *
     * @return bool|string The line read from the stream.
     * @public
     * @example
     * ```
     * $fileStream = new FileStream('/path/to/file.txt');
     * $line = $fileStream->ReadLine(); // Reads a line from the stream
     * ```
     */
    public function ReadLine(): bool|string
    {
        return \fgets($this->_stream);
    }

    /**
     * Writes a line to the stream.
     *
     * @param mixed $string The string to write.
     * @return bool|int The number of bytes written or false on failure.
     * @public
     * @example
     * ```
     * $fileStream = new FileStream('/path/to/file.txt');
     * $fileStream->WriteLine('Hello, World!'); // Writes 'Hello, World!' to the stream followed by a newline
     * ```
     */
    public function WriteLine($string): bool|int
    {
        return \fputs($this->_stream, $string);
    }

}
