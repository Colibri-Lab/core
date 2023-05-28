<?php

namespace Colibri\Events\Handlers;
use Colibri\App;
use Colibri\Common\VariableHelper;
use Colibri\Events\Event;
use Colibri\Threading\Process;


class LocalClosure implements IClosure
{

    private mixed $_callable;

    private ?object $_object;

    public function __construct(mixed $callable, ?object $object = null) 
    {
        $this->_callable = $callable;
        $this->_object = $object;
    }

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

    public function AsyncInvoke(string|Event $event, mixed $args): Process
    {
        $worker = new LocalClosureAsyncWorker(0, 0);
        $process = App::$threadingManager->CreateProcess($worker);
        $process->Run((object) ['event' => $event, 'args' => $args, 'closure' => $this->Serialize()]);
        return $process;
    }

    public function Serialize(): string
    {
        $callable = $this->_callable;
        if(is_callable($callable)) {
            $callable = VariableHelper::CallableToString($callable);
        }

        return serialize(['object' => $this->_object, 'callable' => $callable]);
    }

    public static function Unserialize(string $serialized): ?LocalClosure
    {
        $unserialized = unserialize($serialized);
        if(!$unserialized) {
            return null;
        }

        return new LocalClosure(eval('return ' . $unserialized['callable'] . ';'), $unserialized['object']);
    }

}