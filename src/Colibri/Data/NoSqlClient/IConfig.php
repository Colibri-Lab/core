<?php

/**
 * NoSqlClient
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\NoSqlClient
 */

namespace Colibri\Data\NoSqlClient;

/**
 * Interface for driver settings
 */
interface IConfig
{
    /**
     * Driver allowed types
     * @return array
     */
    public static function AllowedTypes(): array;

    /**
     * Database has indexes
     * @return bool
     */
    public static function HasIndexes(): bool;

    /**
     * Create prefixed fields in tables
     * @return bool
     */
    public static function FieldsHasPrefix(): bool;

    /**
     * Indexes can be created on multiple fields
     * @return bool
     */
    public static function HasMultiFieldIndexes(): bool;

    /**
     * Can have virtual fields
     * @return bool
     */
    public static function HasVirtual(): bool;

    /**
     * Has autoincrement fields
     * @return bool
     */
    public static function HasAutoincrement(): bool;

    /**
     * Index types
     * @return array
     */
    public static function IndexTypes(): array;

    /**
     * Index methods
     * @return array
     */
    public static function IndexMethods(): array;

    public static function JsonIndexes(): bool;

}
