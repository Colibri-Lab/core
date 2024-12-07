<?php

/**
 * Storages
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\Storages
 */

namespace Colibri\Data\Storages;

use Colibri\App;
use Colibri\Common\VariableHelper;
use Colibri\Data\DataAccessPoint;
use Colibri\Data\DataAccessPointsException;
use Colibri\Data\Storages\Models\DataTable as StorageDataTable;
use Colibri\Utils\Config\Config;
use Colibri\Utils\Config\ConfigException;
use Colibri\Utils\Logs\Logger;

/**
 * Represents a collection of storage objects.
 *
 * This class manages multiple storage objects and provides methods to interact with them collectively.
 *
 * @author Vahan P. Grigoryan
 * @package Colibri\Data\Storages
 */
class Storages
{
    /**
     * The singleton instance of the Storages class.
     * @var Storages|null
     */
    private static $instance;

    /**
     * Array of storage data.
     * @var array|null
     */
    private ?array $_storages = null;

    /**
     * Types of storages.
     * @var array|null
     */
    private ?array $_types = null;

    /**
     * Constructs a Storages object.
     */
    public function __construct()
    {

        $this->_types = [];
        try {
            $storagesConfig = App::$config->Query('databases.storages');
            $this->_storages = $storagesConfig->AsArray();
            foreach ($this->_storages as $name => $storage) {
                if ($name === '__global_types') {
                    $this->_types = VariableHelper::Extend($this->_types, $this->_storages[$name]);
                    unset($this->_storages[$name]);
                } else {
                    $this->_storages[$name]['name'] = $name;
                    $this->_storages[$name]['path'] = $storagesConfig->GetFile();
                }
            }
        } catch (ConfigException $e) {
            $this->_storages = [];
        }

        // Собираем хранилища из модулей
        try {
            $modules = App::$config->Query('modules.entries');
        } catch (ConfigException $e) {
            $modules = [];
        }

        foreach ($modules as $moduleConfig) {
            if (!$moduleConfig->Query('enabled')->GetValue()) {
                continue;
            }
            /** @var Config $moduleConfig */
            try {

                $keysArray = $moduleConfig->Query('for', [])->ToArray();
                if (!empty($keysArray) && !in_array(App::$domainKey, $keysArray)) {
                    continue;
                }

                $tablePrefix = $moduleConfig->Query('config.databases.table-prefix', '')->GetValue();
                $config = $moduleConfig->Query('config.databases.storages');
                $storagesConfig = $config->AsArray();
                foreach ($storagesConfig as $name => $storage) {
                    $storagesConfig[$name] = $moduleConfig->Query('config.databases.storages.' . $name)->AsArray();
                    if ($name === '__global_types') {
                        $this->_types = VariableHelper::Extend($this->_types, $storagesConfig[$name]);
                        unset($storagesConfig[$name]);
                    } else {
                        $storagesConfig[$name]['name'] = $name;
                        $storagesConfig[$name]['prefix'] = $tablePrefix;
                        $storagesConfig[$name]['file'] = $config->GetFile();
                    }
                }
                $this->_storages = array_merge($this->_storages, $storagesConfig);
            } catch (ConfigException $e) {

            }
        }

        $this->_storages = $this->_replaceTypes($this->_storages);


    }

    /**
     * Extracts a short field record
     * @param string $field
     * @return array
     */
    private function _fromShortString(string $field): array
    {
        $parts = explode(',', $field);
        $type = trim($parts[0] ?? 'varchar');
        $class = trim($parts[1] ?? 'string');
        $component = trim($parts[2] ?? 'Text');
        $desc = trim($parts[3] ?? '');
        $note = trim($parts[4] ?? '');
        $default = trim($parts[5] ?? '');
        $length = null;

        if (strstr($type, '(') !== false) {
            $type = trim($type, ')');
            $type = explode('(', $type);
            $length = (int) $type[1];
            $type = $type[0];
        }

        $return = [
            'type' => $type,
            'class' => $class,
            'component' => $component,
            'desc' => $desc,
            'note' => $note,
            'default' => $default
        ];
        if ($length) {
            $return['length'] = $length;
        }

        return $return;
    }

