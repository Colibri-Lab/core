<?php

/**
 * Storages
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\Storages
 */

namespace Colibri\Data\Storages;

use Colibri\Data\SqlClient\IDataReader;
use Colibri\Data\Storages\Models\DataTable;
use Colibri\Data\Storages\Fields\Field;
use Colibri\App;
use Colibri\AppException;
use Colibri\Common\StringHelper;
use Colibri\Common\VariableHelper;
use Colibri\Data\DataAccessPoint;
use Colibri\Data\Storages\Models\DataRow;
use Colibri\Modules\Module;
use Colibri\Utils\Config\Config;

/**
 * Storage class
 *
 * This class represents a storage configuration
 *
 * @property-read string $name The name of the storage.
 * @property-read string $table The name of the database table associated with the storage.
 * @property-read boolean $levels Indicates whether the storage supports levels.
 * @property-read array $settings The settings array containing various configuration options.
 * @property-read object $fields The list of fields associated with the storage.
 * @property-read DataAccessPoint $accessPoint The DataAccessPoint object associated with the storage.
 * @property-read bool $isSoftDelete Indicates whether the rows is not deleted but marked as deleted
 * @property-read bool $isShowDeletedRows Indicates whether the deleted rows must be shown or not
 * 
 */
class Storage
{
    /**
     * Internal storage data
     * @var array
     */
    private array|object $_xstorage;

    /**
     * Data access point
     * @var DataAccessPoint|null
     */
    private ?DataAccessPoint $_dataPoint;

    /**
     * Fields list
     * @var object
     */
    private object $_fields;

    /**
     * Storage Name
     * @var string
     */
    private ?string $_name;

    /**
     * Constructs a new Storage object.
     *
     * @param array|object $xstorage The data from the storage settings.
     * @param string|null $name The name of the storage.
     */
    public function __construct(array|object $xstorage, ?string $name = null)
    {
        $xstorage = (array) $xstorage;
        $this->_xstorage = $xstorage;
        $this->_name = $name;
        $this->_init();
    }

    /**
     * Initializes the storage object.
     * This method sets up the name, data access point, and loads fields for the storage.
     *
     * @return void
     */
    private function _init()
    {
        if (isset($this->_xstorage['name'])) {
            $this->_name = $this->_xstorage['name'];
        }
        $this->_dataPoint = isset($this->_xstorage['access-point']) ? App::$dataAccessPoints->Get($this->_xstorage['access-point']) : null;
        $this->_loadFields();
    }

    /**
     * Creates a new instance of the Storage class.
     *
     * This method is a factory method used to create a new Storage object.
     * It allows creating a Storage object with the provided module, name, and data.
     *
     * ```
     * For example
     *
     * $storage = Storage::Create(App::$moduleManager->{'lang'}, 'langs');
     *
     * its equivalent of
     *
     * $storage = Storages::Create()->Load('langs', 'lang');
     *
     * @example
     * ```
     *
     * @param Module $module The module instance.
     * @param string $name The name of the storage.
     * @param array $data Additional data for configuring the storage.
     * @return Storage The newly created Storage object.
     */
    public static function Create(Module $module, string $name = '', array $data = []): self
    {
        $data['file'] = $module->moduleStoragesPath;
        return new Storage($data, $name);
    }

    /**
     * Getter method.
     *
     * Retrieves the value of the specified property.
     *
     * @param string $prop The name of the property to retrieve.
     * @return mixed The value of the specified property.
     */
    public function __get($prop)
    {
        $return = null;
        $prop = strtolower($prop);
        switch ($prop) {
            default:
                $return = isset($this->_xstorage[$prop]) ? $this->_xstorage[$prop] : null;
                break;
            case 'issoftdelete':
                $return = !isset($this->_xstorage['params']['softdeletes']) ? false :
                    (bool)$this->_xstorage['params']['softdeletes'];
                break;
            case 'isshowdeletedrows':
                $return = !isset($this->_xstorage['params']['deletedautoshow']) ? false :
                    (bool)$this->_xstorage['params']['deletedautoshow'];
                break;
            case 'settings':
                $return = $this->_xstorage;
                break;
            case 'fields':
                $return = $this->_fields;
                break;
            case 'accesspoint':
                $return = $this->_dataPoint;
                break;
            case 'name':
                $return = $this->_name;
                break;
            case 'table':
                $return = (isset($this->_xstorage['prefix']) ? $this->_xstorage['prefix'] . '_' : '') . $this->_name;
                break;
        }
        return $return;
    }

