<?php


namespace Colibri\Events;

use Colibri\Collections\ArrayList;
use Colibri\Collections\Collection;

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
    public function AddEventListener(array|string $ename, mixed $listener, ?object $object = null): bool
    {
        if(is_array($ename)) {
            
            $ret = [];
            foreach($ename as $e) {
                $ret[] = $this->AddEventListener($e, $listener, $object);
            }
            return !in_array(false, $ret);

        }

        // если не передали listener
        // или если передали обьект и listener не строка
        // то выходим
        if (!is_string($ename) || empty($ename) || empty($listener) || (!is_object($object) && !is_string($listener) && !is_callable($listener))) {
            return false;
        }

        $minfo = $listener;
        if (is_object($object)) {
            $minfo = (object)[];
            $minfo->listener = $listener;
            $minfo->object = $object;
        }

        $e = $this->_events->$ename;
        if (is_null($e)) {
            $e = new ArrayList();
            $this->_events->Add($ename, $e);
        }

        if (!$e->Contains($minfo)) {
            $e->Add($minfo);
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

        $minfo = $listener;
        if (is_object($object)) {
            $minfo = (object)[];
            $minfo->listener = $listener;
            $minfo->object = $object;
        }

        return $e->Delete($minfo);
    }

    /**
     * Поднять событие
     * @testFunction testEventDispatcherDispatch
     */
    public function Dispatch(string|Event $event, mixed $args = null): ?object
    {
        if (!($event instanceof Event) || !$this->_events->Exists($event->name)) {
            return null;
        }

        $e = $this->_events->Item($event->name);
        if ($e == null) {
            return null;
        }

        foreach ($e as $item) {
            if (is_callable($item)) {
                $result = $item($event, $args);
            } elseif (is_object($item)) {
                $object = $item->object;
                $listener = $item->listener;
                if (is_callable($listener)) {
                    $newListener = @\Closure::bind($listener, $object);
                    $result = $newListener($event, $args);
                } elseif ((is_string($object) || is_object($object)) && method_exists($object, strval($listener))) {
                    $result = $object->$listener($event, $args);
                }
            } elseif (function_exists(strval($item))) {
                $result = $item($event, $args);
            }

            if (!$result) {
                break;
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
            $minfo = (object)[];
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
