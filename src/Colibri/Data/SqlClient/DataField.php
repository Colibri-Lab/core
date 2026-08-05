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
 * @class
 */
class DataField
{
    /**
     * The name of the database containing the field.
     *
     * @var string
     * @public
     */
    public string $db;

    /**
     * The name of the field.
     *
     * @var string
     * @public
     */
    public string $name;

    /**
     * The original name of the field.
     *
     * @var string
     * @public
     */
    public string $originalName;

    /**
     * The name of the table containing the field.
     *
     * @var string
     * @public
     */
    public string $table;

    /**
     * The original table name of the field.
     *
     * @var string
     * @public
     */
    public string $originalTable;

    /**
     * The escaped name of the field.
     *
     * @var string
     * @public
     */
    public string $escaped;

    /**
     * The default value of the field.
     *
     * @var string
     * @public
     */
    public string $def;

    /**
     * The maximum length of the field.
     *
     * @var int
     * @public
     */
    public int $maxLength;

    /**
     * The length of the field.
     *
     * @var int
     * @public
     */
    public int $length;

    /**
     * The flags associated with the field.
     *
     * @var array
     * @public
     */
    public array $flags;

    /**
     * The data type of the field.
     *
     * @var string
     * @public
     */
    public string $type;

    /**
     * The number of decimals for the field (if applicable).
     *
     * @var int
     * @public
     */
    public int $decimals;

}
