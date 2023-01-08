<?php

namespace Colibri\Modules;

use Colibri\App;
use Colibri\Common\StringHelper;
use Colibri\Utils\Config\Config;
use Colibri\Events\TEventDispatcher;
use Colibri\Utils\Debug;

/**
 * Модуль
 * базовый класс
 *
 * @property-read string $modulePath
 * @property-read string $moduleNamepsace
 * @property-read string $moduleConfigPath
 * @property-read string $moduleStoragesPath
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

        $configArray = $this->_config->AsArray();
        $this->_moduleConfigFile = str_replace(')', '', str_replace('include(', '', $configArray['config']));

        try {
            $databasesConfigArray = $this->Config()->Query('config.databases')->AsArray();
            $this->_moduleStoragesConfigPath = str_replace(')', '', str_replace('include(', '', $databasesConfigArray['storages']));

        } catch (\Throwable $e) {

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
    public function Config(): Config
    {
        return $this->_config;
    }

    public function __get(string $prop): mixed
    {
        $prop = strtolower($prop);
        if ($prop == 'modulepath') {
            return $this->_modulePath;
        } elseif ($prop == 'modulenamespace') {
            return $this->_moduleNamespace;
        } elseif ($prop == 'moduleconfigpath') {
            return $this->_moduleConfigFile;
        } elseif ($prop == 'modulestoragespath') {
            return $this->_moduleStoragesConfigPath;
        }
        return false;
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
}