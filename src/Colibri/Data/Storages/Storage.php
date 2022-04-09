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
        $this->_name = $name ?: $xstorage['name'];
        $this->_xstorage = $xstorage;
        $this->_dataPoint = isset($xstorage['access-point']) ?App::$dataAccessPoints->Get($xstorage['access-point']) : null;
        $this->_loadFields();
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
            $found = $fields->$field;
            $fields = $found->fields->$field ?? null;
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
        $return = $this->_xstorage;
        $module = $this->GetModule();
        if($module) {
            $config = $module->Config();
            $moduleDesc = $config->Query('desc')->GetValue();
            $moduleName = $config->Query('name')->GetValue();
            $return['module'] = ['desc' => $moduleDesc, 'name' => $moduleName];
        }
        return $return;
    }

}
