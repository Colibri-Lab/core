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
 * Абстрактный класс Стриминга
 */
abstract class Stream
{

    /**
     * Длина стрима
     *
     * @var integer
     */
    protected int $_length = 0;
    /**
     * Декриптор
     *
     * @var mixed
     */
    protected mixed $_stream;

    /**
     * Конструктор
     */
    public function __construct()
    {
    }

    /**
     * Деструктор
     */
    public function __destruct()
    {
        if ($this->_stream) {
            $this->close();
        }
        unset($this->_stream);
    }

    /**
     * Геттер
     * @param string $property свойство 
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
     * Передвинуть позицию
     *
     * @param integer $offset куда передвинуть позицию
     * @return void
     */
    abstract public function seek(int $offset = 0): void;

    /**
     * Считать из стрима
     *
     * @param int $offset откуда начать считывание
     * @param int $count количество байл которые нужно считать
     * @return string
     */
    abstract public function Read(?int $offset = null, ?int $count = null): bool|string;

    /**
     * Записать в стрим
     *
     * @param string $content контент, которые нужно записать
     * @param int $offset место откуда записать
     * @return void
     */
    abstract public function write(string $content, ?int $offset = null): void;

    /**
     * Считать из стрима одну строку
     *
     * @return string
     */
    abstract public function readLine(): string;

    /**
     * Записать в стрим одну строку
     *
     * @param string $string контент, которые нужно записать
     * @return void
     */
    abstract public function writeLine(string $string): void;

    /**
     * Сохранить изменения
     *
     * @return void
     */
    abstract public function flush(): void;

    /**
     * Закрыть стрим
     *
     * @return void
     */
    abstract public function close(): void;
}
