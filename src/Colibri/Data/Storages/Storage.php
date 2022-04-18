<?php

/**
 * Structure
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Data\Storages
 */

namespace Colibri\Data\Storages;

use Colibri\Data\Storages\Models\DataTable;
use Colibri\Data\Storages\Fields\Field;
use Colibri\App;
use Colibri\AppException;
use Colibri\Common\StringHelper;
use Colibri\Data\DataAccessPoint;
use Colibri\Data\Storages\Models\DataRow;
use Colibri\Utils\Debug;
use Colibri\Xml\XmlNode;
use Colibri\Modules\Module;
use Colibri\Utils\Config\Config;

/**
 * Класс Хранилище
 *
 * @property-read boolean $levels
 * @property-read array $settings
 * @property-read object $fields
 * @property-read DataAccessPoint $accessPoint
 * @property-read string $name
 */
class Storage
{
    /**
     * Данные хранилища
     * @var array
     */
    private $_xstorage;

    /**
     * Точка доступа
     * @var DataAccessPoint|null
     */
    private $_dataPoint;

    /**
     * Список полей
     * @var object
     */
    private $_fields;

    private $_name;

    /**
     * Конструктор
     * @param array|object $xstorage данные из настроек хранилища
     * @return void
     */
    public function __construct(array|object $xstorage, ?string $name = null)
    {
        $xstorage = (array)$xstorage;
        $this->_xstorage = $xstorage;
        $this->_init();
    }

    private function _init() {
        if(isset($this->_xstorage['name'])) {
            $this->_name = $this->_xstorage['name'];
        }
        $this->_dataPoint = isset($this->_xstorage['access-point']) ?App::$dataAccessPoints->Get($this->_xstorage['access-point']) : null;
        $this->_loadFields();
    }

    public static function Create(Module $module, string $name = '', array $data = []): self
    {
        $data['file'] = $module->moduleStoragesPath;
        return new Storage($data, $name);
    } 

    /**
     * Геттер
     * @param string $prop свойство
     * @return mixed значение
     */
    public function __get($prop)
    {
        $return = null;
        $prop = strtolower($prop);
        switch ($prop) {
            default:
                $return = isset($this->_xstorage[$prop]) ? $this->_xstorage[$prop] : null;
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
        }
        return $return;
    }

    public function __set(string $prop, mixed $value): void
    {
        $this->_xstorage[$prop] = $value;
        $this->_init();
    }


    /**
     * Загружает поля в массив
     * @return void
     */
    private function _loadFields()
    {
        $xfields = $this->_xstorage['fields'];
        $this->_fields = (object)array();
        foreach ($xfields as $name => $xfield) {
            $xfield['name'] = $name;
            $this->_fields->$name = new Field($xfield, $this);
        }
    }

    public function UpdateField(Field $field)
    {
        $this->_xstorage['fields'][$field->name] = $field->ToArray();
    }

    /**
     * Возвращает модели репу и модель
     * @return array 
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
        if (isset($this->_xstorage['models']) && isset($this->_xstorage['models']['table']) && class_exists($rootNamespace . $this->_xstorage['models']['table'])) {
            $tableClass = $rootNamespace . $this->_xstorage['models']['table'];
        }
        if (isset($this->_xstorage['models']) && isset($this->_xstorage['models']['row']) && class_exists($rootNamespace . $this->_xstorage['models']['row'])) {
            $rowClass = $rootNamespace . $this->_xstorage['models']['row'];
        }
        return [$tableClass, $rowClass];
    }

    public function GetFieldClass(Field $field)
    {
        $rootNamespace = '';
        $module = isset($this->settings['module']) ? $this->settings['module'] : null;
        if($module) {
            $module = StringHelper::ToLower($module);
            if(!App::$moduleManager->$module) {
                throw new AppException('Unknown module in storage configuration '.$module);
            }
            $rootNamespace = App::$moduleManager->$module->moduleNamespace;
        }

        if(class_exists($field->class)) {
            return $field->class;
        }
        else if(class_exists('Colibri\\Data\\Storages\\Fields\\' . $field->class)) {
            return 'Colibri\\Data\\Storages\\Fields\\'.$field->class;
        }
        else if(class_exists($rootNamespace.'Models\\Fields\\' . $field->class)) {
            return $rootNamespace.'Models\\Fields\\' . $field->class;
        }
        else {
            throw new AppException('Unknown class: ' . $field->class);
        }
        
    }

    /**
     * Возвращает реальное название поля в базе данных
     * @param string $name название без префикса
     * @return string название с префиксом
     */
    public function GetRealFieldName($name)
    {
        return $this->name . '_' . $name;
    }

