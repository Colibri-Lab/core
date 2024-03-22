<?php

/**
 * Modules
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Modules
 */

namespace Colibri\Modules;

use Colibri\App;
use Colibri\IO\FileSystem\File;
use Colibri\Utils\Config\Config;
use Colibri\Events\TEventDispatcher;
use Colibri\Utils\Config\ConfigItemsList;

/**
 * Module
 * Base class representing a module.
 *
 * @property-read string $modulePath The path of the module.
 * @property-read string $moduleNamespace The namespace of the module.
 * @property-read string $moduleConfigPath The file path of the module's configuration.
 * @property-read string $moduleStoragesPath The file path of the module's storage configurations.
 * @property self $instance An instance of the Module class.
 */
class Module
{
    // Include event dispatcher trait.
    use TEventDispatcher;

    /**
     * Config object.
     *
     * @var Config
     */
    protected $_config;

    /**
     * Location of the module.
     *
     * @var string
     */
    protected string $_modulePath;

    /**
     * Namespace of the module.
     *
     * @var string
     */
    protected string $_moduleNamespace;

    /**
     * File path of the module configuration.
     *
     * @var string
     */
    protected string $_moduleConfigFile = '';

    /**
     * File path of the module's storage configurations.
     *
     * @var string
     */
    protected string $_moduleStoragesConfigPath = '';

    /**
     * Constructor.
     *
     * @param mixed $config The configuration for the module.
     */
    protected function __construct(mixed $config)
    {

        // загружаем настройки модуля
        if (is_string($config)) {
            $config = new Config($config, file_exists($config));
        }
        $this->_config = $config;

        $reflection = new \ReflectionClass(static::class);
        $filename = $reflection->getFileName();
        $this->_modulePath = str_replace('Module.php', '', $filename);
        $this->_moduleNamespace = $reflection->getNamespaceName() . '\\';

        // если начинается не с вендора то надо попробовать найти
        // composer.json и выкопать из него где реально лежит модуль
        if(strstr($this->_modulePath, '/vendor/') === false && strstr($this->_modulePath, '/App/') === false) {
            $pathParts = explode('/', $this->_modulePath);
            $composerPath = implode('/', $pathParts) . '/composer.json';
            while(!File::Exists($composerPath)) {
                array_pop($pathParts);
                $composerPath = implode('/', $pathParts) . '/composer.json';
            }
            $composerContent = (object)json_decode(File::Read($composerPath));
            $name = $composerContent->name;
            $psr4 = $composerContent->autoload->{'psr-4'}->{$this->_moduleNamespace};
            $this->_modulePath = App::$appRoot . 'vendor/' . $name . '/' . $psr4;
        }

        $configArray = $this->_config->AsArray();
        $this->_moduleConfigFile = str_replace(')', '', str_replace('include(', '', $configArray['config']));

        try {
            $databasesConfigArray = $this->Config()->Query('config.databases')->AsArray();
            $this->_moduleStoragesConfigPath = str_replace(
                ')',
                '',
                str_replace('include(', '', $databasesConfigArray['storages'])
            );

        } catch (\Throwable $e) {
            // do nothing
        }

    }

    /**
     * Static constructor to create an instance of the Module class.
     *
     * @param mixed $config The configuration for the module.
     * @return mixed
     */
    public static function Create(mixed $config): self
    {
        if (!static::$instance) {
            static::$instance = new static ($config);
        }
        return static::$instance;
    }

    /**
     * Returns the configuration object.
     *
     * @param string|null $item The item to retrieve from the configuration.
     * @param mixed $default The default value if the item is not found.
     * @return Config|ConfigItemsList
     */
    public function Config(?string $item = null, mixed $default = null): Config|ConfigItemsList
    {
        return $item ? $this->_config->Query('config.' . $item, $default) : $this->_config;
    }

    /**
     * Magic method to retrieve module properties.
     *
     * @param string $prop The property name.
     * @return mixed
     */
    public function __get(string $prop): mixed
    {
        $return = null;
        $prop = strtolower($prop);
        if ($prop == 'modulepath') {
            $return = $this->_modulePath;
        } elseif ($prop == 'modulenamespace') {
            $return = $this->_moduleNamespace;
        } elseif ($prop == 'moduleconfigpath') {
            $return = $this->_moduleConfigFile;
        } elseif ($prop == 'modulestoragespath') {
            $return = $this->_moduleStoragesConfigPath;
        }
        return $return;
    }

    /**
     * Initializes the module after object creation.
     *
     * @return void
     */
    public function InitializeModule(): void
    {
    }

    /**
     * Installs the module.
     *
     * @return void
     */
    public function Install(): void
    {
    }

    /**
     * Uninstalls the module.
     *
     * @return void
     */
    public function Uninstall(): void
    {
    }

    /**
     * Disposes of the module object.
     *
     * @return void
     */
    public function Dispose(): void
    {
    }

    /**
     * Retrieves the list of permissions for the module.
     *
     * @return array
     */
    public function GetPermissions(): array
    {
        return [];
    }

    /**
     * Retrieves paths from the module configuration.
     *
     * @param array|null $extendArray Additional array to extend the result.
     * @return array
     */
    public function GetPathsFromModuleConfig(?array $extendArray = null): array
    {
        $paths = [];
        $p = $this->Config()->Query('config.paths.ui', [])->ToArray();
        if(!empty($p)) {
            foreach($p as $path) {
                if (is_object($path)) {
                    $path = $path->path;
                } elseif (is_array($path)) {
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
        return $paths;
    }

}
