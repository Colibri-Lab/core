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

    public static function HasIndexes(): bool;

    public static function FieldsHasPrefix(): bool;

    public static function HasMultiFieldIndexes(): bool;

    public static function HasVirtual(): bool;
    public static function HasAutoincrement(): bool;

    public function ExtractFieldInformation(array|object $field): object;
    public function ExtractIndexInformation(array|object $index): object;
    
}