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
interface IConnection
{
    /**
     * Creates a new connection instance from the provided connection information.
     *
     * @param object|array $connectionInfo The connection information as an object or array.
     * @return static A new instance of the connection.
     * @public
     * @static
     */
    public static function FromConnectionInfo(object|array $connectionInfo): static;

    /**
     * Opens a connection to the database.
     *
     * @return bool True if the connection was successfully opened, false otherwise.
     * @public
     */
    public function Open(): bool;

    /**
     * Reopens a connection to the database.
     *
     * @return bool True if the connection was successfully reopened, false otherwise.
     * @public
     */
    public function Reopen(): bool;

    /**
     * Closes the connection to the database.
     *
     * @return void
     * @public
     */
    public function Close(): void;

    /**
     * Check that the service alive
     * @return bool
     * @public
     */
    public function Ping(): bool;


}
