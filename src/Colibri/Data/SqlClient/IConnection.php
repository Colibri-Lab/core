<?php

/**
 * SqlClient
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\SqlClient
 */

namespace Colibri\Data\SqlClient;

/**
 * Interface for managing database connections.
 */
interface IConnection
{
    /**
     * Creates a new instance of the connection from the provided connection information.
     *
     * @param object|array $connectionInfo The connection information as an object or array.
     * @return static A new instance of the connection.
     */
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


}
