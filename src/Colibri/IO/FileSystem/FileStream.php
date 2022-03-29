<?php

/**
 * FileSystem
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\IO\FileSystem
 */

namespace Colibri\IO\FileSystem;

/**
 * Работа с стримом файла
 * @testFunction testFileStream
 */
class FileStream extends Stream
{

    /**
     * Виртуальный файл
     *
     * @var boolean
     */
    protected bool $_virtual;

    /**
     * Конструктор
     *
     * @param string $source
     * @param boolean $virtual
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
     * Передвинуть позицию
     *
     * @param integer $offset куда передвинуть позицию
     * @return void
     * @testFunction testFileStreamSeek
     */
    public function Seek(int $offset = 0): void
    {
        if ($offset == 0) {
            return;
        }

        fseek($this->_stream, $offset);
    }

    /**
     * Считать из стрима
     *
     * @param int $offset откуда начать считывание
     * @param int $count количество байл которые нужно считать
     * @return string|bool
     * @testFunction testFileStreamRead
     */
    public function Read(int $offset = 0, int $count = 0): bool|string
    {
        $this->Seek($offset);
        return fread($this->_stream, $count);
    }

    /**
     * Записать в стрим
     *
     * @param string $buffer контент, которые нужно записать
     * @param int $offset место откуда записать
     * @return bool|int
     * @testFunction testFileStreamWrite
     */
    public function Write(string $buffer, int $offset = 0): bool|int
    {
        $this->seek($offset);
        return fwrite($this->_stream, $buffer);
    }

    /**
     * Сохранить изменения
     *
     * @return void
     * @testFunction testFileStreamFlush
     */
    public function Flush(): void
    {
        fflush($this->_stream);
    }

    /**
     * Закрыть стрим
     *
     * @return void
     * @testFunction testFileStreamClose
     */
    public function Close(): void
    {
        $this->flush();
        fclose($this->_stream);
        $this->_stream = false;
    }

    /**
     * Геттер
     *
     * @param string $property свойство
     * @return mixed
     * @testFunction testFileStream__get
     */
    public function __get(string $property): mixed
    {
        if ($property == 'stream') {
            return $this->_stream;
        }
        return null;
    }

    public function readLine(): bool|string
    {
        return \fgets($this->_stream);
    }

    public function writeLine($string): bool|int
    {
        return \fputs($this->_stream, $string);
    }

}
