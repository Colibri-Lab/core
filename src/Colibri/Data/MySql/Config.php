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
            'bool' => ['length' => false, 'generic' => 'bool', 'component' => 'Colibri.UI.Forms.Checkbox'],
            'int' => ['length' => true, 'generic' => 'int', 'component' => 'Colibri.UI.Forms.Number'],
            'bigint' => ['length' => false, 'generic' => 'int', 'component' => 'Colibri.UI.Forms.Number'],
            'float' => ['length' => true, 'generic' => 'float', 'component' => 'Colibri.UI.Forms.Number'],
            'double' => ['length' => true, 'generic' => 'float', 'component' => 'Colibri.UI.Forms.Number'],
            'date' => ['length' => false, 'generic' => 'DateField', 'component' => 'Colibri.UI.Forms.Date'],
            'datetime' => ['length' => false, 'generic' => 'DateTimeField', 'component' => 'Colibri.UI.Forms.DateTime'],
            'timestamp' => ['length' => false, 'generic' => 'DateTimeField', 'component' => 'Colibri.UI.Forms.DateTime'],
            'varchar' => ['length' => true, 'generic' => 'string', 'component' => 'Colibri.UI.Forms.Text'],
            'text' => ['length' => false, 'generic' => 'string', 'component' => 'Colibri.UI.Forms.TextArea'],
            'longtext' => ['length' => false, 'generic' => 'string', 'component' => 'Colibri.UI.Forms.TextArea'],
            'mediumtext' => ['length' => false, 'generic' => 'string', 'component' => 'Colibri.UI.Forms.TextArea'],
            'tinytext' => ['length' => true, 'generic' => 'string', 'component' => 'Colibri.UI.Forms.TextArea'],
            'enum' => ['length' => false, 'generic' => 'ValueField', 'component' => 'Colibri.UI.Forms.Select'],
            'json' => ['length' => false, 'generic' => ['Colibri.UI.Forms.Object' => 'ObjectField', 'Colibri.UI.Forms.Array' => 'ArrayField'], 'component' => 'Colibri.UI.Forms.Object']
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

    public static function HasMultiFieldIndexes(): bool
    {
        return true;
    }    


    public static function HasVirtual(): bool
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
            'SPATIAL',
            'UNIQUE',
            'FULLTEXT'
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