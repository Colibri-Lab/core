<?php

namespace Colibri\Events\Handlers;
use Colibri\Events\Event;
use Colibri\Threading\Process;

interface IClosure
{

    public function Invoke(string|Event $event, mixed $args): ?bool;

    public function AsyncInvoke(string|Event $event, mixed $args): ?Process;

    public function Serialize(): string;

    public static function Unserialize(string $serialized): ?IClosure;

}