<?php


/**
 * Events
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Events
 */

namespace Colibri\Events;

/**
 * Basic Event Dispatcher trait.
 * @trait
 * 
 * @example
 * ```php
 * class TextClass {
 *     use TEventDispatcher;
 *     public function doSomething() {
 *         $this->DispatchEvent('textclass.something', ['arg1' => 'value1']);
 *     }
 * }
 * ```
 * 
 */
trait TEventDispatcher
{
    /**
     * Dispatches an event.
     *
     * @param string|Event $event The event object or its name.
     * @param mixed $args Additional arguments to pass to the event handlers.
     * @param bool $async Whether to dispatch the event asynchronously.
     * @return object|null The event object with updated arguments, or null if the event does not exist.
     * @public
     * @example 
     * ```
     * $eventDispatcher = new EventDispatcher();
     * $eventDispatcher->DispatchEvent('myEvent', ['arg1' => 'value1', 'arg2' => 'value2']);
     * 
     * $eventDispatcher = new EventDispatcher();
     * $event = new Event($this, 'myEvent');
     * $eventDispatcher->DispatchEvent($event, ['arg1' => 'value1', 'arg2' => 'value2']);
     * 
     * class MyClass {
     *     use TEventDispatcher;
     *     public function doSomething() {
     *         $this->DispatchEvent('myEvent', ['arg1' => 'value1', 'arg2' => 'value2']);
     *     }
     * }
     * 
     * class MyClass {
     *     use TEventDispatcher;
     *     public function doSomething() {
     *         $event = new Event($this, 'myEvent');
     *         $this->DispatchEvent($event, ['arg1' => 'value1', 'arg2' => 'value2']);
     *     }
     * }
     * ```
     */
    public function DispatchEvent(string|Event $event, mixed $args = null, bool $async = false): ?object
    {
        return EventDispatcher::Instance()->Dispatch(new Event($this, $event), $args, $async);
    }

    /**
     * Adds an event handler.
     *
     * @param array|string $ename The event name or an array of event names.
     * @param mixed $listener The event handler.
     * @return self
     * @public
     * @example
     * ```
     * class MyClass {
     *     use TEventDispatcher;
     *     public function __construct() {
     *         $this->HandleEvent('myEvent', [$this, 'onMyEvent']);
     *     }
     *     public function onMyEvent($event, $args) {
     *         /// Handle the event
     *     }
     * }
     * ```
     */
    public function HandleEvent(array |string $ename, mixed $listener): self
    {
        EventDispatcher::Instance()->AddEventListener($ename, $listener, $this);
        return $this;
    }

    /**
     * Removes an event handler.
     *
     * @param string $ename The event name.
     * @param mixed $listener The event handler to remove.
     * @return self
     * @public
     * @example
     * ```
     * class MyClass {
     *     use TEventDispatcher;
     *     public function __construct() {
     *         $this->HandleEvent('myEvent', [$this, 'onMyEvent']);
     *     }
     *     public function onMyEvent($event, $args) {
     *         /// Handle the event
     *     }
     *     public function removeMyEventHandler() {
     *         $this->RemoveHandler('myEvent', [$this, 'onMyEvent']);
     *     }
     * }
     * ```
     */
    public function RemoveHandler(string $ename, mixed $listener): self
    {
        EventDispatcher::Instance()->RemoveEventListener($ename, $listener);
        return $this;
    }

}