    /**
     * Setter method.
     *
     * Sets the value of the specified property.
     *
     * @param string $prop The name of the property to set.
     * @param mixed $value The value to set.
     * @return void
     */
    public function __set(string $prop, mixed $value): void
    {
        $this->_xstorage[$prop] = $value;
        $this->_init();
    }


    /**
     * Loads fields into an array.
     *
     * This method initializes the fields of the storage object from the stored configuration.
     *
     * @return void
     */
    private function _loadFields()
    {
        $xfields = $this->_xstorage['fields'] ?? [];
        $this->_fields = (object) array();
        foreach ($xfields as $name => $xfield) {
            $xfield['name'] = $name;
            $this->_fields->$name = new Field($xfield, $this);
        }
    }

    /**
     * Updates a field in the storage configuration.
     *
     * This method updates the configuration of a specific field in the storage object.
     *
     * @param Field $field The field object to update.
     * @return void
     */
    public function UpdateField(Field $field)
    {
        $this->_xstorage['fields'][$field->{'name'}] = $field->ToArray();
    }

    /**
     * Retrieves the model classes for the storage.
     *
     * @return array An array containing the table class and row class.
     */
    public function GetModelClasses()
    {
        $tableClass = DataTable::class;
        $rowClass = DataRow::class;
        $module = isset($this->_xstorage['module']) ? $this->_xstorage['module'] : null;
        $rootNamespace = '';
        if ($module) {
            $module = StringHelper::ToLower($module);
            if (!App::$moduleManager->$module) {
                throw new AppException('Unknown module in storage configuration ' . $module);
            }
            $rootNamespace = App::$moduleManager->$module->moduleNamespace;
        }

        if (isset($this->_xstorage['models']) && isset($this->_xstorage['models']['table'])) {
            $tableClassName = $this->_xstorage['models']['table'];
            if(strstr($tableClassName, 'Models\\') === false) {
                $tableClassName = 'Models\\' . $tableClassName;
            }
            if(class_exists($rootNamespace . $tableClassName)) {
                $tableClass = $rootNamespace . $tableClassName;
            }
        }

        if (isset($this->_xstorage['models']) && isset($this->_xstorage['models']['row'])) {
            $rowClassName = $this->_xstorage['models']['row'];
            if(strstr($rowClassName, 'Models\\') === false) {
                $rowClassName = 'Models\\' . $rowClassName;
            }
            if(class_exists($rootNamespace . $this->_xstorage['models']['row'])) {
                $rowClass = $rootNamespace . $this->_xstorage['models']['row'];
            }
        }
        return [$tableClass, $rowClass];
    }

    /**
     * Retrieves the class of a specific field.
     *
     * @param Field $field The field object.
     * @return string The class name of the field.
     * @throws AppException If the class of the field is unknown.
     */
    public function GetFieldClass(Field $field)
    {
        $rootNamespace = '';
        $module = isset($this->settings['module']) ? $this->settings['module'] : null;
        if ($module) {
            $module = StringHelper::ToLower($module);
            if (!App::$moduleManager->$module) {
                throw new AppException('Unknown module in storage configuration ' . $module);
            }
            $rootNamespace = App::$moduleManager->$module->moduleNamespace;
        }

        if (class_exists($field->{'class'})) {
            return $field->{'class'};
        } elseif (class_exists('Colibri\\Data\\Storages\\Fields\\' . $field->{'class'})) {
            return 'Colibri\\Data\\Storages\\Fields\\' . $field->{'class'};
        } elseif (class_exists($rootNamespace . 'Models\\Fields\\' . $field->{'class'})) {
            return $rootNamespace . 'Models\\Fields\\' . $field->{'class'};
        } elseif (class_exists($rootNamespace . 'Models\\' . $field->{'class'})) {
            return $rootNamespace . 'Models\\' . $field->{'class'};
        } else {
            throw new AppException('Unknown class: ' . $field->{'class'});
        }

    }

    /**
     * Retrieves the real field name with the table prefix.
     *
     * @param string $name The name of the field without the prefix.
     * @return string The name of the field with the prefix.
     */
    public function GetRealFieldName($name)
    {
        if($this->_dataPoint->fieldsHasPrefix) {
            return $this->name . '_' . $name;
        } else {
            return $name;
        }
    }