    /**
     * Replaces a global types
     * @param array $fields
     * @return array
     */
    private function _replaceTypes(array $fields): array
    {

        foreach ($fields as $name => $field) {

            if (is_string($field)) {
                // короткая запись
                // type(length?), class, component
                $field = $this->_fromShortString($field);
                $fields[$name] = $field;
            }

            if (isset($field['inherit']) && $this->_types[$field['inherit']]) {
                $inherit = $field['inherit'];
                unset($field['inherit']);
                $fields[$name] = VariableHelper::Extend($this->_types[$inherit], $field, true);
                $field = $fields[$name];
            }

            if (isset($field['fields'])) {
                $fields[$name]['fields'] = $this->_replaceTypes($field['fields']);
            }


        }

        return $fields;
    }

    /**
     * Singleton
     * @return Storages
     */
    public static function Create()
    {
        if (!self::$instance) {
            self::$instance = new Storages();
        }
        return self::$instance;
    }

    #region "Checking"

    /**
     * Migrates storage tables and fields.
     *
     * @param Logger $logger  The logger object to log messages.
     * @param bool   $isDev   Indicates whether the migration is for development purposes (optional, default: false).
     *
     * @return void
     */
    public function Migrate(Logger $logger, bool $isDev = false)
    {
        $langModule = App::$moduleManager->Get('lang');
        $logger->info('Starting migration process');

        try {

            foreach ($this->_storages as $name => $xstorage) {

                $prefix = isset($xstorage['prefix']) ? $xstorage['prefix'] : '';
                $table_name = $prefix ? $prefix . '_' . $name : $name;

                $logger->info($name . ': Migrating storage');

                if (!$xstorage['access-point']) {
                    $logger->error($name . ': No access point found');
                    continue;
                }

                $dtp = App::$dataAccessPoints->Get($xstorage['access-point']);
                if($dtp->dbms === DataAccessPoint::DBMSTypeRelational) {
                    $reader = $dtp->Tables($table_name);
                    if ($reader->Count() == 0) {
                        $logger->error($table_name . ': Storage destination not found: creating');
                        $this->_createStorageTable($logger, $dtp, $prefix, $name);
                    }
    
                    // проверяем наличие и типы полей, и если отличаются пересоздаем
                    $ofields = [];
                    $reader = $dtp->Fields($table_name);
                    while ($ofield = $reader->Read()) {
    
                        $ofield->GENERATION_EXPRESSION = preg_replace_callback("/_utf8mb\d\\\'(.*?)\\\'/", function ($match) {
                            return '\'' . $match[1] . '\'';
                        }, $ofield->GENERATION_EXPRESSION);
    
                        $ofields[$ofield->COLUMN_NAME] = (object) [
                            'Field' => $ofield->COLUMN_NAME,
                            'Type' => $ofield->COLUMN_TYPE,
                            'Null' => $ofield->IS_NULLABLE,
                            'Key' => $ofield->COLUMN_KEY,
                            'Default' => $ofield->COLUMN_DEFAULT,
                            'Extra' => $ofield->EXTRA ?? '',
                            'Expression' => $ofield->GENERATION_EXPRESSION ?? ''
                        ];
                    }
    
                    try {
    
                        $indexesReader = $dtp->Indexes($table_name);
                        $indices = [];
                        while ($index = $indexesReader->Read()) {
                            if ($index->Key_name && $index->Key_name != 'PRIMARY') {
                                // индексы возвращаются отдельно для каждого поля
                                // Sec_in_index указывает на какой позиции стоит поле
                                if (!isset($indices[$index->Key_name])) {
                                    if (isset($index->Seq_in_index)) {
                                        $index->Column_name = [($index->Seq_in_index - 1) => $index->Column_name];
                                    }
                                    $indices[$index->Key_name] = $index;
                                } else {
                                    $indices[$index->Key_name]->Column_name[$index->Seq_in_index - 1] = $index->Column_name;
                                }
                            }
                        }
                    } catch(\Throwable $e) {
                        $logger->error($table_name . ' does not exists');
                    }
    
                    $virutalFields = [];
    
                    $xfields = $xstorage['fields'] ?? [];
                    $logger->error($table_name . ': Checking fields');
                    foreach ($xfields as $fieldName => $xfield) {
                        $fname = $name . '_' . $fieldName;
                        $fparams = $xfield['params'] ?? [];
    
                        if (($xfield['virtual'] ?? false) === true) {
                            $virutalFields[$fieldName] = $xfield;
                            continue;
                        }
    
                        if ($xfield['type'] == 'enum') {
                            $xfield['type'] .= isset($xfield['values']) && $xfield['values'] ? '(' . implode(',', array_map(function ($v) {
                                return '\'' . $v['value'] . '\'';
                            }, $xfield['values'])) . ')' : '';
                        } elseif ($xfield['type'] === 'bool' || $xfield['type'] === 'boolean') {
                            $xfield['type'] = 'tinyint';
                            $xfield['length'] = 1;
                            if(isset($xfield['default'])) {
                                $xfield['default'] = $xfield['default'] === 'true' ? 1 : 0;
                            }
                        } elseif ($xfield['type'] === 'json') {
                            $fparams['required'] = false;
                        }
    
                        $xdesc = isset($xfield['desc']) ? $xfield['desc'] : '';
                        if ($langModule) {
                            $xdesc = $xdesc[$langModule->Default()] ?? $xdesc;
                        }
    
                        if (!isset($ofields[$fname])) {
                            $logger->error($name . ': ' . $fieldName . ': Field destination not found: creating');
                            $this->_createStorageField($logger, $dtp, $prefix, $name, $fieldName, $xfield['type'], isset($xfield['length']) ? $xfield['length'] : null, isset($xfield['default']) ? $xfield['default'] : null, isset($fparams['required']) ? $fparams['required'] : false, $xdesc);
                        } else {
                            // проверить на соответствие
                            $ofield = $ofields[$fname];
    
                            $required = isset($fparams['required']) ? $fparams['required'] : false;
                            $default = isset($xfield['default']) ? $xfield['default'] : null;
                            [, $length,] = $this->_updateDefaultAndLength($fieldName, $xfield['type'], $required, $xfield['length'] ?? null, $default);
    
                            $orType = $ofield->Type != $xfield['type'] . ($length ? '(' . $length . ')' : '');
                            $orDefault = $ofield->Default != $default;
                            $orRequired = $required != ($ofield->Null == 'NO');
    
                            if ($orType || $orDefault || $orRequired) {
                                $logger->error($name . ': ' . $fieldName . ': Field destination changed: updating');
                                $this->_alterStorageField($logger, $dtp, $prefix, $name, $fieldName, $xfield['type'], isset($xfield['length']) ? $xfield['length'] : null, $default, $required, $xdesc);
                            }
                        }
                    }
    
                    foreach ($virutalFields as $fieldName => $xVirtualField) {
                        $fname = $name . '_' . $fieldName;
                        $fparams = $xVirtualField['params'] ?? [];
                        $xdesc = isset($xVirtualField['desc']) ? $xVirtualField['desc'] : '';
                        if ($langModule) {
                            $xdesc = $xdesc[$langModule->Default()] ?? $xdesc;
                        }
                        if (!isset($ofields[$fname])) {
                            $this->_createStorageVirtualField($logger, $dtp, $prefix, $name, $fieldName, $xVirtualField['type'], isset($xVirtualField['length']) ? $xVirtualField['length'] : null, $xVirtualField['expression'], $xdesc);
                        } else {
                            $ofield = $ofields[$fname];
    
                            $required = isset($fparams['required']) ? $fparams['required'] : false;
                            $expression = isset($xVirtualField['expression']) ? $xVirtualField['expression'] : null;
    
                            $orType = $ofield->Type != $xVirtualField['type'] . ($length ? '(' . $length . ')' : '');
                            $orExpression = $ofield->Expression != $expression;
                            $orRequired = $required != ($ofield->Null == 'NO');
    
                            if ($orType || $orExpression || $orRequired) {
                                $logger->error($name . ': ' . $fieldName . ': Field destination changed: updating');
                                $this->_alterStorageVirtualField($logger, $dtp, $prefix, $name, $fieldName, $xVirtualField['type'], isset($xVirtualField['length']) ? $xVirtualField['length'] : null, $expression, $xdesc);
                            }
    
                        }
                    }
    
                    $xindexes = isset($xstorage['indices']) ? $xstorage['indices'] : [];
                    $logger->error($name . ': Checking indices');
                    foreach ($xindexes as $indexName => $xindex) {
                        if (!isset($indices[$indexName])) {
                            $logger->error($name . ': ' . $indexName . ': Index not found: creating');
                            $this->_createStorageIndex($logger, $dtp, $prefix, $name, $indexName, $xindex['fields'], $xindex['type'], $xindex['method']);
                        } else {
                            $oindex = $indices[$indexName];
                            $fields1 = $name . '_' . implode(',' . $name . '_', $xindex['fields']);
                            $fields2 = implode(',', $oindex->Column_name);
    
                            $xtype = isset($xindex['type']) ? $xindex['type'] : 'NORMAL';
                            $xmethod = isset($xindex['method']) ? $xindex['method'] : 'BTREE';
                            if ($xtype === 'FULLTEXT') {
                                $xmethod = '';
                            }
    
                            $otype = 'NORMAL';
                            $omethod = 'BTREE';
                            if ($oindex->Index_type == 'FULLTEXT') {
                                $otype = 'FULLTEXT';
                                $omethod = '';
                            }
                            if ($oindex->Non_unique == 0) {
                                $otype = 'UNIQUE';
                                $omethod = $oindex->Index_type;
                            }
    
                            if ($fields1 != $fields2 || $xtype != $otype || $xmethod != $omethod) {
                                $logger->error($name . ': ' . $indexName . ': Index changed: updating');
                                $this->_alterStorageIndex($logger, $dtp, $prefix, $name, $indexName, $xindex['fields'], $xtype, $xmethod);
                            }
                        }
                    }
                } else {

                    if(!$dtp->CollectionExists($table_name)) {
                        $dtp->CreateCollection($table_name);
                    }

                    $dtp->CreateCustomFields($table_name);

                    // надо создать поля
                    $ofields = $dtp->GetFields($table_name);
                    if(!$ofields) {
                        continue;
                    }

                    $xfields = $xstorage['fields'] ?? [];
                    foreach ($xfields as $fieldName => $xfield) {

                        $fname = $fieldName;
                        $fparams = $xfield['params'] ?? [];
                        
                        $fieldFound = VariableHelper::FindInArray($ofields->ResultData(), 'name', $fname);
    
                        if ($xfield['type'] == 'enum') {
                            $xfield['type'] .= isset($xfield['values']) && $xfield['values'] ? '(' . implode(',', array_map(function ($v) {
                                return '\'' . $v['value'] . '\'';
                            }, $xfield['values'])) . ')' : '';
                        } elseif ($xfield['type'] === 'bool' || $xfield['type'] === 'boolean') {
                            if(isset($xfield['default'])) {
                                $xfield['default'] = $xfield['default'] === 'true';
                            }
                        } elseif ($xfield['type'] === 'json') {
                            $xfield['type'] = 'text';
                            $fparams['required'] = false;
                            $xfield['indexed'] = false;
                        }
    
                        $xdesc = isset($xfield['desc']) ? $xfield['desc'] : '';
                        if ($langModule) {
                            $xdesc = $xdesc[$langModule->Default()] ?? $xdesc;
                        }
    
                        if (!$fieldFound) {
                            $logger->error($name . ': ' . $fname . ': Field destination not found: creating');
                            $dtp->AddField(
                                $table_name,
                                $fname,
                                $xfield['type'],
                                $fparams['required'] ?? false,
                                $xfield['indexed'] ?? true,
                                $xfield['default'] ?? null
                            );
                        } else {
                            $required = $fparams['required'] ?? false;
                            $default = $xfield['default'] ?? null;
    
                            $orType = $fieldFound->type != $xfield['type'];
                            $orDefault = ($fieldFound?->default ?? null) != $default;
                            $orRequired = ($fieldFound?->required ?? false) != $required;

                            if ($orType || $orDefault || $orRequired) {
                                $logger->error($name . ': ' . $fname . ': Field destination changed: updating');
                                // проверить на соответствие
                                $dtp->ReplaceField(
                                    $table_name,
                                    $fname,
                                    $xfield['type'],
                                    $required,
                                    $xfield['indexed'] ?? true,
                                    $default
                                );
                            }
                        }
                    
                    }

                }


            }

            $logger->error('Migrating data ...');
            foreach ($this->_storages as $name => $xstorage) {
                $logger->error($name . ': Checking storage ...');

                $module = $xstorage['module'];
                $tableClass = $xstorage['models']['table'];
                if(strstr($tableClass, 'Models\\') === false) {
                    $tableClass = 'Models\\' . $tableClass;
                }
                $tableModel = 'App\\Modules\\' . $module . '\\' . $tableClass;
                $module = App::$moduleManager->$module;

                if (is_object($module) && method_exists($tableModel, 'DataMigrate')) {
                    $logger->error($name . ': Migrating data ...');
                    $tableModel::DataMigrate($logger);
                }

            }


            $logger->debug('Creating module seeds');
            foreach(App::$moduleManager->list as $module) {
                if(method_exists($module, 'Seeders')) {
                    $module->Seeders($logger);
                }
            }
            $logger->debug('Seeders successful');

        } catch (DataAccessPointsException $e) {
            if ($isDev) {
                $logger->emergency('Returned with exception: ' . $e->getMessage());
            } else {
                throw $e;
            }
        }

    }

