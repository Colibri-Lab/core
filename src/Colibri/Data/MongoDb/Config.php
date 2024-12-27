<?php


/**
 * MongoDb
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\MongoDb
 */

namespace Colibri\Data\MongoDb;

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
        return 'nosql';
    }


    public static function AllowedTypes(): array
    {
        return [
            'bool' => ['length' => false, 'generic' => 'bool', 'component' => 'Colibri.UI.Forms.Checkbox'],
            'int' => ['length' => false, 'generic' => 'int', 'component' => 'Colibri.UI.Forms.Number'],
            'bigint' => ['length' => false, 'generic' => 'int', 'component' => 'Colibri.UI.Forms.Number'],
            'float' => ['length' => false, 'generic' => 'float', 'component' => 'Colibri.UI.Forms.Number'],
            'date' => ['length' => false, 'generic' => 'DateField', 'component' => 'Colibri.UI.Forms.Date'],
            'datetime' => ['length' => false, 'generic' => 'DateTimeField', 'component' => 'Colibri.UI.Forms.DateTime'],
            'timestamp' => ['length' => false, 'generic' => 'DateTimeField', 'component' => 'Colibri.UI.Forms.DateTime', 'db' => 'datetime'],
            'varchar' => ['length' => false, 'generic' => 'string', 'component' => 'Colibri.UI.Forms.Text'],
            'longtext' => ['length' => false, 'generic' => 'string', 'component' => 'Colibri.UI.Forms.TextArea'],
            'json' => ['length' => false, 'generic' => 'ObjectField', 'component' => 'Colibri.UI.Forms.Object']
        ];
    }

    public static function HasIndexes(): bool
    {
        return false;
    }

    public static function FieldsHasPrefix(): bool
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

    public static function IndexTypes(): array
    {
        return [];
    }

    public static function IndexMethods(): array
    {
        return [];
    }

    public static function Symbol(): string
    {
        return '';
    }
    public static function JsonIndexes(): bool
    {
        return false;
    }

}
