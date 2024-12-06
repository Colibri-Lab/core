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
interface IConnection
{

    public static function FromConnectionInfo(object|array $connectionInfo): static;
    
    /**
     * Opens a connection to the database.
     *
     * @return bool True if the connection was successfully opened, false otherwise.
     */
    public function Open(): bool;

    /**
     * Reopens a connection to the database.
     *
     * @return bool True if the connection was successfully reopened, false otherwise.
     */
    public function Reopen(): bool;

    /**
     * Closes the connection to the database.
     *
     * @return void
     */
    public function Close(): void;

    /**
     * Check that the service alive
     * @return bool
     */
    public function Ping(): bool;

    
    public static function AllowedTypes(): array;

}