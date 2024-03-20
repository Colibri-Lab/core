<?php

/**
* Events
*
* @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
* @copyright 2019 ColibriLab
* @package Colibri\Data\Storages
*/

namespace Colibri\Events;

use Colibri\Collections\ArrayList;
use Colibri\Collections\Collection;
use Colibri\Events\Handlers\IClosure;
use Colibri\Events\Handlers\LocalClosure;

/**
 * Event manager.
 */
class EventDispatcher
{
    /**
     * Singleton instance.
     *
     * @var EventDispatcher
     */
    public static $instance;

    /**
     * Array of events.
     *
     * @var Collection
     */
    private $_events;

    /**
     * Constructor.
     */
    private function __construct()
    {
        $this->_events = new Collection();
    }

    /**
     * Static constructor.
     *
     * @return EventDispatcher The EventDispatcher instance.
     */
    public static function Create(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Disposes the object.
     */
    public function Dispose(): void
    {
        $this->_events->Clear();
    }

    /**
     * Adds an event listener.
     *
     * @param array|string $ename The event name or an array of event names.
     * @param mixed $listener The listener function or object.
     * @param object|null $object The object associated with the listener function.
     * @return bool True if the listener was added successfully, false otherwise.
     */
    public function AddEventListener(array |string $ename, mixed $listener, ?object $object = null): bool
    {
        if (is_array($ename)) {

            $ret = [];
            foreach ($ename as $e) {
                $ret[] = $this->AddEventListener($e, $listener, $object);
            }
            return !in_array(false, $ret);

        }

        if (
            !is_string($ename) || empty($ename) || empty($listener) ||
            (!is_object($object) && !is_string($listener) && !is_callable($listener))) {
            return false;
        }

        if(!($listener instanceof IClosure)) {
            if (is_object($object)) {
                $listener = new LocalClosure($listener, $object);
            } else {
                $listener = new LocalClosure($listener);
            }
        }

        $e = $this->_events->$ename;
        if (is_null($e)) {
            $e = new ArrayList();
            $this->_events->Add($ename, $e);
        }

        if (!$e->Contains($listener)) {
            $e->Add($listener);
            return true;
        }

        return false;
    }

    /**
     * Removes an event listener.
     *
     * @param string $ename The event name.
     * @param mixed $listener The listener function or object.
     * @param object|null $object The object associated with the listener function.
     * @return bool True if the listener was removed successfully, false otherwise.
     */
    public function RemoveEventListener(string $ename, mixed $listener, ?object $object = null): bool
    {
        if (!is_string($ename) || empty($ename) || empty($listener) || (!is_object($object) && !is_string($listener) && !is_callable($listener))) {
            return false;
        }

        if (!$this->_events->Exists($ename)) {
            return false;
        }

        /** @var ArrayList */
        $e = $this->_events->$ename;
        if ($e === null) {
            return false;
        }

        if(!($listener instanceof IClosure)) {
            if (is_object($object)) {
                $listener = new LocalClosure($listener, $object);
            } else {
                $listener = new LocalClosure($listener);
            }
        }

        return $e->Delete($listener);
    }

    /**
     * Dispatches an event.
     *
     * @param string|Event $event The event object or its name.
     * @param mixed $args The arguments for the event handlers.
     * @param bool $async Whether to dispatch asynchronously.
     * @return object|null The result of the event dispatching.
     */
    public function Dispatch(string|Event $event, mixed $args = null, bool $async = false): ?object
    {
        if (!($event instanceof Event) || !$this->_events->Exists($event->name)) {
            return null;
        }

        $args = (object)$args;

        /** @var ArrayList */
        $e = $this->_events->Item($event->name);
        if ($e == null) {
            return null;
        }

        foreach ($e as $iclosure) {
            /** @var IClosure */
            if(!($iclosure instanceof IClosure)) {
                continue;
            }

            if($async) {
                $process = $iclosure->AsyncInvoke($event, $args);
                if(!$args->asyncResults) {
                    $args->asyncResults = [];
                }
                $args->asyncResults[] = $process;
            } else {
                $result = $iclosure->Invoke($event, $args);
                if (!$result) {
                    break;
                }
            }

        }

        return $args;
    }

    /**
     * Checks if an event listener exists.
     *
     * @param string $ename The event name.
     * @param mixed $listener The listener function or object.
     * @param object|null $object The object associated with the listener function.
     * @return bool True if the event listener exists, false otherwise.
     */
    public function HasEventListener(string $ename, mixed $listener, ?object $object = null): bool
    {
        if (!is_string($ename) || empty($ename) || empty($listener) || (!is_object($object) && !is_string($listener) && !is_callable($listener))) {
            return false;
        }

        if (!$this->_events->Exists($ename)) {
            return false;
        }

        /** @var ArrayList */
        $e = $this->_events->$ename;
        if ($e == null) {
            return false;
        }

        $minfo = $listener;
        if (is_object($object)) {
            $minfo = (object) [];
            $minfo->listener = $listener;
            $minfo->object = $object;
        }

        return $e->Contains($minfo);
    }

    /**
     * Returns the list of registered event listeners.
     *
     * @param string $ename The event name.
     * @return ArrayList|null The list of registered event listeners.
     */
    public function RegisteredListeners(string $ename = ""): ?ArrayList
    {
        if ($this->_events->Count() == 0) {
            return null;
        }

        $listeners = new ArrayList();
        if (empty($ename)) {
            foreach ($this->_events as $listeners) {
                $listeners->Append($listeners);
            }
        } else {
            if ($this->_events->Exists($ename)) {
                $listeners->Append($this->_events->$ename);
            }
        }

        return $listeners;
    }
}
