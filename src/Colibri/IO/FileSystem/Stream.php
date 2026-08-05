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
 * @class
 * @abstract
 */
abstract class Stream
{
    /**
     * The length of the stream.
     *
     * @var int
     * @protected
     */
    protected int $_length = 0;

    /**
     * The stream descriptor.
     *
     * @var mixed
     * @protected
     */
    protected mixed $_stream;

    /**
     * Constructor.
     * @constructor
     * @public
     */
    public function __construct()
    {
    }

    /**
     * Destructor.
     * @destructor
     * @public
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
     * @magic
     * @public
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
     * @public
     * @abstract
     */
    abstract public function Seek(int $offset = 0): void;

    /**
     * Read from the stream.
     *
     * @param int|null $offset Where to start reading from.
     * @param int|null $count The number of bytes to read.
     * @return bool|string
     * @public
     * @abstract
     */
    abstract public function Read(?int $offset = null, ?int $count = null): bool|string;

    /**
     * Write to the stream.
     *
     * @param string $content The content to write.
     * @param int|null $offset Where to write from.
     * @return int|bool
     * @abstract
     * @public
     */
    abstract public function Write(string $content, ?int $offset = null): int|bool;

    /**
     * Read a line from the stream.
     *
     * @return bool|string
     * @abstract
     * @public
     */
    abstract public function ReadLine(): bool|string;

    /**
     * Write a line to the stream.
     *
     * @param string $string The content to write.
     * @return bool|int
     * @abstract
     * @public
     */
    abstract public function WriteLine(string $string): bool|int;

    /**
     * Save changes.
     *
     * @return void
     * @abstract
     * @public
     */
    abstract public function flush(): void;

    /**
     * Close the stream.
     *
     * @return void
     * @abstract
     * @public
     */
    abstract public function close(): void;

}
