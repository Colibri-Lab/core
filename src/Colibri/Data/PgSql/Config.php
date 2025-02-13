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
 */
final class Config implements IConfig
{
    public static function DbmsType(): string
    {
        return 'relational';
    }

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
            'json' => ['length' => false, 'db' => 'jsonb', 'generic' => ['Colibri.UI.Forms.Object' => 'ObjectField', 'Colibri.UI.Forms.Array' => 'ArrayField'], 'component' => 'Colibri.UI.Forms.Object', 'param' => 'string']
        ];
    }


    public static function HasIndexes(): bool
    {
        return true;
    }

    public static function FieldsHasPrefix(): bool
    {
        return true;
    }

    public static function HasVirtual(): bool
    {
        return true;
    }

    public static function HasMultiFieldIndexes(): bool
    {
        return true;
    }

    public static function HasAutoincrement(): bool
    {
        return true;
    }

    public static function IndexTypes(): array
    {
        return [
            'NORMAL',
            'UNIQUE'
        ];
    }

    public static function IndexMethods(): array
    {
        return [
            'BTREE',
            'HASH',
            // 'GIST',
            // 'SPGIST',
            // 'GIN',
            // 'BRIN'
        ];
    }

    public static function Symbol(): string
    {
        return '"';
    }

    public static function JsonIndexes(): bool
    {
        return true;
    }


}
