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
use Colibri\Helpers\Date;
use Colibri\Helpers\Variable;
use Colibri\IO\FileSystem\Directory;
use Colibri\IO\FileSystem\File;
use Colibri\Modules\Module;
use Colibri\Utils\Config\Config;
use Colibri\Utils\Config\ConfigException;
use Colibri\Utils\Debug;
use Colibri\Xml\XmlNode;
use Colibri\Data\DataAccessPointsException;

/**
 * Класс список хранилищ
 * @author Vahan P. Grigoryan
 * @package Colibri\Data\Storages
 */
class Storages
{

    private static $instance;

    /**
     * Данные о хранилищах
     * @var array
     */
    private ?array $_storages = null;

    /**
     * Набор типов данных
     */
    private ?array $_types = null;

    /**
     * Конструктор
     * @return void
     */
    public function __construct()
    {

        $this->_types = [];
        try {
            $storagesConfig = App::$config->Query('databases.storages');
            $this->_storages = $storagesConfig->AsArray();
            foreach($this->_storages as $name => $storage) {
                if($name === '__global_types') {
                    $this->_types = VariableHelper::Extend($this->_types, $this->_storages[$name]);
                    unset($this->_storages[$name]);
                }
                else {
                    $this->_storages[$name]['name'] = $name;
                    $this->_storages[$name]['path'] = $storagesConfig->GetFile();
                }
            }    
        }
        catch(ConfigException $e) {
            $this->_storages = [];
        }
        
        // Собираем хранилища из модулей
        try {
            $modules = App::$config->Query('modules.entries');
        }
        catch(ConfigException $e) {
            $modules = [];
        }
        
        foreach($modules as $moduleConfig) {
            /** @var Config $moduleConfig */
            try {
                
                $keysArray = $moduleConfig->Query('for', [])->ToArray();
                if(!in_array(App::$domainKey, $keysArray)) {
                    continue;
                }
                
                $config = $moduleConfig->Query('config.databases.storages');
                $storagesConfig = $config->AsArray();
                foreach($storagesConfig as $name => $storage) {
                    if($name === '__global_types') {
                        $this->_types = VariableHelper::Extend($this->_types, $storagesConfig[$name]);
                        unset($storagesConfig[$name]);
                    }
                    else {
                        $storagesConfig[$name]['name'] = $name;
                        $storagesConfig[$name]['file'] = $config->GetFile();
                    }
                }
                $this->_storages = array_merge($this->_storages, $storagesConfig);
            }
            catch(ConfigException $e) {

            }
        }

        $this->_storages = $this->_replaceTypes($this->_storages);


    }

