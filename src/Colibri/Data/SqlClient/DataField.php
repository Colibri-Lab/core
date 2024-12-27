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
 * Represents a data field in a database table.
 */
class DataField
{
    /**
     * The name of the database containing the field.
     *
     * @var string
     */
    public string $db;

    /**
     * The name of the field.
     *
     * @var string
     */
    public string $name;

    /**
     * The original name of the field.
     *
     * @var string
     */
    public string $originalName;

    /**
     * The name of the table containing the field.
     *
     * @var string
     */
    public string $table;

    /**
     * The original table name of the field.
     *
     * @var string
     */
    public string $originalTable;

    /**
     * The escaped name of the field.
     *
     * @var string
     */
    public string $escaped;

    /**
     * The default value of the field.
     *
     * @var string
     */
    public string $def;

    /**
     * The maximum length of the field.
     *
     * @var int
     */
    public int $maxLength;

    /**
     * The length of the field.
     *
     * @var int
     */
    public int $length;

    /**
     * The flags associated with the field.
     *
     * @var array
     */
    public array $flags;

    /**
     * The data type of the field.
     *
     * @var string
     */
    public string $type;

    /**
     * The number of decimals for the field (if applicable).
     *
     * @var int
     */
    public int $decimals;

}
