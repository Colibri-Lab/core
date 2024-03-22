<?php

/**
 * Queue
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Queue
 */

namespace Colibri\Queue;

use Colibri\Common\DateHelper;
use Colibri\Utils\ExtendedObject;
use Colibri\Utils\Logs\Logger;

/**
 * Interface for job objects.
 *
 * Represents a job to be executed.
 *
 * @property ?int $id The ID of the job.
 * @property string $queue The queue in which the job resides.
 * @property int $attempts The number of attempts made to execute the job.
 * @property string $class The class name of the job.
 * @property string $payload_class The class name of the payload associated with the job.
 * @property int $attempts
 * @property ExtendedObject $payload The payload object associated with the job.
 */
interface IJob
{
    /**
     * Handles the job.
     *
     * @param Logger $logger The logger instance to use for logging.
     * @return bool True if the job is handled successfully, false otherwise.
     */
    public function Handle(Logger $logger): bool;

    /**
     * Checks if the current attempt is the last attempt.
     *
     * @return bool True if the current attempt is the last attempt, false otherwise.
     */
    public function IsLastAttempt(): bool;

    /**
     * Checks if the job can be executed in parallel.
     *
     * @return bool True if the job can be executed in parallel, false otherwise.
     */
    public function IsParallel(): bool;

    /**
     * Adds the job.
     *
     * @return bool True if the job is successfully added, false otherwise.
     */
    public function Add(): bool;

    /**
     * Updates the job.
     *
     * @return bool True if the job is successfully updated, false otherwise.
     */
    public function Update(): bool;

    /**
     * Deletes the job.
     *
     * @return bool True if the job is successfully deleted, false otherwise.
     */
    public function Delete(): bool;

    /**
     * Begins a transaction.
     *
     * @return bool True if the transaction is successfully begun, false otherwise.
     */
    public function Begin(): bool;

    /**
     * Commits a transaction.
     *
     * @param array|object $result The result of the transaction.
     * @return bool True if the transaction is successfully committed, false otherwise.
     */
    public function Commit(array|object $result): bool;

    /**
     * Marks the job as failed.
     *
     * @param \Throwable $exception The exception that caused the failure.
     * @param bool $isLastAttempt Indicates if the failure is occurring on the last attempt.
     * @return bool True if the job is marked as failed, false otherwise.
     */
    public function Fail(\Throwable $exception, bool $isLastAttempt = false): bool;

    /**
     * Rolls back a transaction.
     *
     * @return bool True if the transaction is successfully rolled back, false otherwise.
     */
    public function Rollback(): bool;

    /**
     * Converts the job to an array.
     *
     * @param bool $dummy Indicates if dummy data should be included in the array.
     * @param \Closure|null $callback An optional callback function to manipulate the array.
     * @return array The array representation of the job.
     */
    public function ToArray(bool $dummy = false, ?\Closure $callback = null): array;

}
