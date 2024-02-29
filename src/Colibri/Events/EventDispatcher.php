<?php

namespace Colibri\Events;

use Colibri\Collections\ArrayList;
use Colibri\Collections\Collection;
use Colibri\Events\Handlers\IClosure;
use Colibri\Events\Handlers\LocalClosure;

/**
 * Менеджер событий
 *
 * @testFunction testEventDispatcher
 */
class EventDispatcher
{
    /**
     * Синглтон
     *
     * @var EventDispatcher
     */
    public static $instance;

    /**
     * Массив событий
     *
     * @var Collection
     */
    private $_events;

    private function __construct()
    {
        $this->_events = new Collection();
    }

    /**
     * Статический конструктор
     */
    public static function Create(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Удаляет обьект
     * @testFunction testEventDispatcherDispose
     */
    public function Dispose(): void
    {
        $this->_events->Clear();
    }

    /**
     * Добавляет обработчик события
     * @testFunction testEventDispatcherAddEventListener
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

        // если не передали listener
        // или если передали обьект и listener не строка
        // то выходим
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
     * Удаляет обработчик события
     * @testFunction testEventDispatcherRemoveEventListener
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
     * Поднять событие
     * @testFunction testEventDispatcherDispatch
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
     * Проверяет наличие обработчика на событие
     * @testFunction testEventDispatcherHasEventListener
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
     * Возвращает список обработчиков события
     * @testFunction testEventDispatcherRegisteredListeners
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