    /**
     * Creates a new storage table.
     *
     * @param Logger            $logger      The logger object to log messages.
     * @param DataAccessPoint   $accessPoint The data access point object.
     * @param string            $prefix      The prefix for the storage table.
     * @param string            $table       The name of the table to be created.
     * @param bool              $levels      Indicates whether the table supports hierarchical levels (optional, default: false).
     *
     * @return void
     */
    private function _createStorageTable(
        Logger $logger,
        DataAccessPoint $accessPoint,
        string $prefix,
        string $table,
        bool $levels = false
    ) {
        $query = $accessPoint->CreateQuery('CreateDefaultStorageTable', [$table, $prefix]);
        $res = $accessPoint->Query($query, ['type' => DataAccessPoint::QueryTypeNonInfo]);
        if ($res->error) {
            $logger->error($table . ': Can not create destination: ' . $res->query);
            throw new DataAccessPointsException('Can not create destination: ' . $res->query);
        }
    }

    /**
     * Updates the default value and length of a storage field.
     *
     * @param string $field    The name of the field to be updated.
     * @param string $type     The new data type of the field.
     * @param bool   $required Indicates if the field is required.
     * @param int|null $length The new length of the field if applicable (nullable).
     * @param mixed  $default  The new default value for the field.
     *
     * @return array An array containing the updated default value and length.
     */
    private function _updateDefaultAndLength(
        string $field,
        string $type,
        bool $required,
        ?int $length,
        mixed $default
    ): array {

        if (\is_bool($default)) {
            $default = $default ? 'TRUE' : 'FALSE';
        }

        if ($type == 'json') {
            $default = $default ? '(' . $default . ')' : null;
            $required = false;
        } elseif (strstr($type, 'enum') !== false) {
            $default = $default ? "'" . $default . "'" : null;
        } elseif (strstr($type, 'char') !== false) {
            $default = $default ? "'" . $default . "'" : null;
        }

        if ($type == 'varchar' && !$length) {
            $length = 255;
        }

        return [$required, $length, $default];

    }

