<?php

/**
 * Structure
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
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
 * Storages object
 * @author Vahan P. Grigoryan
 * @package Colibri\Data\Storages
 */
class Storages
{

    private static $instance;

    /**
     * Array of storage data
     * @var array|null
     */
    private ?array $_storages = null;

    /**
     * Types
     * @var array|null
     */
    private ?array $_types = null;

    /**
     * Constructs an Storages object
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
     * Migrates a database
     * @param Logger $logger
     * @param bool $isDev
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
                $reader = $dtp->Query('SHOW TABLES LIKE \'' . $table_name . '\'');
                if ($reader->count == 0) {
                    $logger->error($table_name . ': Storage destination not found: creating');
                    $this->_createStorageTable($logger, $dtp, $prefix, $name);
                } 

                // проверяем наличие и типы полей, и если отличаются пересоздаем
                $ofields = array();
                $reader = $dtp->Query('SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=\'' . $dtp->point->database . '\' and TABLE_NAME=\'' . $table_name . '\'');
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

                $indexesReader = $dtp->Query('SHOW INDEX FROM ' . $table_name);
                $indices = array();
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

                $virutalFields = [];

                $xfields = $xstorage['fields'] ?? [];
                $logger->error($table_name . ': Checking fields');
                foreach ($xfields as $fieldName => $xfield) {
                    $fname = $name . '_' . $fieldName;
                    $fparams = $xfield['params'] ?? [];

                    if (($xfield['virtual'] ?? false) === true) {
                        $virutalFields[$fieldName] = $xfield;
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

                // TODO: хуйня какая то
                // $xobjects = isset($xstorage['objects']) ? $xstorage['objects'] : [];
                // foreach ($xobjects as $xobject) {
                //     if (isset($xobject['json'])) {
                //         $object = json_decode($xobject['json']);
                //         $datatable = new StorageDataTable(App::$dataAccessPoints->Get($name));
                //         if (!$datatable->SaveRow($datatable->CreateEmptyRow($object))) {

                //         }
                //     }
                // }

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

        } catch (DataAccessPointsException $e) {
            if ($isDev) {
                $logger->emergency('Returned with exception: ' . $e->getMessage());
            } else {
                throw $e;
            }
        }

    }

    /**
     * Creates a storage table
     * @param Logger $logger
     * @param DataAccessPoint $accessPoint
     * @param string $prefix
     * @param string $table
     * @param bool $levels
     * @throws DataAccessPointsException
     * @return void
     */
    private function _createStorageTable(Logger $logger, DataAccessPoint $accessPoint, string $prefix, string $table, bool $levels = false)
    {
        $res = $accessPoint->Query('
            create table `' . ($prefix ? $prefix . '_' : '') . $table . '`(
                `' . $table . '_id` bigint unsigned auto_increment, 
                `' . $table . '_datecreated` timestamp not null default CURRENT_TIMESTAMP, 
                `' . $table . '_datemodified` timestamp not null default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP, 
                `' . $table . '_datedeleted` timestamp null, 
                primary key ' . $table . '_primary (`' . $table . '_id`), 
                key `' . $table . '_datecreated_idx` (`' . $table . '_datecreated`),
                key `' . $table . '_datemodified_idx` (`' . $table . '_datemodified`),
                key `' . $table . '_datedeleted_idx` (`' . $table . '_datedeleted`)
            ) DEFAULT CHARSET=utf8', ['type' => DataAccessPoint::QueryTypeNonInfo]);
        if ($res->error) {
            $logger->error($table . ': Can not create destination: ' . $res->query);
            throw new DataAccessPointsException('Can not create destination: ' . $res->query);
        }
    }

    /**
     * Update a default value
     * @param string $field
     * @param string $type
     * @param bool $required
     * @param int $length
     * @param mixed $default
     * @return array
     */
    private function _updateDefaultAndLength(string $field, string $type, bool $required, ?int $length, mixed $default): array
    {

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
     * Creates a storage field
     * @param Logger $logger
     * @param DataAccessPoint $accessPoint
     * @param string $prefix
     * @param string $table
     * @param string $field
     * @param string $type
     * @param int $length
     * @param mixed $default
     * @param bool $required
     * @param string $comment
     * @throws DataAccessPointsException
     * @return void
     */
    private function _createStorageField(Logger $logger, DataAccessPoint $accessPoint, string $prefix, string $table, string $field, string $type, ?int $length, mixed $default, ?bool $required, ?string $comment)
    {
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
     * Creates a virtual field
     * @param Logger $logger
     * @param DataAccessPoint $accessPoint
     * @param string $prefix
     * @param string $table
     * @param string $field
     * @param string $type
     * @param int|null $length
     * @param string $expression
     * @param string $comment
     * @throws DataAccessPointsException
     * @return void
     */
    private function _createStorageVirtualField(Logger $logger, DataAccessPoint $accessPoint, string $prefix, string $table, string $field, string $type, ?int $length, ?string $expression, ?string $comment)
    {

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
     * Alters a storage field
     * @param Logger $logger
     * @param DataAccessPoint $accessPoint
     * @param string $prefix
     * @param string $table
     * @param string $field
     * @param string $type
     * @param int|null $length
     * @param mixed $default
     * @param bool $required
     * @param string $comment
     * @throws DataAccessPointsException
     * @return void
     */
    private function _alterStorageField(Logger $logger, DataAccessPoint $accessPoint, string $prefix, string $table, string $field, string $type, ?int $length, mixed $default, bool $required, ?string $comment)
    {

        [$required, $length, $default] = $this->_updateDefaultAndLength($field, $type, $required, $length, $default);

        $res = $accessPoint->Query('
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
     * Alters a virtual field
     * @param Logger $logger
     * @param DataAccessPoint $accessPoint
     * @param string $prefix
     * @param string $table
     * @param array $field
     * @param string $type
     * @param int $length
     * @param string $expression
     * @param string $comment
     * @throws DataAccessPointsException
     * @return void
     */
    private function _alterStorageVirtualField(Logger $logger, DataAccessPoint $accessPoint, string $prefix, string $table, string $field, string $type, ?int $length, string $expression, string $comment)
    {

        $res = $accessPoint->Query('
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
     * Creates a storage table
     * @param Logger $logger
     * @param mixed $accessPoint
     * @param string $prefix
     * @param string $table
     * @param string $indexName
     * @param array $fields
     * @param string $type
     * @param string $method
     * @throws DataAccessPointsException
     * @return void
     */
    private function _createStorageIndex(Logger $logger, $accessPoint, string $prefix, string $table, string $indexName, array $fields, string $type, ?string $method)
    {
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
     * Alters the storage table
     * @param Logger $logger
     * @param DataAccessPoint $accessPoint
     * @param string $prefix
     * @param string $table
     * @param string $indexName
     * @param array $fields
     * @param string $type
     * @param string $method
     * @throws DataAccessPointsException
     * @return void
     */
    private function _alterStorageIndex(Logger $logger, DataAccessPoint $accessPoint, string $prefix, string $table, string $indexName, array $fields, string $type, ?string $method)
    {

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

    public function Exists(string $name, ?string $module = null): bool
    {
        return isset($this->_storages[$name]);
    }

    /**
     * Загружает хранилище
     *
     * @param string $name
     * @return Storage|null
     */
    public function Load(string $name, ?string $module = null): ? Storage
    {
        if (!isset($this->_storages[$name])) {
            return null;
        }
        return new Storage($this->_storages[$name], $name);
    }


    /**
     * Возвращает массив всех хранилищ
     * @return array<Storage> список хранлищ
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
     * Getter
     * @param string $prop
     * @return mixed
     */
    public function __get($prop)
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