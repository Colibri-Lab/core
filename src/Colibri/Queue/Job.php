<?php

namespace Colibri\Queue;
use Colibri\Common\DateHelper;
use Colibri\Utils\ExtendedObject;
use Colibri\Utils\Logs\Logger;

/**
 * @property ?int $id
 * @property string $queue
 * @property int $attempts
 * @property string $class
 * @property string $payload_class
 * @property int $attempts
 * @property ExtendedObject $payload
 */

abstract class Job extends ExtendedObject implements IJob
{

    protected static $maxAttempts = 5;

    public static function Create(ExtendedObject $payload, string $queue = 'default', int $attempts = 0, bool $parallel = false, ?int $id = null): static
    {
        $job = new static();
        $job->payload = $payload;
        $job->payload_class = get_class($payload);
        $job->class = static::class;
        $job->attempts = $attempts;
        $job->queue = $queue;
        $job->parallel = $parallel;
        $job->id = $id;
        return $job;
    }

    public abstract function Handle(Logger $logger): bool;

    public function IsLastAttempt(): bool
    {
        return ($this->attempts ?: 0) > static::$maxAttempts;
    }
    
    public function IsParallel(): bool
    {
        return ($this->parallel ?: false);
    }

    public function Add(): bool
    {
        return Manager::Create()->AddJob($this);
    }

    public function Update(): bool
    {
        return Manager::Create()->UpdateJob($this);
    }

    public function Delete(): bool
    {
        return Manager::Create()->DeleteJob($this);
    }

    public function Begin(): bool
    {
        $this->attempts ++;
        $this->datereserved = DateHelper::ToDbString();
        return Manager::Create()->UpdateJob($this);
    }

    public function Commit(array|object $result): bool
    {
        /** @var Manager */
        $manager = Manager::Create();
        if(!$manager->SuccessJob($this, $result)) {
            return false;
        }
        if(!$manager->DeleteJob($this)) {
            return false;
        }
        return true;
    }

    public function Fail(\Throwable $exception, bool $isLastAttempt = false): bool
    {
        /** @var Manager */
        $manager = Manager::Create();
        if(!$manager->FailJob($this, $exception)) {
            return false;
        }
        if($isLastAttempt && !$manager->DeleteJob($this)) {
            return false;
        }
        return true;
    }

    public function Rollback(): bool
    {
        $this->attempts += 1;
        $this->datereserved = null;
        return Manager::Create()->UpdateJob($this);
    }

    public function ToArray(bool $dummy = false, ?\Closure $callback = null): array
    {
        return [
            'queue' => $this->queue,
            'attempts' => $this->attempts,
            'datereserved' => $this->datereserved,
            'parallel' => $this->parallel,
            'class' => static::class,
            'payload_class' => get_class($this->payload),
            'payload' => json_encode($this->payload),
            'id' => $this->id ?: null
        ];
    }

}