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
     * Синглтон
     *
     * @var mixed
     */
    public static $instance;

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
    protected $_modulePath;

    /**
     * Namespace модуля
     *
     * @var string
     */
    protected $_moduleNamespace;

    protected function __construct($config)
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
     * @param \stdClass $config
     * @return mixed
     */
    public static function Create($config)
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
    public function Config()
    {
        return $this->_config;
    }

    public function __get($prop)
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
    public function InitializeModule()
    {
    }

    /**
     * Установка, вызывается менеджером при установке
     *
     * @return void
     */
    public function Install()
    {
    }

    /**
     * Удаление, вызывается менеджером при удалении модуля
     *
     * @return void
     */
    public function Uninstall()
    {
    }

    /**
     * Удаление обьекта модуля
     *
     * @return void
     */
    public function Dispose()
    {
    }

    /**
     * Список прав модуля, стандартный набор
     *
     * @return array
     */
    public function GetPermissions()
    {

        $permissions = [];

        // $className = static::class;
        // $permissionsName = strtolower(str_replace('\\', '.', $className));

        // $permissions[$permissionsName . '.load'] = 'Загрузка модуля';
        // $permissions[$permissionsName . '.install'] = 'Установка модуля';
        // $permissions[$permissionsName . '.uninstall'] = 'Деинсталляция модуля';

        return $permissions;
    }
}
