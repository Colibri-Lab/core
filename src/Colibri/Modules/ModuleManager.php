<?php

/**
 * Modules
 *
 * Represents a manager for modules within the application.
 *
 * @package Colibri\Modules
 */
namespace Colibri\Modules;

use Colibri\App;
use Colibri\AppException;
use Colibri\Collections\Collection;
use Colibri\Common\VariableHelper;
use Colibri\Events\TEventDispatcher;
use Colibri\Events\EventsContainer;
use Colibri\Utils\Config\Config;
use Colibri\Utils\Config\ConfigException;
use Colibri\Web\Templates\PhpTemplate;


/**
 * Module Manager
 *
 * Manages modules within the application.
 *
 * @property-read Config $settings Configuration settings for the module manager.
 * @property-read Collection $list List of modules.
 *
 */
class ModuleManager
{

    // Includes functionality of event dispatcher.
    use TEventDispatcher;

    /**
     * Singleton instance.
     *
     * @var ModuleManager
     */
    public static $instance;

    /**
     * Settings object.
     *
     * @var object
     */
    private $_settings;

    /**
     * List of modules.
     *
     * @var Collection
     */
    private $_list;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->_list = new Collection();
    }

    /**
     * Static constructor for creating a singleton instance.
     *
     * @return ModuleManager The created instance of ModuleManager.
     *
     */
    public static function Create(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initializes the module manager.
     *
     * @return void
     *
     */
    public function Initialize(): void
    {

        try {
            $this->_settings = App::$config->Query('modules');
            $entities = $this->_settings->Query('entries');
            $domainKey = App::$domainKey;
            foreach ($entities as $moduleConfig) {
                if (!$moduleConfig->Query('enabled', true)->GetValue()) {
                    continue;
                }

                $keysArray = $moduleConfig->Query('for', [])->ToArray();
                if (!empty($keysArray) && !in_array($domainKey, $keysArray)) {
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
     * Initializes a module.
     *
     * @param Config $configNode Configuration node for the module.
     * @return Module|null The initialized module instance, or null if initialization fails.
     *
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
     * Magic method to handle property retrieval.
     *
     * @param string $property The name of the property to retrieve.
     * @return mixed The value of the property.
     *
     * @magic
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

    /**
     * Retrieves a module by name.
     *
     * @param string $moduleName The name of the module.
     * @return mixed The module corresponding to the provided name.
     */
    public function Get(string $moduleName): mixed
    {
        return $this->_list->$moduleName;
    }

    /**
     * Gets the configuration of a module.
     *
     * @param string $name The name of the module.
     * @return Config The configuration of the module.
     *
     */
    public function Config(string $name): Config
    {
        return $this->_settings->$name->config;
    }

    /**
     * Retrieves the permissions of all modules.
     *
     * @return array The permissions of all modules.
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
     * Retrieves paths of modules.
     *
     * @param string $extend String to append to each path.
     * @param array|null $extendArray Additional elements to append to each path.
     * @return string[] Paths of modules.
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

    /**
     * Retrieves paths of modules from module configurations.
     *
     * @param array|null $extendArray Additional elements to append to each path.
     * @return string[] Paths of modules.
     */
    public function GetPathsFromModuleConfig(?array $extendArray = null): array
    {
        $paths = [];
        foreach ($this->list as $module) {
            $paths = [...$paths, ...$module->GetPathsFromModuleConfig($extendArray)];
        }
        return VariableHelper::UniqueByProperty($paths, 'path');
    }

    /**
     * Retrieves templates of modules.
     *
     * @param string $templateName The name of the template.
     * @return string[] Templates of modules.
     */
    public function GetTemplates(string $templateName = 'index'): array
    {
        $templates = [];
        foreach ($this->list as $module) {
            $tname = $module->modulePath . 'templates/' . $templateName;
            try {
                $templates[] = PhpTemplate::Create($tname);

            } catch (\Throwable $e) {
                // do nothing
            }
        }
        return $templates;
    }

}