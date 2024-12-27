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
 * Interface for reading data from a data source.
 */
interface IDataReader
{
    /**
     * Retrieves the field names from the data source.
     *
     * @return array An array containing the field names.
     */
    public function Fields(): array;

    /**
     * Reads the next row of data from the data source.
     *
     * @return object|null The next row of data as an object, or null if there are no more rows.
     */
    public function Read(): ?object;

    /**
     * Closes the data reader and releases any resources associated with it.
     *
     * @return void
     */
    public function Close(): void;

    /**
     * Gets the number of rows in the data source.
     *
     * @return int The number of rows in the data source.
     */
    public function Count(): int;

}
