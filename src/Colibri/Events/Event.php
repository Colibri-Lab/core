<?php

/**
 * Класс события
 * 
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Events
 * @version 1.0.0
 * 
 * 
 */

namespace Colibri\Events;

use InvalidArgumentException;

/**
 * Класс событие
 *
 * @property-read string $name
 * @property-read mixed $sender
 *
 * @testFunction testEvent
 */
class Event
{

    /**
     * Отправитель
     *
     * @var mixed
     */
    private $_sender;

    /**
     * Наименование события
     *
     * @var string
     */
    private $_name;

    /**
     * Конструктор
     *
     * @param mixed $sender
     * @param string $name
     * @throws InvalidArgumentException
     */
    public function __construct($sender, $name)
    {
        if (!is_object($sender) || !is_string($name)) {
            throw new InvalidArgumentException();
        }

        $this->_sender = $sender;
        $this->_name = $name;
    }

    /**
     * Возвращает наименование события
     * @return string 
     */
    protected function getPropertyName()
    {
        return $this->_name;
    }

    /**
     * Возвращает отправителя события
     * @return mixed 
     */
    protected function getPropertySender()
    {
        return $this->_sender;
    }

    /**
     * Getter
     * @param string $key 
     * @return mixed 
     */
    public function __get($key)
    {
        $return = null;
        switch (strtolower($key)) {
            case "name": {
                    $return = $this->getPropertyName();
                    break;
                }
            case "sender": {
                    $return = $this->getPropertySender();
                    break;
                }
            default: {
                    $return = null;
                }
        }
        return $return;
    }
}