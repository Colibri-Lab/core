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
        $this->_moduleNamespace = $reflection->getNamespaceName().'\\';

    }

    /**
     * Статический конструктор
     *
     * @param mixed $config
     * @return mixed
     */
    public static function Create(mixed $config) : self
    {
        if (!static::$instance) {
            static::$instance = new static($config);
        }
        return static::$instance;
    }

    /**
     * Возвращает обьект кофигурации
     *
     * @return Config
     */
    public function Config() : Config
    {
        return $this->_config;
    }

    public function __get(string $prop) : mixed
    {
        $prop = strtolower($prop);
        if ($prop == 'modulepath') {
            return $this->_modulePath;
        }
        else if ($prop == 'modulenamespace') {
            return $this->_moduleNamespace;
        }
        return false;
    }

    /**
     * Инициализация, вызывается после создания обьекта модуля
     *
     * @return void
     */
    public function InitializeModule() : void
    {
    }

    /**
     * Установка, вызывается менеджером при установке
     *
     * @return void
     */
    public function Install() : void
    {
    }

    /**
     * Удаление, вызывается менеджером при удалении модуля
     *
     * @return void
     */
    public function Uninstall() : void
    {
    }

    /**
     * Удаление обьекта модуля
     *
     * @return void
     */
    public function Dispose() : void
    {
    }

    /**
     * Список прав модуля, стандартный набор
     *
     * @return array
     */
    public function GetPermissions() : array
    {
        return [];
    }
}
