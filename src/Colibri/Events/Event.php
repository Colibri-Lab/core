<?php

/**
 * Events
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Events
 */

namespace Colibri\Events;

use InvalidArgumentException;

/**
 * Event class.
 *
 * @property-read string $name The name of the event.
 * @property-read mixed $sender The sender of the event.
 *
 * @testFunction testEvent Used for testing Event.
 */
class Event
{
    /**
     * The sender of the event.
     *
     * @var mixed
     */
    private $_sender;

    /**
     * The name of the event.
     *
     * @var string
     */
    private $_name;

    /**
     * Constructor.
     *
     * @param mixed $sender The sender of the event.
     * @param string $name The name of the event.
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
     * Returns the name of the event.
     *
     * @return string The name of the event.
     */
    protected function getPropertyName()
    {
        return $this->_name;
    }

    /**
     * Returns the sender of the event.
     *
     * @return mixed The sender of the event.
     */
    protected function getPropertySender()
    {
        return $this->_sender;
    }

    /**
     * Getter method.
     *
     * @param string $key The key.
     * @return mixed The value associated with the key.
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
