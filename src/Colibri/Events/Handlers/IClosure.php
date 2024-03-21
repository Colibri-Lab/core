<?php

/**
 * Handlers
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Events\Handlers
 */

namespace Colibri\Events\Handlers;
use Colibri\Events\Event;
use Colibri\Threading\Process;

/**
 * Interface IClosure
 *
 * Represents a closure handler for events.
 */
interface IClosure
{

    /**
     * Invokes the closure synchronously.
     *
     * @param string|Event $event The event object or its name.
     * @param mixed $args Additional arguments to pass to the closure.
     * @return bool|null True if the closure was successfully invoked, otherwise false. Null if invocation fails.
     */
    public function Invoke(string|Event $event, mixed $args): ?bool;

    /**
     * Invokes the closure asynchronously.
     *
     * @param string|Event $event The event object or its name.
     * @param mixed $args Additional arguments to pass to the closure.
     * @return Process|null A Process object representing the asynchronous execution, or null if async invocation fails.
     */
    public function AsyncInvoke(string|Event $event, mixed $args): ?Process;

    /**
     * Serializes the closure.
     *
     * @return string The serialized closure.
     */
    public function Serialize(): string;

    /**
     * Unserializes a serialized closure.
     *
     * @param string $serialized The serialized closure.
     * @return IClosure|null An instance of IClosure, or null if unserialization fails.
     */
    public static function Unserialize(string $serialized): ?IClosure;

}