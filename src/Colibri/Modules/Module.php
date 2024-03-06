<?php

namespace Colibri\Modules;

use Colibri\App;
use Colibri\Common\StringHelper;
use Colibri\IO\FileSystem\File;
use Colibri\Utils\Config\Config;
use Colibri\Events\TEventDispatcher;
use Colibri\Utils\Config\ConfigItemsList;
use Colibri\Utils\Debug;

/**
 * Модуль
 * базовый класс
 *
 * @property-read string $modulePath
 * @property-read string $moduleNamepsace
 * @property-read string $moduleConfigPath
 * @property-read string $moduleStoragesPath
 * @property self $instance
 */
class Module
{
    // подключаем trait событийной модели
    use TEventDispatcher;

    /**
     * Обьект настроек
     *
     * @var Config
     */
    protected $_config;

    /**
     * Местоположение модуля
     *
     * @var string
     */
    protected string $_modulePath;

    /**
     * Namespace модуля
     *
     * @var string
     */
    protected string $_moduleNamespace;


    /**
     * Файл конфигурации модуля
     */
    protected string $_moduleConfigFile = '';

    /**
     * Файл конфигурации хранилищь в модуле
     */
    protected string $_moduleStoragesConfigPath = '';

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
     * Статический конструктор
     *
     * @param mixed $config
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
     * Возвращает обьект кофигурации
     *
     * @return Config
     */
    public function Config(?string $item = null, mixed $default = null): Config|ConfigItemsList
    {
        return $item ? $this->_config->Query('config.' . $item, $default) : $this->_config;
    }

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
     * Инициализация, вызывается после создания обьекта модуля
     *
     * @return void
     */
    public function InitializeModule(): void
    {
    }

    /**
     * Установка, вызывается менеджером при установке
     *
     * @return void
     */
    public function Install(): void
    {
    }

    /**
     * Удаление, вызывается менеджером при удалении модуля
     *
     * @return void
     */
    public function Uninstall(): void
    {
    }

    /**
     * Удаление обьекта модуля
     *
     * @return void
     */
    public function Dispose(): void
    {
    }

    /**
     * Список прав модуля, стандартный набор
     *
     * @return array
     */
    public function GetPermissions(): array
    {
        return [];
    }

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
