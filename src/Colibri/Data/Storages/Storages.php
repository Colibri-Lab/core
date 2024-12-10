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
        $logger->info('Starting migration process');

        try {

            foreach ($this->_storages as $name => $xstorage) {

                $logger->info($name . ': Migrating storage');
                if (!$xstorage['access-point']) {
                    $logger->error($name . ': No access point found');
                    continue;
                }

                $dtp = App::$dataAccessPoints->Get($xstorage['access-point']);
                $dtp->Migrate($logger, $name, $xstorage);

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
