<?php

namespace Colibri\Queue;
use Colibri\Common\DateHelper;
use Colibri\Utils\ExtendedObject;
use Colibri\Utils\Logs\Logger;

/**
 * @property ?int $id
 * @property string $queue
 * @property int $attempts
 * @property ExtendedObject $payload
 */
interface IJob  
{

    public function Handle(Logger $logger): bool;

    public function IsLastAttempt(): bool;

    public function IsParallel(): bool;

    public function Add(): bool;

    public function Update(): bool;

    public function Delete(): bool;

    public function Begin(): bool;

    public function Commit(array|object $result): bool;

    public function Fail(\Throwable $exception, bool $isLastAttempt = false): bool;

    public function Rollback(): bool;

    public function ToArray(bool $dummy = false): array;

}