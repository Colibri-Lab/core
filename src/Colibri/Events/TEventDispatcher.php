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
     */
    public function DispatchEvent(string|Event $event, mixed $args = null, bool $async = false): ?object
    {
        return EventDispatcher::Create()->Dispatch(new Event($this, $event), $args, $async);
    }

    /**
     * Adds an event handler.
     *
     * @param array|string $ename The event name or an array of event names.
     * @param mixed $listener The event handler.
     * @return self
     */
    public function HandleEvent(array |string $ename, mixed $listener): self
    {
        EventDispatcher::Create()->AddEventListener($ename, $listener, $this);
        return $this;
    }

    /**
     * Removes an event handler.
     *
     * @param string $ename The event name.
     * @param mixed $listener The event handler to remove.
     * @return self
     */
    public function RemoveHandler(string $ename, mixed $listener): self
    {
        EventDispatcher::Create()->RemoveEventListener($ename, $listener);
        return $this;
    }
}
