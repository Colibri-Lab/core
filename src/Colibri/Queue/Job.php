<?php

namespace Colibri\Queue;
use Colibri\Common\DateHelper;
use Colibri\Utils\ExtendedObject;

abstract class Job extends ExtendedObject 
{

    protected static $maxAttempts = 5;

    public static function Create(ExtendedObject $payload, string $queue = 'default', int $attempts = 0): static
    {
        $job = new static();
        $job->payload = $payload;
        $job->class = static::class;
        $job->attempts = $attempts;
        $job->queue = $queue;
        return $job;
    }

    public abstract function Handle(): bool;

    public function IsLastAttempt(): bool
    {
        return ($this->attempts ?: 0) > static::$maxAttempts;
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

    public function ToArray(bool $dummy = false): array
    {
        return [
            'queue' => $this->queue,
            'attempts' => $this->attempts,
            'class' => static::class,
            'payload' => json_encode($this->payload),
            'id' => $this->id ?: null
        ];
    }

}