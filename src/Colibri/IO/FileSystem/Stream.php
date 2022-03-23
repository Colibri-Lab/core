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
    protected $_length = 0;
    /**
     * Декриптор
     *
     * @var mixed
     */
    protected $_stream;

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
    public function __get($property)
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
    abstract public function seek($offset = 0);

    /**
     * Считать из стрима
     *
     * @param int $offset откуда начать считывание
     * @param int $count количество байл которые нужно считать
     * @return string
     */
    abstract public function read($offset = null, $count = null);

    /**
     * Записать в стрим
     *
     * @param string $content контент, которые нужно записать
     * @param int $offset место откуда записать
     * @return void
     */
    abstract public function write($content, $offset = null);

    /**
     * Считать из стрима одну строку
     *
     * @return string
     */
    abstract public function readLine();

    /**
     * Записать в стрим одну строку
     *
     * @param string $string контент, которые нужно записать
     * @return void
     */
    abstract public function writeLine($string);

    /**
     * Сохранить изменения
     *
     * @return void
     */
    abstract public function flush();

    /**
     * Закрыть стрим
     *
     * @return void
     */
    abstract public function close();
}
