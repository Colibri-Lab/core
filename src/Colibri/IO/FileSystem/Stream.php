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
 * Abstract streaming class.
 */
abstract class Stream
{

    /**
     * The length of the stream.
     *
     * @var int
     */
    protected int $_length = 0;

    /**
     * The stream descriptor.
     *
     * @var mixed
     */
    protected mixed $_stream;

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        if ($this->_stream) {
            $this->close();
        }
        unset($this->_stream);
    }

    /**
     * Getter.
     *
     * @param string $property The property.
     * @return mixed
     */
    public function __get(string $property): mixed
    {
        if ($property == 'length') {
            return $this->_length;
        }

        return null;
    }

    /**
     * Move the position.
     *
     * @param int $offset The position to move to.
     * @return void
     */
    abstract public function Seek(int $offset = 0): void;

    /**
     * Read from the stream.
     *
     * @param int|null $offset Where to start reading from.
     * @param int|null $count The number of bytes to read.
     * @return bool|string
     */
    abstract public function Read(?int $offset = null, ?int $count = null): bool|string;

    /**
     * Write to the stream.
     *
     * @param string $content The content to write.
     * @param int|null $offset Where to write from.
     * @return int|bool
     */
    abstract public function Write(string $content, ?int $offset = null): int|bool;

    /**
     * Read a line from the stream.
     *
     * @return bool|string
     */
    abstract public function ReadLine(): bool|string;

    /**
     * Write a line to the stream.
     *
     * @param string $string The content to write.
     * @return bool|int
     */
    abstract public function WriteLine(string $string): bool|int;

    /**
     * Save changes.
     *
     * @return void
     */
    abstract public function flush(): void;

    /**
     * Close the stream.
     *
     * @return void
     */
    abstract public function close(): void;
    
}