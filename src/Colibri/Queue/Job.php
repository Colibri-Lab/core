<?php

/**
 * Queue
 *
 * Represents an abstract class for jobs in the queue system.
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Queue
 */

namespace Colibri\Queue;

use Colibri\App;
use Colibri\Common\DateHelper;
use Colibri\Threading\Process;
use Colibri\Utils\ExtendedObject;
use Colibri\Utils\Logs\Logger;

/**
 * Abstract class Job
 *
 * Represents a job to be executed.
 *
 * @property ExtendedObject $payload The payload object associated with the job.
 * @property array $headers The headers payload associated with the job.
 * @property string $payload_class The class name of the payload associated with the job.
 * @property string $class The class name of the job.
 * @property int $attempts The number of attempts made to execute the job.
 * @property string $queue The queue in which the job resides.
 * @property bool $parallel Indicates if the job can be executed in parallel.
 * @property ?int $id The ID of the job.
 */
abstract class Job extends ExtendedObject implements IJob
{
    /**
     * Maximum number of attempts for executing the job.
     *
     * @var int
     */
    protected static $maxAttempts = 5;

    /**
     * Creates a new job instance.
     *
     * @param ExtendedObject $payload The payload object associated with the job.
     * @param string $queue The queue in which the job resides.
     * @param int $attempts The number of attempts made to execute the job.
     * @param bool $parallel Indicates if the job can be executed in parallel.
     * @param ?int $id The ID of the job.
     * @return static The created job instance.
     */
    public static function Create(
        ExtendedObject $payload,
        string $queue = 'default',
        int $attempts = 0,
        bool $parallel = false,
        ?int $id = null
    ): static {
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

    /**
     * Handles the job.
     *
     * @param Logger $logger The logger instance to use for logging.
     * @return bool True if the job is handled successfully, false otherwise.
     */
    abstract public function Handle(Logger $logger): bool;

    /**
     * Add headers to job manager
     * @overloads
     */
    public function SetHeaders(): void
    {
        $this->headers = [];
    }

    public function Key(): string
    {
        return md5($this?->id);
    }

    /**
     * Checks if the current attempt is the last attempt.
     *
     * @return bool True if the current attempt is the last attempt, false otherwise.
     */
    public function IsLastAttempt(): bool
    {
        return ($this->attempts ?: 0) > static::$maxAttempts;
    }

    /**
     * Checks if the job can be executed in parallel.
     *
     * @return bool True if the job can be executed in parallel, false otherwise.
     */
    public function IsParallel(): bool
    {
        return ($this->parallel ?: false);
    }

    /**
     * Adds the job.
     *
     * @param string|null $startDate The start date of the job.
     * @return bool True if the job is successfully added, false otherwise.
     */
    public function Add(?string $startDate = null): bool
    {
        return Manager::Create()->AddJob($this, $startDate);
    }

    /**
     * Updates the job.
     *
     * @param string|null $startDate The start date of the job.
     * @return bool True if the job is successfully updated, false otherwise.
     */
    public function Update(?string $startDate = null): bool
    {
        return Manager::Create()->UpdateJob($this, $startDate);
    }

    /**
     * Deletes the job.
     *
     * @return bool True if the job is successfully deleted, false otherwise.
     */
    public function Delete(): bool
    {
        return Manager::Create()->DeleteJob($this);
    }

    /**
     * Begins a transaction.
     *
     * @return bool True if the transaction is successfully begun, false otherwise.
     */
    public function Begin(): bool
    {
        $this->attempts ++;
        $this->datereserved = DateHelper::ToDbString();
        return Manager::Create()->UpdateJob($this);
    }

    /**
     * Commits a transaction.
     *
     * @param array|object $result The result of the transaction.
     * @return bool True if the transaction is successfully committed, false otherwise.
     */
    public function Commit(array|object $result, bool $stopProcess = false): bool
    {
        
        // killing a process if exists
        if($stopProcess && $this->IsParallel()) {
            $parallelWorkerKey = $this->Key();
            $i = 10;
            while(true && $i > 0) {
                $pid = Process::PidByWorkerName($parallelWorkerKey);
                $pid && Process::StopProcess($pid);
                $i--;
            }
        }

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

    /**
     * Marks the job as failed.
     *
     * @param \Throwable $exception The exception that caused the failure.
     * @param bool $isLastAttempt Indicates if the failure is occurring on the last attempt.
     * @return bool True if the job is marked as failed, false otherwise.
     */
    public function Fail(\Throwable $exception, bool $isLastAttempt = false, bool $stopProcess = false): bool
    {

        // killing a process if exists
        if($stopProcess && $this->IsParallel()) {
            $parallelWorkerKey = $this->Key();
            $i = 10;
            while(true && $i > 0) {
                $pid = Process::PidByWorkerName($parallelWorkerKey);
                $pid && Process::StopProcess($pid);
                $i--;
            }
        }

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

    /**
     * Rolls back a transaction.
     * @param ?int $delaySeconds delay before run again
     * @return bool True if the transaction is successfully rolled back, false otherwise.
     */
    public function Rollback(?int $delaySeconds = null): bool
    {
        $this->attempts += 1;
        $this->datereserved = null;
        $this->reservation_key = null;
        $this->datestart = (new \DateTime('now'))
            ->modify('+'.$delaySeconds.' seconds')->format('Y-m-d H:i:s');
        return Manager::Create()->UpdateJob($this);
    }

    /**
     * Converts the job to an array.
     *
     * @param bool $dummy Indicates if dummy data should be included in the array.
     * @param \Closure|null $callback An optional callback function to manipulate the array.
     * @return array The array representation of the job.
     */
    public function ToArray(bool $dummy = false, ?\Closure $callback = null): array
    {
        return [
            'queue' => $this->queue,
            'attempts' => $this->attempts,
            'datereserved' => $this->datereserved,
            'parallel' => $this->parallel,
            'class' => static::class,
            'payload_class' => get_class($this->payload),
            'payload' => json_encode(['data' => $this->payload, 'headers' => $this->headers]),
            'id' => $this->id ?: null
        ];
    }


}