    /**
     * Возвращает название поля без префикса таблицы
     * @param string $name полное наименование поля в базе данных
     * @return string название без префикса
     */
    public function GetFieldName($name)
    {
        return str_replace($this->name . '_', '', $name);
    }

    /**
     * Возвращает поле по пути типа fieldname/fieldname
     * @param string $path путь
     * @return Field|null
     */
    public function GetField($path)
    {
        $found = null;
        $fields = $this->fields;
        $path = explode('/', $path);
        foreach ($path as $field) {
            $found = $fields->$field ?? null;
            if(!$found) {
                return null;
            }
            $fields = $found->fields ?? [];
        }
        return $found;
    }

    /**
     * Возвращает обьект содержащий данные для отображения записей хранилища по шаблонам
     * @return object данные: 
     *      содержит 
     *      class - класс шаблона, 
     *      templates - опсания шаблонов, 
     *      templates.default - шаблон по умолчанию, 
     *      templates.files - обьект ключ значение, ключ - название шаблона, значение файл шаблона
     */
    public function GetTemplates()
    {
        $view = $this->_xstorage['view'];
        $templateClass = $view['class'];
        $templates = $view['templates'];
        $default = $templates['default'];
        unset($templates['default']);
        foreach ($templates as $name => $template) {
            $templates[$name] = App::$appRoot . $template;
        }
        return (object)['class' => $templateClass, 'templates' => (object)['default' => App::$appRoot . $default, 'files' => (object)$templates]];
    }

    public function GetModule(): ?Module
    {
        $module = isset($this->_xstorage['module']) ? $this->_xstorage['module'] : null;
        if (!$module) {
            return null;
        }
        return App::$moduleManager->$module;
    }

    /**
     * Возвращает настройки хранилищя в виде 
     * @return array 
     */
    public function ToArray()
    {
        return $this->_xstorage;
    }

    public function Save() 
    {
        $file = $this->file;
        $storageData = $this->ToArray();
        unset($storageData['name']);
        unset($storageData['file']);

        foreach($this->_fields as $fname => $field) {
            $storageData['fields'][$fname] = $field->Save();
        }

        $config = Config::LoadFile($file);
        $config->Set($this->name, $storageData);
        $config->Save();
        
    }

    public function Delete(): void 
    {
        $file = $this->file;
        $config = Config::LoadFile($file);
        $config->Set($this->name, null);
        $config->Save();
    }

    public function AddField($path, $data) {
        $path = explode('/', $path);
        unset($path[count($path) - 1]);
        $path = implode('/', $path);
        if(!$path) {
            $name = $data['name'];
            $this->_xstorage['fields'][$name] = ['name' => $name];
            $this->_fields->$name = new Field($this->_xstorage['fields'][$name], $this);
            $this->_fields->$name->UpdateData($data);
            return $this->_fields->$name;
        }
        else {
            $parentField = $this->GetField($path);
            $field = $parentField->AddField($data['name'], ['name' => $data['name']]);
            $field->UpdateData($data);
        }
        return $field;
    }

    public function DeleteField($path) 
    {
        $field = $this->GetField($path);
        $path = explode('/', $path);
        unset($path[count($path) - 1]);
        $parentPath = implode('/', $path);
        if(!$parentPath) {
            unset($this->_xstorage['fields'][$field->name]);
            unset($this->_fields->{$field->name});
        }
        else {
            $parentField = $this->GetField($parentPath);
            $parentField->DeleteField($field->name);
        }
    }

    public function AddIndex($name, $data) {
        if(!isset($this->_xstorage['indices'])) {
            $this->_xstorage['indices'] = [];
        }
        $this->_xstorage['indices'][$name] = $data;
    }

    public function DeleteIndex($name) {
        if(isset($this->_xstorage['indices'][$name])) {
            unset($this->_xstorage['indices'][$name]);
        }
    }
    

}
