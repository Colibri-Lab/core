<?php

/**
 * Добавки в класс, который собирается работать с событиями
 * 
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Events
 * @version 1.0.0
 * 
 * 
 */

namespace Colibri\Events;

/**
 * Базовый класс "Диспетчер событий"
 */
trait TEventDispatcher
{

    /**
     * Поднять событие
     *
     * @param string $event
     * @param mixed $args
     * @return mixed
     * @testFunction testDispatchEvent
     */
    public function DispatchEvent($event, $args = null)
    {
        return EventDispatcher::Create()->Dispatch(new Event($this, $event), $args);
    }

    /**
     * Добавить обработчик события
     *
     * @param string $ename
     * @param mixed $listener
     * @return mixed
     * @testFunction testHandleEvent
     */
    public function HandleEvent($ename, $listener)
    {
        EventDispatcher::Create()->AddEventListener($ename, $listener, $this);
        return $this;
    }

    /**
     * Удалить обработчик события
     *
     * @param string $ename
     * @param mixed $listener
     * @return mixed
     * @testFunction testRemoveHandler
     */
    public function RemoveHandler($ename, $listener)
    {
        EventDispatcher::Create()->RemoveEventListener($ename, $listener);
        return $this;
    }
}