    private function _fromShortString(string $field): array {
        $parts = explode(',', $field);
        $type = trim($parts[0] ?? 'varchar');
        $class = trim($parts[1] ?? 'string');
        $component = trim($parts[2] ?? 'Text');
        $desc = trim($parts[3] ?? '');
        $note = trim($parts[4] ?? '');
        $default = trim($parts[5] ?? '');
        $length = null;

        if(strstr($type, '(') !== false) {
            $type = trim($type, ')');
            $type = explode('(', $type);
            $length = (int)$type[1];
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
        if($length) {
            $return['length'] = $length;
        }

        return $return;
    }

    private function _replaceTypes($fields): array {

        foreach($fields as $name => $field) {

            if(is_string($field)) {
                // короткая запись
                // type(length?), class, component
                $field = $this->_fromShortString($field);
                $fields[$name] = $field;
            }
            
            if(isset($field['inherit']) && $this->_types[$field['inherit']]) {
                $inherit = $field['inherit'];
                unset($field['inherit']);
                $fields[$name] = VariableHelper::Extend($this->_types[$inherit], $field, true);
                $field = $fields[$name];
            }

            if(isset($field['fields'])) {
                $fields[$name]['fields'] = $this->_replaceTypes($field['fields']);
            }

            
        }

        return $fields;
    } 

    /**
     * Статический конструктор
     * @return Storages 
     */
    public static function Create() {
        if(!self::$instance) {
            self::$instance = new Storages();
        }
        return self::$instance;
    }

    #region "Checking"

    /**
     * проверяет все ли правильно в базе данных
     * @return void
     */
    public function Migrate()
    {
        foreach ($this->_storages as $name => $xstorage) {

            if (!$xstorage['access-point']) {
                continue;
            }

            $dtp = App::$dataAccessPoints->Get($xstorage['access-point']);
            $reader = $dtp->Query('SHOW TABLES LIKE \'' . $name . '\'');
            if ($reader->count == 0) {
                $this->_createStorageTable($dtp, $name);
            }

            // проверяем наличие и типы полей, и если отличаются пересоздаем
            $ofields = array();
            $reader = $dtp->Query('SHOW COLUMNS FROM `' . $name . '`');
            while ($ofield = $reader->Read()) {
                $ofields[$ofield->Field] = $ofield;
            }

            $indexesReader = $dtp->Query('SHOW INDEX FROM ' . $name);
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

            $xfields = $xstorage['fields'] ?? [];
            foreach ($xfields as $fieldName => $xfield) {
                $fname = $name . '_' . $fieldName;
                $fparams = $xfield['params'] ?? [];

                if($xfield['type'] == 'enum') {
                    $xfield['type'] .= isset($xfield['values']) && $xfield['values'] ? '('.implode(',', array_map(function($v) { return '\''.$v['value'].'\''; }, $xfield['values'])).')' : '';
                }

                if (!isset($ofields[$fname])) {
                    $this->_createStorageField($dtp, $name, $fieldName, $xfield['type'], isset($xfield['length']) ? $xfield['length'] : null, isset($xfield['default']) ? $xfield['default'] : null, isset($fparams['required']) ? $fparams['required'] : false, isset($xfield['desc']) ? $xfield['desc'] : '');
                } else {
                    // проверить на соответствие
                    $ofield = $ofields[$fname];

                    $required = isset($fparams['required']) ? $fparams['required'] : false;
                    $default = isset($xfield['default']) ? $xfield['default'] : null;
                    [,$length,] = $this->_updateDefaultAndLength($fieldName,  $xfield['type'], $required, $xfield['length'] ?? null, $default);

                    $orType = $ofield->Type != $xfield['type'] . ($length ? '(' . $length . ')' : '');
                    $orDefault = $ofield->Default != $default;
                    $orRequired = $required != ($ofield->Null == 'NO');

                    if ($orType || $orDefault || $orRequired) {
                        $this->_alterStorageField($dtp, $name, $fieldName, $xfield['type'], isset($xfield['length']) ? $xfield['length'] : null, $default, $required, isset($xfield['desc']) ? $xfield['desc'] : '');
                    }
                }
            }

            $xindexes = isset($xstorage['indices']) ? $xstorage['indices'] : [];
            foreach ($xindexes as $indexName => $xindex) {
                if (!isset($indices[$indexName])) {
                    $this->_createStorageIndex($dtp, $name, $indexName, $xindex['fields'], $xindex['type'], $xindex['method']);
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
                        $this->_alterStorageIndex($dtp, $name, $indexName, $xindex['fields'], $xtype, $xmethod);
                    }
                }
            }
        }
    }

