<?php

/**
 * Interface for database drivers
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Utils\Config
 * @version 1.0.0
 *
 */
namespace Colibri\Data\SqlClient;

/**
 * Interface for managing database connections.
 */
interface IConnection
{
    
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

}