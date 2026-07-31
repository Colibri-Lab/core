<?php


/**
 * PgSql
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\PgSql
 */

namespace Colibri\Data\PgSql;

use Colibri\Data\SqlClient\IConfig;

/**
 * Represents query information.
 *
 * This class extends the functionality of SqlQueryInfo, providing additional features and information about a database query.
 * 
 * @inheritDoc
 * @final
 * @class
 * @implements IConfig
 */
final class Config implements IConfig
{
    /**
     * Returns the type of database management system (DBMS) used.
     * @return string The type of DBMS.
     */
    public static function DbmsType(): string
    {
        return 'relational';
    }

    /**
     * Returns the allowed data types for the database.
     * @return array An array of allowed data types.
     */
    public static function AllowedTypes(): array
    {
        return [
            'bool' => ['length' => false, 'generic' => 'bool', 'component' => 'Colibri.UI.Forms.Checkbox', 'param' => 'integer', 'convert' => 'fn($v) => $v === true ? 1 : 0'],
            'int2' => ['length' => false, 'generic' => 'int', 'component' => 'Colibri.UI.Forms.Number', 'param' => 'integer'],
            'int4' => ['length' => false, 'generic' => 'int', 'component' => 'Colibri.UI.Forms.Number', 'param' => 'integer'],
            'int8' => ['length' => false, 'generic' => 'int', 'component' => 'Colibri.UI.Forms.Number', 'param' => 'integer'],
            'float4' => ['length' => true, 'generic' => 'float', 'component' => 'Colibri.UI.Forms.Number', 'param' => 'double'],
            'float8' => ['length' => true, 'generic' => 'float', 'component' => 'Colibri.UI.Forms.Number', 'param' => 'double'],
            'date' => ['length' => false, 'generic' => 'DateField', 'component' => 'Colibri.UI.Forms.Date', 'param' => 'string'],
            'timestamp' => ['length' => false, 'generic' => 'DateTimeField', 'component' => 'Colibri.UI.Forms.DateTime', 'param' => 'string'],
            'varchar' => ['length' => true, 'generic' => 'string', 'component' => 'Colibri.UI.Forms.Text', 'param' => 'string'],
            'text' => ['length' => false, 'generic' => 'string', 'component' => 'Colibri.UI.Forms.TextArea', 'param' => 'string'],
            'point' => ['length' => false, 'generic' => 'string', 'db' => 'GEOGRAPHY(POINT,4326)', 'component' => 'Colibri.UI.Forms.Text', 'param' => 'string'],
            'line' => ['length' => false, 'generic' => 'string', 'db' => 'GEOMETRY(LineString,4326)', 'component' => 'Colibri.UI.Forms.Text', 'param' => 'string'],
            'geometry' => ['length' => false, 'generic' => 'string', 'db' => 'GEOMETRY(GEOMETRY, 4326)', 'component' => 'Colibri.UI.Forms.Text', 'param' => 'string'],
            'json' => ['length' => false, 'db' => 'jsonb', 'generic' => ['Colibri.UI.Forms.Object' => 'ObjectField', 'Colibri.UI.Forms.Array' => 'ArrayField'], 'component' => 'Colibri.UI.Forms.Object', 'param' => 'string']
        ];
    }

    /**
     * Indicates whether the database has indexes.
     * @return bool True if the database has indexes, false otherwise.
     */
    public static function HasIndexes(): bool
    {
        return true;
    }

    /**
     * Indicates whether the database has triggers.
     * @return bool True if the database has triggers, false otherwise.
     */
    public static function HasTriggers(): bool
    {
        return true;
    }

    /**
     * Indicates whether the database fields have a prefix.
     * @return bool True if the database fields have a prefix, false otherwise.
     */
    public static function FieldsHasPrefix(): bool
    {
        return true;
    }

    /**
     * Indicates whether the database has multi-field indexes.
     * @return bool True if the database has multi-field indexes, false otherwise.
     */
    public static function HasVirtual(): bool
    {
        return true;
    }

    /**
     * Indicates whether the database has virtual fields.
     * @return bool True if the database has virtual fields, false otherwise.
     */
    public static function HasMultiFieldIndexes(): bool
    {
        return true;
    }

    /**
     * Indicates whether the database has autoincrement fields.
     * @return bool True if the database has autoincrement fields, false otherwise.
     */
    public static function HasAutoincrement(): bool
    {
        return true;
    }

    /**
     * Returns the index types supported by the database.
     * @return array An array of supported index types.
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
     */
    public static function IndexMethods(): array
    {
        return [
            'BTREE',
            'HASH',
            'GIST',
            'SPGIST',
            // 'GIN',
            // 'BRIN'
        ];
    }

    /**
     * Returns the symbol used for quoting identifiers in the database.
     * @return string The symbol used for quoting identifiers.
     */
    public static function Symbol(): string
    {
        return '"';
    }

    /**
     * Indicates whether the database supports JSON indexes.
     * @return bool True if the database supports JSON indexes, false otherwise.
     */
    public static function JsonIndexes(): bool
    {
        return true;
    }


}