    /**
     * Создает таблицу в базе данных соответсвующую хранилищу
     * @param DataAccessPoint $accessPoint точка доступа
     * @param string $table название таблицы
     * @param bool $levels да, если это дерево
     * @return void
     */
    private function _createStorageTable($accessPoint, $table, $levels = false)
    {
        $accessPoint->Query('
            create table `' . $table . '`(
                `' . $table . '_id` bigint unsigned auto_increment, 
                `' . $table . '_datecreated` timestamp not null default CURRENT_TIMESTAMP, 
                `' . $table . '_datemodified` timestamp not null default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP, 
                primary key ' . $table . '_primary (`' . $table . '_id`), 
                key `' . $table . '_datecreated_idx` (`' . $table . '_datecreated`),
                key `' . $table . '_datemodified_idx` (`' . $table . '_datemodified`)
            ) DEFAULT CHARSET=utf8', ['type' => DataAccessPoint::QueryTypeNonInfo]);
    }

    private function _updateDefaultAndLength($field, $type, $required, $length, $default): array
    {

        if (\is_bool($default)) {
            $default = $default ? 'TRUE' : 'FALSE';
        }

        if($type == 'json') {
            $default = $default ? '('.$default.')' : null;
            $required = false;
        }
        else if(strstr($type, 'enum') !== false) {
            $default = $default ? "'".$default."'" : null;
        }

        if($type == 'varchar' && !$length) {
            $length = 255;
        }

        return [$required, $length, $default];

    }
    /**
     * Создает поле в таблице
     * @param DataAccessPoint $accessPoint точка доступа
     * @param string $table таблица
     * @param string $field поле
     * @param string $type тип
     * @param mixed $default значение по умолчанию
     * @param bool $indexed индексировать
     * @param bool $required обязательное
     * @return void
     */
    private function _createStorageField($accessPoint, $table, $field, $type, $length, $default, $required, $comment)
    {
        [$required, $length, $default] = $this->_updateDefaultAndLength($field, $type, $required, $length, $default);

        // ! специфика UUID нужно выключить параметр sql_log_bin
        $sqlLogBinVal = 0;
        if(strstr($default, 'UUID') !== false) {
            $reader = $accessPoint->Query('SELECT @@sql_log_bin as val');
            $sqlLogBinVal = $reader->Read()->val;
            if($sqlLogBinVal == 1) {
                $accessPoint->Query('set sql_log_bin=0', ['type' => DataAccessPoint::QueryTypeNonInfo]);
            }
        }

        $res = $accessPoint->Query('
            ALTER TABLE `' . $table . '` 
            ADD COLUMN `' . $table . '_' . $field . '` ' . $type . ($length ? '(' . $length . ')' : '') . ($required ? ' NOT NULL' : ' NULL') . ' 
            ' . ($default ? 'DEFAULT ' . $default . ' ' : '') . ($comment ? ' COMMENT \'' . $comment . '\'' : ''), ['type' => DataAccessPoint::QueryTypeNonInfo]);
            
        if($sqlLogBinVal == 1) {
            $accessPoint->Query('set sql_log_bin=1', ['type' => DataAccessPoint::QueryTypeNonInfo]);
        }
        
        if($res->error) {
            App::$log->debug('Can not save field: ' . $res->query);
            throw new DataAccessPointsException('Can not save field: ' . $res->query);
        }
    }


