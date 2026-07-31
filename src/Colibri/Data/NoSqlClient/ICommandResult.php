<?php

/**
 * SqlClient
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\NoSqlClient
 */

namespace Colibri\Data\NoSqlClient;

/**
 * Interface for managing database connections.
 * @interface
 */
interface ICommandResult
{
    /**
     * Returns the error object from the command execution, if any.
     */
    public function Error(): ?object;

    /**
     * Returns information about the query execution.
     */
    public function QueryInfo(): object;

    /**
     * Returns the result data from the command execution.
     */
    public function ResultData(): array;

    /**
     * Sets the returned ID for the command result.
     *
     * @param int $id The returned ID.
     */
    public function SetReturnedId(int $id): void;

    /**
     * Sets the collection name for the command result.
     *
     * @param string $name The collection name.
     */
    public function SetCollectionName(string $name): void;

    /**
     * Merges the current command result with another command result.
     *
     * @param ICommandResult $result The command result to merge with.
     */
    public function MergeWith(ICommandResult $result): void;

}
