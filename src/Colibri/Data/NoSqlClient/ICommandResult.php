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
 */
interface ICommandResult
{
    public function Error(): ?object;

    public function QueryInfo(): object;

    public function ResultData(): array;

    public function SetReturnedId(int $id): void;
    public function SetCollectionName(string $name): void;

    public function MergeWith(ICommandResult $result): void;

}
