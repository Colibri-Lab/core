<?php


/**
 * MySql
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\MySql
 */

namespace Colibri\Data\MySql;

use Colibri\Data\SqlClient\IConfig;

/**
 * Represents query information.
 *
 * This class extends the functionality of SqlQueryInfo, providing additional features and information about a database query.
 * @final
 * @class
 * @implements IConfig
 */
final class Config implements IConfig
{
    /** 
     * Returns the type of database management system (DBMS) used.
     * @return string The type of DBMS.
     * @public
     * @static
     */
    public static function DbmsType(): string
    {
        return 'relational';
    }

    /** 
     * Returns the allowed data types for the database.
     * @return array An array of allowed data types.
     * @public
     * @static
     */
    public static function AllowedTypes(): array
    {
        return [
            'bool' => ['length' => false, 'generic' => 'bool', 'component' => 'Colibri.UI.Forms.Checkbox', 'param' => 'integer', 'convert' => 'fn($v) => $v === true ? 1 : 0'],
            'int' => ['length' => true, 'generic' => 'int', 'component' => 'Colibri.UI.Forms.Number', 'param' => 'integer'],
            'bigint' => ['length' => false, 'generic' => 'int', 'component' => 'Colibri.UI.Forms.Number', 'param' => 'integer'],
            'float' => ['length' => true, 'generic' => 'float', 'component' => 'Colibri.UI.Forms.Number', 'param' => 'double'],
            'double' => ['length' => true, 'generic' => 'float', 'component' => 'Colibri.UI.Forms.Number', 'param' => 'double'],
            'decimal' => ['length' => true, 'generic' => 'float', 'component' => 'Colibri.UI.Forms.Number', 'param' => 'double'],
            'date' => ['length' => false, 'generic' => 'DateField', 'component' => 'Colibri.UI.Forms.Date', 'param' => 'string'],
            'datetime' => ['length' => false, 'generic' => 'DateTimeField', 'component' => 'Colibri.UI.Forms.DateTime', 'param' => 'string'],
            'timestamp' => ['length' => false, 'generic' => 'DateTimeField', 'component' => 'Colibri.UI.Forms.DateTime', 'param' => 'string'],
            'varchar' => ['length' => true, 'generic' => 'string', 'component' => 'Colibri.UI.Forms.Text', 'param' => 'string'],
            'text' => ['length' => false, 'generic' => 'string', 'component' => 'Colibri.UI.Forms.TextArea', 'param' => 'string'],
            'longtext' => ['length' => false, 'generic' => 'string', 'component' => 'Colibri.UI.Forms.TextArea', 'param' => 'string'],
            'mediumtext' => ['length' => false, 'generic' => 'string', 'component' => 'Colibri.UI.Forms.TextArea', 'param' => 'string'],
            'tinytext' => ['length' => true, 'generic' => 'string', 'component' => 'Colibri.UI.Forms.TextArea', 'param' => 'string'],
            'enum' => ['length' => false, 'generic' => 'ValueField', 'component' => 'Colibri.UI.Forms.Select', 'param' => 'string'],
            'json' => ['length' => false, 'generic' => ['Colibri.UI.Forms.Object' => 'ObjectField', 'Colibri.UI.Forms.Array' => 'ArrayField'], 'component' => 'Colibri.UI.Forms.Object', 'param' => 'string']
        ];
    }

    /** 
     * Indicates whether the database has triggers.
     * @return bool True if triggers are supported, false otherwise.
     * @public
     * @static
     */
    public static function HasIndexes(): bool
    {
        return true;
    }
    
    /** 
     * Indicates whether the database has triggers.
     * @public
     * @static
     * @return bool True if triggers are supported, false otherwise.
     */
    public static function HasTriggers(): bool
    {
        return false;
    }

    /** 
     * Indicates whether the database fields have a prefix.
     * @return bool True if fields have a prefix, false otherwise.
     * @public
     * @static
     */
    public static function FieldsHasPrefix(): bool
    {
        return true;
    }

    /** 
     * Indicates whether the database has multi-field indexes.
     * @return bool True if multi-field indexes are supported, false otherwise.
     * @public
     * @static
     */
    public static function HasMultiFieldIndexes(): bool
    {
        return true;
    }

    /** 
     * Indicates whether the database has virtual fields.
     * @return bool True if virtual fields are supported, false otherwise.
     * @public
     * @static
     */
    public static function HasVirtual(): bool
    {
        return true;
    }

    /** 
     * Indicates whether the database has autoincrement fields.
     * @return bool True if autoincrement fields are supported, false otherwise.
     * @public
     * @static
     */
    public static function HasAutoincrement(): bool
    {
        return true;
    }

    /** 
     * Returns the allowed index types for the database.
     * @return array An array of allowed index types.
     * @public
     * @static
     */
    public static function IndexTypes(): array
    {
        return [
            'NORMAL',
            'SPATIAL',
            'UNIQUE',
            'FULLTEXT'
        ];
    }

    /** 
     * Returns the allowed index methods for the database.
     * @public
     * @static
     * @return array An array of allowed index methods.
     */
    public static function IndexMethods(): array
    {
        return [
            'BTREE', 'HASH'
        ];
    }

    /** 
     * Returns the symbol used for quoting identifiers in SQL queries.
     * @return string The symbol used for quoting identifiers.
     * @public
     * @static
     */
    public static function Symbol(): string
    {
        return '`';
    }

    /** 
     * Indicates whether the database supports JSON indexes.
     * @return bool True if JSON indexes are supported, false otherwise.
     * @public
     * @static
     */
    public static function JsonIndexes(): bool
    {
        return false;
    }

}
