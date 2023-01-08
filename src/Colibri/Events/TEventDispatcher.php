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
     * @testFunction testDispatchEvent
     */
    public function DispatchEvent(string|Event $event, mixed $args = null): ?object
    {
        return EventDispatcher::Create()->Dispatch(new Event($this, $event), $args);
    }

    /**
     * Добавить обработчик события
     * @testFunction testHandleEvent
     */
    public function HandleEvent(array |string $ename, mixed $listener): self
    {
        EventDispatcher::Create()->AddEventListener($ename, $listener, $this);
        return $this;
    }

    /**
     * Удалить обработчик события
     * @testFunction testRemoveHandler
     */
    public function RemoveHandler(string $ename, mixed $listener): self
    {
        EventDispatcher::Create()->RemoveEventListener($ename, $listener);
        return $this;
    }
}