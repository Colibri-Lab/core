<?php

/**
 * Handlers
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Events\Handlers
 */

namespace Colibri\Events\Handlers;
use Colibri\App;
use Colibri\Common\VariableHelper;
use Colibri\Events\Event;
use Colibri\Threading\Process;


/**
 * Class LocalClosure
 *
 * Represents a local closure handler for events.
 */
class LocalClosure implements IClosure
{

    /**
     * @var mixed The callable object or function.
     */
    private mixed $_callable;

    /**
     * @var object|null The object associated with the closure.
     */
    private ?object $_object;

    /**
     * LocalClosure constructor.
     *
     * @param mixed $callable The callable object or function.
     * @param object|null $object The object associated with the closure.
     */
    public function __construct(mixed $callable, ?object $object = null) 
    {
        $this->_callable = $callable;
        $this->_object = $object;
    }

    /**
     * Invokes the closure synchronously.
     *
     * @param string|Event $event The event object or its name.
     * @param mixed $args Additional arguments to pass to the closure.
     * @return bool|null True if the closure was successfully invoked, otherwise false. Null if invocation fails.
     */
    public function Invoke(string|Event $event, mixed $args): ?bool
    {
        $listener = $this->_callable;
        if (is_callable($listener)) {
            $result = $listener($event, $args);
        } elseif ($this->_object) {
            $object = $this->_object;
            if (is_callable($listener)) {
                $newListener = @\Closure::bind($listener, $object);
                $result = $newListener($event, $args);
            } elseif ((is_string($object) || is_object($object)) && method_exists($object, strval($listener))) {
                $result = $object->$listener($event, $args);
            }
        } elseif (function_exists(strval($listener))) {
            $result = $listener($event, $args);
        }
        return $result;
    }

    /**
     * Invokes the closure asynchronously.
     *
     * @param string|Event $event The event object or its name.
     * @param mixed $args Additional arguments to pass to the closure.
     * @return Process A Process object representing the asynchronous execution.
     */
    public function AsyncInvoke(string|Event $event, mixed $args): Process
    {
        $worker = new LocalClosureAsyncWorker(0, 0);
        $process = App::$threadingManager->CreateProcess($worker);
        $process->Run((object) ['event' => $event, 'args' => $args, 'closure' => $this->Serialize()]);
        return $process;
    }
    
    /**
     * Serializes the closure.
     *
     * @return string The serialized closure.
     */
    public function Serialize(): string
    {
        $callable = $this->_callable;
        if(is_callable($callable)) {
            $callable = VariableHelper::CallableToString($callable);
        }

        return serialize(['object' => $this->_object, 'callable' => $callable]);
    }

    /**
     * Unserializes a serialized closure.
     *
     * @param string $serialized The serialized closure.
     * @return LocalClosure|null An instance of LocalClosure, or null if unserialization fails.
     */
    public static function Unserialize(string $serialized): ?LocalClosure
    {
        $unserialized = unserialize($serialized);
        if(!$unserialized) {
            return null;
        }

        return new LocalClosure(eval('return ' . $unserialized['callable'] . ';'), $unserialized['object']);
    }

}