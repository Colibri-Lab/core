<?php


/**
 * Sphinx
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\Sphinx
 */

namespace Colibri\Data\Sphinx;

use Colibri\Data\SqlClient\IConfig;

/**
 * Represents query information.
 *
 * This class extends the functionality of SqlQueryInfo, providing additional features and information about a database query.
 * 
 * @inheritDoc
 * @class
 * @final
 * @implements IConfig
 * 
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
            'bigint' => ['length' => false, 'generic' => 'int', 'component' => 'Colibri.UI.Forms.Number', 'index' => true, 'param' => 'integer'],
            'bool' => ['length' => false, 'generic' => 'bool', 'component' => 'Colibri.UI.Forms.Checkbox', 'db' => 'uint', 'index' => true, 'param' => 'integer', 'convert' => 'fn($v) => $v === true ? 1 : 0'],
            'uint' => ['length' => false, 'generic' => 'int', 'component' => 'Colibri.UI.Forms.Number', 'index' => true, 'param' => 'integer'],
            'float' => ['length' => false, 'generic' => 'float', 'component' => 'Colibri.UI.Forms.Number', 'index' => true, 'param' => 'double'],
            'timestamp' => ['length' => false, 'generic' => 'DateTimeToIntField', 'component' => 'Colibri.UI.Forms.DateTime', 'db' => 'bigint', 'index' => true, 'param' => 'integer', 'convert' => 'fn($v) => is_numeric($v) ? $v : strtotime($v)'],
            'string' => ['length' => false, 'generic' => 'string', 'component' => 'Colibri.UI.Forms.Text', 'index' => false, 'param' => 'string'],
            'json' => ['length' => false, 'generic' => ['Colibri.UI.Forms.Object' => 'ObjectField', 'Colibri.UI.Forms.Array' => 'ArrayField'], 'component' => 'Colibri.UI.Forms.Object', 'index' => false, 'param' => 'string'],
            'field' => ['length' => false, 'generic' => 'string', 'component' => 'Colibri.UI.Forms.Text', 'index' => false, 'param' => 'string'],
            'field_string' => ['length' => false, 'generic' => 'string', 'component' => 'Colibri.UI.Forms.Text', 'index' => false, 'param' => 'string'],
        ];
    }
    /**
     * Indicates whether the database has triggers.
     * @return bool True if triggers are supported, false otherwise.
     * @public
     * @static
     */
    public static function HasTriggers(): bool
    {
        return false;
    }
    /**
     * Indicates whether the database has indexes.
     * @return bool True if indexes are supported, false otherwise.
     * @public
     * @static
     */
    public static function HasIndexes(): bool
    {
        return false;
    }
    /**
     * Indicates whether the database has multi-field indexes.
     * @return bool True if multi-field indexes are supported, false otherwise.
     * @public
     * @static
     */
    public static function HasMultiFieldIndexes(): bool
    {
        return false;
    }
    /**
     * Indicates whether the database fields have prefixes.
     * @return bool True if fields have prefixes, false otherwise.
     * @public
     * @static
     */
    public static function HasVirtual(): bool
    {
        return false;
    }
    /**
     * Indicates whether the database supports auto-increment fields.
     * @return bool True if auto-increment is supported, false otherwise.
     * @public
     * @static
     */
    public static function HasAutoincrement(): bool
    {
        return false;
    }
    /**
     * Indicates whether the database fields have prefixes.
     * @return bool True if fields have prefixes, false otherwise.
     * @public
     * @static
     */
    public static function FieldsHasPrefix(): bool
    {
        return false;
    }
    /**
     * Returns the symbol used for quoting identifiers in the database.
     * @return string The symbol used for quoting identifiers.
     * @public
     * @static
     */
    public static function IndexTypes(): array
    {
        return [
            'NORMAL',
            'UNIQUE'
        ];
    }
    /**
     * Returns the index methods supported by the database.
     * @return array An array of supported index methods.
     * @public
     * @static
     */
    public static function IndexMethods(): array
    {
        return [
            'BTREE', 'HASH'
        ];
    }
    /**
     * Returns the symbol used for quoting identifiers in the database.
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
