<?php

namespace Colibri\Modules;

use Colibri\App;
use Colibri\AppException;
use Colibri\Collections\Collection;
use Colibri\Events\TEventDispatcher;
use Colibri\Events\EventsContainer;
use Colibri\Utils\Config\Config;
use Colibri\Utils\Config\ConfigException;
use Colibri\Web\Templates\PhpTemplate;

/**
 * Менеджер модулей
 *
 * @property-read Config $settings
 * @property-read Collection $list
 *
 * @testFunction testModuleManager
 */
class ModuleManager
{

    // подключаем функционал событийной модели
    use TEventDispatcher;

    /**
     * Синглтон
     *
     * @var ModuleManager
     */
    public static $instance;

    /**
     * Настройки
     *
     * @var object
     */
    private $_settings;

    /**
     * Список модулей
     *
     * @var Collection
     */
    private $_list;

    public function __construct()
    {
        $this->_list = new Collection();
    }

    /**
     * Статический конструктор
     *
     * @return ModuleManager
     * @testFunction testModuleManagerCreate
     */
    public static function Create(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Инициализация менеджера
     *
     * @return void
     * @testFunction testModuleManagerInitialize
     */
    public function Initialize(): void
    {

        try {
            $this->_settings = App::$config->Query('modules');
            $entities = $this->_settings->Query('entries');
            foreach ($entities as $moduleConfig) {
                if (!$moduleConfig->Query('enabled', true)->GetValue()) {
                    continue;
                }

                $keysArray = $moduleConfig->Query('for', [])->ToArray();
                if (!empty($keysArray) && !in_array(App::$domainKey, $keysArray)) {
                    continue;
                }

                $module = $this->InitModule($moduleConfig);
                if ($module) {
                    $moduleName = $moduleConfig->Query('name')->GetValue();
                    $this->_list->Add($moduleName, $module);
                }
            }

            foreach ($this->_list as $moduleName => $module) {
                App::$monitoring->StartTimer($moduleName);

                $module->InitializeModule();

                App::$monitoring->EndTimer($moduleName);
            }
        } catch (ConfigException $e) {
            App::$log->debug('Modules not found');
        }

        $this->DispatchEvent(EventsContainer::ModuleManagerReady);
    }

    /**
     * Инициализирит модуль
     *
     * @param Config $configNode
     * @return Module|null
     * @testFunction testModuleManagerInitModule
     */
    public function InitModule(Config $configNode): ? Module
    {
        $moduleEntry = $configNode->Query('entry')->GetValue();

        $path = App::$appRoot . $this->_settings->Query('path')->GetValue() . str_replace('\\', '/', $moduleEntry) . '.php';
        if (file_exists($path)) {
            require_once($path);
        }

        $className = '\\App\\Modules' . $moduleEntry;
        if (!class_exists($className)) {
            return null;
        }

        return $className::Create($configNode);

    }

    /**
     * @magic
     * @testFunction testModuleManager__get
     */
    public function __get(string $property): mixed
    {
        $property = strtolower($property);
        switch ($property) {
            case 'settings':
                return $this->_settings;
            case 'list':
                return $this->_list;
            default:
                return $this->_list->$property;
        }
    }

    public function Get(string $moduleName): mixed
    {
        return $this->_list->$moduleName;
    }

    /**
     * Получает конфигурацию модуля
     *
     * @param string $name
     * @return Config
     * @testFunction testModuleManagerConfig
     */
    public function Config(string $name): Config
    {
        return $this->_settings->$name->config;
    }

    /**
     * Список прав модуля, стандартный набор
     *
     * @return array
     */
    public function GetPermissions(): array
    {
        $permissions = [];
        foreach ($this->list as $module) {
            if (is_object($module) && method_exists($module, 'GetPermissions')) {
                $permissions = array_merge($permissions, $module->GetPermissions());
            }
        }
        return $permissions;
    }

    /**
     * Возвращает список реальных путей к модулям дополняя их нужной строкой
     * @param string $extend строка, которую нужно дополнить в конце, например .Bundle/
     * @param array|null $extendArray дополнить массив нужными элементами
     * @return string[]
     */
    public function GetPaths(string $extend = '/', ?array $extendArray = null): array
    {
        $paths = [];
        foreach ($this->list as $module) {
            $p = ['path' => $module->modulePath . $extend];
            if ($extendArray) {
                $p = array_merge($p, $extendArray);
            }
            $paths[] = $p;
        } 
        return $paths;
    }

    public function GetPathsFromModuleConfig(?array $extendArray = null): array
    {
        $paths = [];
        foreach ($this->list as $module) {
            $p = $module->Config()->Query('config.paths.ui', [])->ToArray();
            if(!empty($p)) {
                foreach($p as $path) {
                    if(is_object($path)) {
                        $path = $path->path;
                    } else if(is_array($path)) {
                        $path = $path['path'];
                    }
                    $pp = ['path' => App::$appRoot . $path]; 
                    if($extendArray) {
                        $paths[] = array_merge($pp, $extendArray);
                    } else {
                        $paths[] = $pp;
                    }
                }
            }
        }
        return $paths;
    }

    /**
     * Возвращает список шаблонов, которые нужно запустить
     * @param string $extend строка, которую нужно дополнить в конце, например .Bundle/
     * @param array|null $extendArray дополнить массив нужными элементами
     * @return string[]
     */
    public function GetTemplates(string $templateName = 'index'): array
    {
        $templates = [];
        foreach ($this->list as $module) {
            $tname = $module->modulePath . 'templates/' . $templateName;
            try {
                $templates[] = PhpTemplate::Create($tname);

            } catch (\Throwable $e) {
                App::$log->debug('Запрошен шаблон модуля, который не существует: ' . $tname);
            }
        }
        return $templates;
    }

}