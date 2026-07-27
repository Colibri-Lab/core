<?php


/**
 * Solr
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\Solr
 */

namespace Colibri\Data\Solr;

use Colibri\Data\SqlClient\IConfig;

/**
 * Represents query information.
 *
 * This class extends the functionality of SqlQueryInfo, providing additional features and information about a database query.
 */
final class Config implements IConfig
{
    /**
     * Returns the type of database management system (DBMS) used.
     * @return string The type of DBMS.
     */
    public static function DbmsType(): string
    {
        return 'nosql';
    }

    /**
     * Returns the allowed data types for the database.
     * @return array An array of allowed data types.
     */
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
            'longtext' => ['length' => false, 'generic' => 'string', 'component' => 'Colibri.UI.Forms.TextArea']
        ];
    }

    /**
     * Indicates whether the database has triggers.
     * @return bool True if triggers are supported, false otherwise.
     */
    public static function HasTriggers(): bool
    {
        return false;
    }
    /**
     * Indicates whether the database has indexes.
     * @return bool True if indexes are supported, false otherwise.
     */
    public static function HasIndexes(): bool
    {
        return false;
    }
    /**
     * Indicates whether the database fields have prefixes.
     * @return bool True if fields have prefixes, false otherwise.
     */
    public static function FieldsHasPrefix(): bool
    {
        return false;
    }
    /**
     * Indicates whether the database supports virtual fields.
     * @return bool True if virtual fields are supported, false otherwise.
     */
    public static function HasVirtual(): bool
    {
        return false;
    }

    /**
     * Indicates whether the database supports autoincrement fields.
     * @return bool True if autoincrement fields are supported, false otherwise.
     */
    public static function HasMultiFieldIndexes(): bool
    {
        return false;
    }

    /**
     * Indicates whether the database supports autoincrement fields.
     * @return bool True if autoincrement fields are supported, false otherwise.
     */
    public static function HasAutoincrement(): bool
    {
        return false;
    }
    /**
     * Returns the index types supported by the database.
     * @return array An array of supported index types.
     */
    public static function IndexTypes(): array
    {
        return [];
    }
    /**
     * Returns the index methods supported by the database.
     * @return array An array of supported index methods.
     */
    public static function IndexMethods(): array
    {
        return [];
    }
    /**
     * Returns the symbol used for the database.
     * @return string The symbol used for the database.
     */
    public static function Symbol(): string
    {
        return '';
    }
    /**
     * Indicates whether the database supports JSON indexes.
     * @return bool True if JSON indexes are supported, false otherwise.
     */
    public static function JsonIndexes(): bool
    {
        return false;
    }

}