    /**
     * Обновляет поле
     * @param DataAccessPoint $accessPoint точка доступа
     * @param string $table таблица
     * @param string $field поле
     * @param string $type тип
     * @param mixed $default значение по умолчанию
     * @param bool $indexed индексировать
     * @param bool $required обязательное
     * @return void
     */
    private function _alterStorageField($accessPoint, $table, $field, $type, $length, $default, $required, $comment)
    {

        [$required, $length, $default] = $this->_updateDefaultAndLength($field, $type, $required, $length, $default);
        
        
        $res = $accessPoint->Query('
            ALTER TABLE `' . $table . '` 
            MODIFY COLUMN `' . $table . '_' . $field . '` ' . $type . ($length ? '(' . $length . ')' : '') . ($required ? ' NOT NULL' : ' NULL') . ' 
            ' . (!is_null($default) ? 'DEFAULT ' . $default . ' ' : '') . ($comment ? 'COMMENT \'' . $comment . '\'' : ''),
            ['type' => DataAccessPoint::QueryTypeNonInfo]
        );
        if($res->error) {
            App::$log->debug('Can not save field: ' . $res->query);
            throw new DataAccessPointsException('Can not save field: ' . $res->query);
        }

    }

    /**
     * Создает индекс
     * @param DataAccessPoint $accessPoint точка доступа
     * @param string $table таблица
     * @param string $indexName наименование индекса
     * @param string[] $fields наименование полей индекса
     * @param string $type тип (NORMAL, UNIQUE, FULLTEXT)
     * @param string $method метод (BTREE, HASH)
     * @return void 
     */
    private function _createStorageIndex($accessPoint, $table, $indexName, $fields, $type, $method)
    {
        $accessPoint->Query('
            ALTER TABLE `' . $table . '` 
            ADD' . ($type !== 'NORMAL' ? ' ' . $type : '') . ' INDEX `' . $indexName . '` (`' . $table . '_' . implode('`,`' . $table . '_', $fields) . '`) ' . ($method ? ' USING ' . $method : '') . '
        ', ['type' => DataAccessPoint::QueryTypeNonInfo]);
    }

    /**
     * Обновляет индекс
     * @param DataAccessPoint $accessPoint точка доступа
     * @param string $table таблица
     * @param string $indexName наименование индекса
     * @param string[] $fields наименование полей индекса
     * @param string $type тип (NORMAL, UNIQUE, FULLTEXT)
     * @param string $method метод (BTREE, HASH)
     * @return void 
     */
    private function _alterStorageIndex($accessPoint, $table, $indexName, $fields, $type, $method)
    {

        $accessPoint->Query('
            ALTER TABLE `' . $table . '` 
            DROP INDEX `' . $indexName . '`
        ', ['type' => DataAccessPoint::QueryTypeNonInfo]);

        $accessPoint->Query('
            ALTER TABLE `' . $table . '` 
            ADD' . ($type !== 'NORMAL' ? ' ' . $type : '') . ' INDEX `' . $indexName . '` (`' . $table . '_' . implode('`,`' . $table . '_', $fields) . '`) ' . ($method ? ' USING ' . $method : '') . '
        ', ['type' => DataAccessPoint::QueryTypeNonInfo]);
    }

    /**
     * Удаляет таблицу из базы данных
     * @param DataAccessPoint $accessPoint точка доступа
     * @param string $table таблица
     * @return void
     */
    private function _dropStorageTable($accessPoint, $table)
    {
        // $accessPoint->Query('drop table `'.$table.'`', ['type' => DataAccessPoint::QueryTypeNonInfo])
        $accessPoint->Query('rename table `' . $table . '` to `_' . $table . '_backup_' . strftime('%Y%m%d%H%M%S', time()) . '`', ['type' => DataAccessPoint::QueryTypeNonInfo]);
    }

    #endregion

    public function Exists($name)
    {
        return isset($this->_storages[$name]);
    }

    /**
     * Загружает хранилище
     *
     * @param string $name
     * @return Storage|null
     */
    public function Load($name): ?Storage
    {
        if(!isset($this->_storages[$name])) {
            return null;
        }
        return new Storage($this->_storages[$name], $name);
    }


    /**
     * Возвращает массив всех хранилищ
     * @return Storage[string] список хранлищ
     */
    public function GetStorages()
    {
        $storages = [];
        foreach ($this->_storages as $xstorage) {
            $storage = new Storage($xstorage);
            $storages[$storage->name] = $storage;
        }
        return $storages;
    }

    private function _loadStoragesNamePathMap(): array
    {
        $storagesConfigs = [];
        $configs = Config::Enumerate();
        foreach($configs as $config) {

            $config = Config::LoadFile($config);
            // ищем файлы в которых настроены хранилища
            try {
                $databasesConfig = $config->Query('databases');
            }
            catch(ConfigException $e) {
                continue;
            }

            $databasesArray = $databasesConfig->AsArray();
            if(!isset($databasesArray['storages'])) {
                continue;
            }

            if(strstr($databasesArray['storages'], 'include(') !== false) {
                // нашли
                $storagesConfigs[] = str_replace(')', '', str_replace('include(', '', $databasesArray['storages']));
            }

        }

        $storagesNamePathMap = [];
        foreach($storagesConfigs as $storagesConfig) {
            $sc = Config::LoadFile($storagesConfig)->AsArray();
            foreach($sc as $name => $config) {
                $storagesNamePathMap[$name] = $storagesConfig;
            }
        }

        return $storagesNamePathMap;
    }

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
