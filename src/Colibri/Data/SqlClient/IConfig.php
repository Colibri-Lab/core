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
 * Interface for driver settings
 */
interface IConfig
{
    /**
     * Returns an DBMS type, i.e. relational or nosql
     * @return string
     */
    public static function DbmsType(): string;

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
     * Database has triggers
     * @return bool
     */
    public static function HasTriggers(): bool;

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

    /**
     * Symbol to escape table and field names
     * @return string
     */
    public static function Symbol(): string;

    /**
     * Can index json fields
     * @return bool
     */
    public static function JsonIndexes(): bool;


}