    /**
     * Retrieves the field name without the table prefix.
     *
     * @param string $name The name of the field with the prefix.
     * @return string The name of the field without the prefix.
     */
    public function GetFieldName($name)
    {
        if($this->_dataPoint->fieldsHasPrefix) {
            return str_replace($this->name . '_', '', $name);
        } else {
            return $name;
        }
    }

    /**
     * Retrieves a field object based on the specified path like field1/field2/field3 if field1 and field2 is of type json
     *
     * @param string $path The path to the field (e.g., 'fieldname/subfieldname').
     * @return Field|null The field object, or null if not found.
     */
    public function GetField($path)
    {
        $found = null;
        $fields = $this->fields;
        $path = explode('/', $path);
        foreach ($path as $field) {
            $found = $fields->$field ?? null;
            if (!$found) {
                return null;
            }
            $fields = $found->fields ?? [];
        }
        return $found;
    }

    /**
     * Retrieves templates for displaying records from the storage.
     *
     * @return object|array The templates data.
     */
    public function GetTemplates()
    {
        return $this->_xstorage['templates'] ?? [];
    }

    /**
     * Retrieves the module associated with the storage.
     *
     * @return Module|null The module object, or null if not found.
     */
    public function GetModule(): ?Module
    {
        $module = isset($this->_xstorage['module']) ? $this->_xstorage['module'] : null;
        if (!$module) {
            return null;
        }
        return App::$moduleManager->$module;
    }

    /**
     * Converts the storage settings to an array.
     *
     * @param bool $changeLang Whether to change the language or not.
     * @return array The storage settings as an array.
     */
    public function ToArray($changeLang = true)
    {
        return $this->_xstorage;
    }

    /**
     * Saves the storage settings to a file.
     *
     * @param bool $performValidationBeforeSave this parameter is dummy
     * @return void
     */
    public function Save(bool $performValidationBeforeSave = false)
    {
        $file = $this->{'file'};
        $storageData = $this->ToArray();
        unset($storageData['name']);
        unset($storageData['file']);
        unset($storageData['prefix']);

        if (isset($storageData['components'])) {
            if (!$storageData['components']['default']) {
                unset($storageData['components']['default']);
            }
            if (!$storageData['components']['list']) {
                unset($storageData['components']['list']);
            }
            if (!$storageData['components']['item']) {
                unset($storageData['components']['item']);
            }
            if (empty($storageData['components'])) {
                unset($storageData['components']);
            }
        }

        if (isset($storageData['templates'])) {
            if (!$storageData['templates']['default']) {
                unset($storageData['templates']['default']);
            }
            if (!$storageData['templates']['list']) {
                unset($storageData['templates']['list']);
            }
            if (!$storageData['templates']['item']) {
                unset($storageData['templates']['item']);
            }
            if (empty($storageData['templates'])) {
                unset($storageData['templates']);
            }
        }

        $storageData['fields'] = !isset($storageData['fields']) ? [] : $storageData['fields'];
        foreach ($this->_fields as $fname => $field) {
            $storageData['fields'][$fname] = $field->Save();
        }

        $config = Config::LoadFile($file);
        $config->Set($this->name, $storageData);
        $config->Save();

    }

    /**
     * Deletes the storage settings from config of module or application.
     *
     * @return void
     */
    public function Delete(): void
    {
        $file = $this->{'file'};
        $config = Config::LoadFile($file);
        $config->Set($this->name, null);
        $config->Save();
    }

    /**
     * Adds a new field to the storage.
     *
     * @param string $path The path to the field.
     * @param array $data The data for the new field.
     * @return Field The newly created field object.
     */
    public function AddField($path, $data)
    {
        $path = explode('/', $path);
        unset($path[count($path) - 1]);
        $path = implode('/', $path);
        if (!$path) {
            $name = $data['name'];
            $this->_xstorage['fields'][$name] = ['name' => $name];
            $this->_fields->$name = new Field($this->_xstorage['fields'][$name], $this);
            $this->_fields->$name->UpdateData($data);
            return $this->_fields->$name;
        } else {
            $parentField = $this->GetField($path);
            $field = $parentField->AddField($data['name'], ['name' => $data['name']]);
            $field->UpdateData($data);
        }
        return $field;
    }

    /**
     * Deletes a field from the storage.
     *
     * @param string $path The path to the field.
     * @return void
     */
    public function DeleteField($path)
    {
        $field = $this->GetField($path);
        $path = explode('/', $path);
        unset($path[count($path) - 1]);
        $parentPath = implode('/', $path);
        if (!$parentPath) {
            unset($this->_xstorage['fields'][$field->{'name'}]);
            unset($this->_fields->{$field->{'name'}});
        } else {
            $parentField = $this->GetField($parentPath);
            $parentField->DeleteField($field->{'name'});
        }
    }

