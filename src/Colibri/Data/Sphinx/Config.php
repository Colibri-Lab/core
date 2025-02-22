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
            'bigint' => ['length' => false, 'generic' => 'int', 'component' => 'Colibri.UI.Forms.Number', 'index' => true, 'param' => 'integer'],
            'bool' => ['length' => false, 'generic' => 'bool', 'component' => 'Colibri.UI.Forms.Checkbox', 'db' => 'uint', 'index' => true, 'param' => 'integer', 'convert' => 'fn($v) => $v === true ? 1 : 0'],
            'uint' => ['length' => false, 'generic' => 'int', 'component' => 'Colibri.UI.Forms.Number', 'index' => true, 'param' => 'integer'],
            'float' => ['length' => false, 'generic' => 'float', 'component' => 'Colibri.UI.Forms.Number', 'index' => true, 'param' => 'double'],
            'timestamp' => ['length' => false, 'generic' => 'DateTimeToIntField', 'component' => 'Colibri.UI.Forms.DateTime', 'db' => 'bigint', 'index' => true, 'param' => 'integer', 'convert' => 'fn($v) => is_string($v) ? strtotime($v) : $v'],
            'string' => ['length' => false, 'generic' => 'string', 'component' => 'Colibri.UI.Forms.Text', 'index' => false, 'param' => 'string'],
            'json' => ['length' => false, 'generic' => ['Colibri.UI.Forms.Object' => 'ObjectField', 'Colibri.UI.Forms.Array' => 'ArrayField'], 'component' => 'Colibri.UI.Forms.Object', 'index' => false, 'param' => 'string'],
            'field' => ['length' => false, 'generic' => 'string', 'component' => 'Colibri.UI.Forms.Text', 'index' => false, 'param' => 'string'],
            'field_string' => ['length' => false, 'generic' => 'string', 'component' => 'Colibri.UI.Forms.Text', 'index' => false, 'param' => 'string'],
        ];
    }

    public static function HasIndexes(): bool
    {
        return false;
    }

    public static function HasMultiFieldIndexes(): bool
    {
        return false;
    }

    public static function HasVirtual(): bool
    {
        return false;
    }

    public static function HasAutoincrement(): bool
    {
        return false;
    }

    public static function FieldsHasPrefix(): bool
    {
        return false;
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
            'BTREE', 'HASH'
        ];
    }

    public static function Symbol(): string
    {
        return '`';
    }

    public static function JsonIndexes(): bool
    {
        return false;
    }

}