    /**
     * Creates a new storage field.
     *
     * @param Logger            $logger      The logger object to log messages.
     * @param DataAccessPoint   $accessPoint The data access point object.
     * @param string            $prefix      The prefix for the storage field.
     * @param string            $table       The name of the table where the field will be created.
     * @param string            $field       The name of the field to be created.
     * @param string            $type        The data type of the field.
     * @param int|null          $length      The length of the field if applicable (nullable).
     * @param mixed             $default     The default value for the field.
     * @param bool|null         $required    Indicates if the field is required (nullable).
     * @param string|null       $comment     A comment describing the field (nullable).
     *
     * @return void
     */
    private function _createStorageField(
        Logger $logger,
        DataAccessPoint $accessPoint,
        string $prefix,
        string $table,
        string $field,
        string $type,
        ?int $length,
        mixed $default,
        ?bool $required,
        ?string $comment
    ) {
        [$required, $length, $default] = $this->_updateDefaultAndLength($field, $type, $required, $length, $default);

        // ! специфика UUID нужно выключить параметр sql_log_bin
        $sqlLogBinVal = 0;
        if (strstr($default, 'UUID') !== false) {
            $reader = $accessPoint->Query('SELECT @@sql_log_bin as val');
            $sqlLogBinVal = $reader->Read()->val;
            if ($sqlLogBinVal == 1) {
                $accessPoint->Query('set sql_log_bin=0', ['type' => DataAccessPoint::QueryTypeNonInfo]);
            }
        }

        $res = $accessPoint->Query('
            ALTER TABLE `' . ($prefix ? $prefix . '_' : '') . $table . '` 
            ADD COLUMN `' . $table . '_' . $field . '` ' . $type . ($length ? '(' . $length . ')' : '') . ($required ? ' NOT NULL' : ' NULL') . ' 
            ' . ($default ? 'DEFAULT ' . $default . ' ' : '') . ($comment ? ' COMMENT \'' . $comment . '\'' : ''), ['type' => DataAccessPoint::QueryTypeNonInfo]);