    /**
     * Adds an index to the storage.
     *
     * @param string $name The name of the index.
     * @param array $data The index data.
     * @return void
     */
    public function AddIndex($name, $data)
    {
        if (!isset($this->_xstorage['indices'])) {
            $this->_xstorage['indices'] = [];
        }
        $this->_xstorage['indices'][$name] = $data;
    }

    /**
     * Deletes an index from the storage.
     *
     * @param string $name The name of the index.
     * @return void
     */
    public function DeleteIndex($name)
    {
        if (isset($this->_xstorage['indices'][$name])) {
            unset($this->_xstorage['indices'][$name]);
        }
    }

    /**
     * Moves a field within the storage.
     *
     * @param Field $field The field to move.
     * @param Field $relative The relative field.
     * @param string $sibling The sibling position ('before' or 'after').
     * @return void
     */
    public function MoveField($field, $relative, $sibling)
    {

        // перемещает во внутреннем массиве
        $xfields = $this->_xstorage['fields'];
        if (!isset($xfields[$field->name])) {
            return;
        }

        $newxFields = [];
        $xfieldMove = $xfields[$field->name];
        foreach ($xfields as $name => $xfield) {
            if ($name != $field->name) {

                if ($name == $relative->name && $sibling === 'before') {
                    $newxFields[$field->name] = $xfieldMove;
                }
                $newxFields[$name] = $xfield;
                if ($name == $relative->name && $sibling === 'after') {
                    $newxFields[$field->name] = $xfieldMove;
                }

            }
        }

        $this->_xstorage['fields'] = $newxFields;
        $this->_init();

    }

    /**
     * Retrieves the status of the storage.
     *
     * @return object The storage status.
     */
    public function GetStatus(): object
    {
        if($this->accessPoint->dbms === DataAccessPoint::DBMSTypeRelational) {
            $reader = $this->accessPoint->Status($this->table);
            if($reader instanceof IDataReader) {
                return $reader->Read();
            } else {
                return (object)[
                    'Name' => $this->table,
                    'Engine' => 'NoSql', 
                    'Version' => 0, 
                    'Row_Format' => 'Dynamic', 
                    'Rows' => 0, 
                    'Avg_row_length' => 0, 
                    'Data_length' => 0, 
                    'Max_data_length' => 0, 
                    'Index_length' => 0, 
                    'Data_free' => 0, 
                    'Auto_increment' => 0, 
                    'Create_time' => 0, 
                    'Update_time' => null, 
                    'Check_time' => 0,
                    'Collection' => 'utf8mb3_general_ci',
                    'Checksum' => null, 
                    'Create_options' => null,
                    'Comment' => null 
                ];
    
            }
        } else {
            return (object)[
                'Name' => $this->table,
                'Engine' => 'NoSql', 
                'Version' => 0, 
                'Row_Format' => 'Dynamic', 
                'Rows' => 0, 
                'Avg_row_length' => 0, 
                'Data_length' => 0, 
                'Max_data_length' => 0, 
                'Index_length' => 0, 
                'Data_free' => 0, 
                'Auto_increment' => 0, 
                'Create_time' => 0, 
                'Update_time' => null, 
                'Check_time' => 0,
                'Collection' => 'utf8mb3_general_ci',
                'Checksum' => null, 
                'Create_options' => null,
                'Comment' => null 
            ];
        }
    }

    /**
     * Disables keys for the storage table.
     *
     * @deprecated
     * @return void
     */
    public function DisableKeys()
    {
        if($this->accessPoint->dbms === DataAccessPoint::DBMSTypeRelational) {
            $this->accessPoint->Query('ALTER TABLE '.$this->table.' DISABLE KEYS');
        }
    }

    /**
     * Enables keys for the storage table.
     *
     * @deprecated
     * @return void
     */
    public function EnableKeys()
    {
        if($this->accessPoint->dbms === DataAccessPoint::DBMSTypeRelational) {
            $this->accessPoint->Query('ALTER TABLE '.$this->table.' ENABLE KEYS');
        }
    }

    public function RecurseFields(\Closure $closure, ?object $fields = null)
    {
        foreach(($fields ?: $this->_fields) as $field) {
            $closure->call($this, $field);
            if($field->fields) {
                $this->RecurseFields($closure, $field->fields);
            }
        }
    }

}