        if ($sqlLogBinVal == 1) {
            $accessPoint->Query('set sql_log_bin=1', ['type' => DataAccessPoint::QueryTypeNonInfo]);
        }

        if ($res->error) {
            $logger->error($table . ': Can not save field: ' . $res->query);
            throw new DataAccessPointsException('Can not save field: ' . $res->query);
        }
    }

    /**
     * Creates a new virtual storage field.
     *
     * @param Logger            $logger      The logger object to log messages.
     * @param DataAccessPoint   $accessPoint The data access point object.
     * @param string            $prefix      The prefix for the storage field.
     * @param string            $table       The name of the table where the field will be created.
     * @param string            $field       The name of the virtual field to be created.
     * @param string            $type        The data type of the virtual field.
     * @param int|null          $length      The length of the virtual field if applicable (nullable).
     * @param string|null       $expression  The SQL expression defining the virtual field (nullable).
     * @param string|null       $comment     A comment describing the virtual field (nullable).
     *
     * @return void
     */
    private function _createStorageVirtualField(
        Logger $logger,
        DataAccessPoint $accessPoint,
        string $prefix,
        string $table,
        string $field,
        string $type,
        ?int $length,
        ?string $expression,
        ?string $comment
    ) {

        $res = $accessPoint->Query('
            ALTER TABLE `' . ($prefix ? $prefix . '_' : '') . $table . '` 
            ADD COLUMN `' . $table . '_' . $field . '` ' . $type . ($length ? '(' . $length . ')' : '') . ' 
            GENERATED ALWAYS AS (' . $expression . ') STORED ' .
            ($comment ? ' COMMENT \'' . $comment . '\'' : ''), ['type' => DataAccessPoint::QueryTypeNonInfo]);

        if ($res->error) {
            $logger->error($table . ': Can not save field: ' . $res->query);
            throw new DataAccessPointsException('Can not save field: ' . $res->query);
        }
    }


    /**
     * Alters an existing storage field.
     *
     * @param Logger            $logger      The logger object to log messages.
     * @param DataAccessPoint   $accessPoint The data access point object.
     * @param string            $prefix      The prefix for the storage field.
     * @param string            $table       The name of the table where the field exists.
     * @param string            $field       The name of the field to be altered.
     * @param string            $type        The new data type of the field.
     * @param int|null          $length      The new length of the field if applicable (nullable).
     * @param mixed             $default     The new default value for the field.
     * @param bool              $required    Indicates if the field is required.
     * @param string|null       $comment     A comment describing the field (nullable).
     *
     * @return void
     */
    private function _alterStorageField(
        Logger $logger,
        DataAccessPoint $accessPoint,
        string $prefix,
        string $table,
        string $field,
        string $type,
        ?int $length,
        mixed $default,
        bool $required,
        ?string $comment
    ) {

        [$required, $length, $default] = $this->_updateDefaultAndLength($field, $type, $required, $length, $default);

        $res = $accessPoint->Query(
            '
            ALTER TABLE `' . ($prefix ? $prefix . '_' : '') . $table . '` 
            MODIFY COLUMN `' . $table . '_' . $field . '` ' . $type . ($length ? '(' . $length . ')' : '') . ($required ? ' NOT NULL' : ' NULL') . ' 
            ' . (!is_null($default) ? 'DEFAULT ' . $default . ' ' : '') . ($comment ? 'COMMENT \'' . $comment . '\'' : ''),
            ['type' => DataAccessPoint::QueryTypeNonInfo]
        );
        if ($res->error) {
            $logger->error($table . ': Can not save field: ' . $res->query);
            throw new DataAccessPointsException('Can not save field: ' . $res->query);
        }

    }

    /**
     * Alters an existing virtual storage field.
     *
     * @param Logger            $logger      The logger object to log messages.
     * @param DataAccessPoint   $accessPoint The data access point object.
     * @param string            $prefix      The prefix for the storage field.
     * @param string            $table       The name of the table where the field exists.
     * @param string            $field       The name of the virtual field to be altered.
     * @param string            $type        The new data type of the virtual field.
     * @param int|null          $length      The new length of the virtual field if applicable (nullable).
     * @param string            $expression  The new SQL expression defining the virtual field.
     * @param string            $comment     A comment describing the virtual field.
     *
     * @return void
     */
    private function _alterStorageVirtualField(
        Logger $logger,
        DataAccessPoint $accessPoint,
        string $prefix,
        string $table,
        string $field,
        string $type,
        ?int $length,
        string $expression,
        string $comment
    ) {

        $res = $accessPoint->Query(
            '
            ALTER TABLE `' . ($prefix ? $prefix . '_' : '') . $table . '` 
            MODIFY COLUMN `' . $table . '_' . $field . '` ' . $type . ($length ? '(' . $length . ')' : '') .
            ' GENERATED ALWAYS AS (' . $expression . ') STORED ' .
            ($comment ? ' COMMENT \'' . $comment . '\'' : ''),
            ['type' => DataAccessPoint::QueryTypeNonInfo]
        );
        if ($res->error) {
            $logger->error($table . ': Can not save field: ' . $res->query);
            throw new DataAccessPointsException('Can not save field: ' . $res->query);
        }

    }

    /**
     * Creates a new storage index.
     *
     * @param Logger            $logger      The logger object to log messages.
     * @param mixed             $accessPoint The data access point object.
     * @param string            $prefix      The prefix for the storage index.
     * @param string            $table       The name of the table where the index will be created.
     * @param string            $indexName   The name of the index to be created.
     * @param array             $fields      An array of field names included in the index.
     * @param string            $type        The type of the index (e.g., 'INDEX', 'UNIQUE', 'FULLTEXT').
     * @param string|null       $method      The method to be used for creating the index (optional).
     *
     * @return void
     */
    private function _createStorageIndex(
        Logger $logger,
        $accessPoint,
        string $prefix,
        string $table,
        string $indexName,
        array $fields,
        string $type,
        ?string $method
    ) {
        if ($type === 'FULLTEXT') {
            $method = '';
        }

        $res = $accessPoint->Query('
            ALTER TABLE `' . ($prefix ? $prefix . '_' : '') . $table . '` 
            ADD' . ($type !== 'NORMAL' ? ' ' . $type : '') . ' INDEX `' . $indexName . '` (`' . $table . '_' . implode('`,`' . $table . '_', $fields) . '`) ' . ($method ? ' USING ' . $method : '') . '
        ', ['type' => DataAccessPoint::QueryTypeNonInfo]);
        if ($res->error) {
            $logger->error($table . ': Can not create index: ' . $res->query);
            throw new DataAccessPointsException('Can not create index: ' . $res->query);
        }
    }

    /**
     * Alters an existing storage index.
     *
     * @param Logger            $logger      The logger object to log messages.
     * @param DataAccessPoint   $accessPoint The data access point object.
     * @param string            $prefix      The prefix for the storage index.
     * @param string            $table       The name of the table where the index exists.
     * @param string            $indexName   The name of the index to be altered.
     * @param array             $fields      An array of field names included in the index.
     * @param string            $type        The type of the index (e.g., 'INDEX', 'UNIQUE', 'FULLTEXT').
     * @param string|null       $method      The method to be used for altering the index (optional).
     *
     * @return void
     */
    private function _alterStorageIndex(
        Logger $logger,
        DataAccessPoint $accessPoint,
        string $prefix,
        string $table,
        string $indexName,
        array $fields,
        string $type,
        ?string $method
    ) {

        $res = $accessPoint->Query('
            ALTER TABLE `' . ($prefix ? $prefix . '_' : '') . $table . '` 
            DROP INDEX `' . $indexName . '`
        ', ['type' => DataAccessPoint::QueryTypeNonInfo]);
        if ($res->error) {
            $logger->error($table . ': Can not delete index: ' . $res->query);
            throw new DataAccessPointsException('Can not delete index: ' . $res->query);
        }

        $res = $accessPoint->Query('
            ALTER TABLE `' . $table . '` 
            ADD' . ($type !== 'NORMAL' ? ' ' . $type : '') . ' INDEX `' . $indexName . '` (`' . $table . '_' . implode('`,`' . $table . '_', $fields) . '`) ' . ($method ? ' USING ' . $method : '') . '
        ', ['type' => DataAccessPoint::QueryTypeNonInfo]);
        if ($res->error) {
            $logger->error($table . ': Can not create index: ' . $res->query);
            throw new DataAccessPointsException('Can not create index: ' . $res->query);
        }
    }

    /**
    * Удаляет таблицу из базы данных
    * @param DataAccessPoint $accessPoint точка доступа
    * @param string $table таблица
    * @return void
    * @code
    * private function _dropStorageTable(Logger $logger, $accessPoint, $table)
    * {
    *   $res = $accessPoint->Query('rename table `' . $table . '` to `_' . $table . '_backup_' . date('YmdHis', time()) . '`', ['type' => DataAccessPoint::QueryTypeNonInfo]);
    *   if($res->error) {
    *       $logger->error($table.': Can not delete destination: ' . $res->query);
    *       throw new DataAccessPointsException('Can not delete destination: ' . $res->query);
    *   }
    * }
    * @endcode
    */
    #endregion

    /**
     * Checks if a storage with the specified name exists.
     * 
     * ```
     * If you need to check existance of storage
     * 
     * if(Storages::Create()->Exists('langs', 'lang')) { ... }
     * 
     * ```
     *
     * @param string      $name   The name of the storage to check.
     * @param string|null $module The module to which the storage belongs (optional).
     *
     * @return bool True if the storage exists, false otherwise.
     */
    public function Exists(string $name, ?string $module = null): bool
    {
        return isset($this->_storages[$name]);
    }

    /**
     * Loads the storage with the specified name and optional module.
     * 
     * ```
     * For example you can get storage structure information for storage "langs" in module "lang"
     *  
     * $storage = Storages::Create()->Load('langs', 'lang');
     * 
     * ```
     *
     * @param string      $name   The name of the storage to load.
     * @param string|null $module The module to which the storage belongs (optional).
     *
     * @return Storage|null The loaded storage object if found, or null if not found.
     */
    public function Load(string $name, ?string $module = null): ?Storage
    {
        if (!isset($this->_storages[$name])) {
            return null;
        }
        return new Storage($this->_storages[$name], $name);
    }


    /**
     * Retrieves an array of all storages.
     *
     * @return array An array containing all storage objects.
     */
    public function GetStorages(): array
    {
        $storages = [];
        foreach ($this->_storages as $xstorage) {
            $storage = new Storage($xstorage);
            $storages[$storage->name] = $storage;
        }
        return $storages;
    }

    /**
     * Magic method to retrieve the value of inaccessible properties.
     *
     * @param string $prop The name of the property to retrieve.
     *
     * @return mixed The value of the specified property.
     */
    public function __get(string $prop): mixed
    {
        $prop = strtolower($prop);
        $return = null;
        switch ($prop) {
            case 'settings':
                $return = $this->_storages;
                break;
            case 'accessPoint':
                $return = $this->_accessPoint;
                break;
            case 'modulepath':
                $return = $this->_modulePath;
                break;
            default: {
                if ($this->Exists($prop)) {
                    return $this->Load($prop);
                }
            }
        }
        return $return;
    }


}
