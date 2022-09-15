<?php

/**
 * Основной класс приложения
 * 
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package App
 * @version 1.0.0
 * 
 * 
 */

namespace Colibri;

use Colibri\Web\Request;
use Colibri\Web\Response;
use Colibri\Utils\Config\Config;
use Colibri\Events\EventDispatcher;
use Colibri\Modules\ModuleManager;
use Colibri\Events\TEventDispatcher;
use Colibri\Events\EventsContainer;
use Colibri\Utils\Cache\Mem;
use Colibri\Data\DataAccessPoints;
use Colibri\Utils\Debug;
use Colibri\IO\FileSystem\File;
use Colibri\Threading\Manager;
use Colibri\Utils\Logs\Logger;
use Colibri\Utils\Performance\Monitoring;
use Colibri\Xml\XmlNode;
use Colibri\Utils\Config\ConfigException;
use Colibri\Web\Router;
use Colibri\IO\FileSystem\Directory;


/**
 * Класс приложения
 */
final class App
{
    // подключаем функционал событийной модели
    use TEventDispatcher;

    /** Режим приложения на локальном компьютере */
    const ModeLocal = 'local';
    /** Режим приложения в разработке */
    const ModeDevelopment = 'dev';
    /** Режим приложения в тестировании */
    const ModeTest = 'test';
    /** Режим приложения в релизе */
    const ModeRelease = 'prod';

    /**
     * Синглтон
     *
     * @var App
     */
    public static ?App $instance = null;

    /**
     * Обьект запроса
     *
     * @var Request
     */
    public static ?Request $request = null;

    /**
     * Обьект ответа
     *
     * @var Response
     */
    public static ?Response $response = null;

    /**
     * Корень приложения
     *
     * @var string
     */
    public static string $appRoot = '';

    /**
     * Корень Public части сайта
     *
     * @var string
     */
    public static string $webRoot = '';

    /**
     * Режим разработки
     * @var boolean
     */
    public static bool $isDev = false;

    /**
     * Конфигурационный файл приложения
     *
     * @var Config
     */
    public static ?Config $config = null;

    /**
     * Диспатчер событий
     *
     * @var EventDispatcher
     */
    public static ?EventDispatcher $eventDispatcher = null;

    /**
     * Менеджер модулей
     *
     * @var ModuleManager
     */
    public static ?ModuleManager $moduleManager = null;

    /**
     * Доступ к данным DAL
     *
     * @var DataAccessPoints
     */
    public static ?DataAccessPoints $dataAccessPoints = null;

    /**
     * Лог девайс
     * @var Logger
     */
    public static ?Logger $log = null;

    /**
     * Менеджер процессов
     * @var Manager
     */
    public static ?Manager $threadingManager = null;

    /**
     * Мониторинг
     * @var Monitoring
     */
    public static ?Monitoring $monitoring = null;

    /**
     * Ключ домена
     */
    public static ?string $domainKey = null;

    /**
     * Раутер
     */
    public static ?Router $router = null;

    /**
     * Закрываем конструктор
     */
    private function __construct()
    {
    // Do nothing
    }

    /**
     * Статический конструктор
     *
     * @return self
     */
    public static function Create(): self
    {

        if (!self::$instance) {
            self::$instance = new App();
            self::$instance->Initialize();
        }

        return self::$instance;
    }

    /**
     * Инициализация приложения
     *
     * @return void
     */
    public function Initialize(): void
    {

        // Блок для обеспечения работы с php-cli
        if (isset($_SERVER['argv']) && !isset($_SERVER['REQUEST_METHOD'])) {

            if (File::Exists(realpath(getcwd() . '/../config/app.yaml'))) {
                $_SERVER['DOCUMENT_ROOT'] = realpath(getcwd() . '/');
            }
            else if (File::Exists(realpath(getcwd() . '/../../config/app.yaml'))) {
                $_SERVER['DOCUMENT_ROOT'] = realpath(getcwd() . '/../');
            }

            $_SERVER['SERVER_NAME'] = @$_SERVER['argv'][1];
            $_SERVER['HTTP_HOST'] = @$_SERVER['argv'][1];
            $_SERVER['REQUEST_URI'] = @$_SERVER['argv'][2];
            $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
            $_SERVER['COMMANDLINE'] = true;

            for ($i = 3; $i < $_SERVER['argc']; $i++) {
                $data = explode('=', $_SERVER['argv'][$i]);
                $_GET[$data[0]] = substr($_SERVER['argv'][$i], strlen($data[0] . '='));
            }
        }

        // получаем местоположение приложения
        if (!self::$appRoot) {

            // пробуем получить DOCUMENT_ROOT
            self::$webRoot = $_SERVER['DOCUMENT_ROOT'] . '/';

            // корень приложения должен находится на уровень выше
            self::$appRoot = realpath(self::$webRoot . '/../') . '/';
        }

        // поднимаем конфиги
        if (!self::$config) {
            self::$config = Config::LoadFile('app.yaml');
        }

        // поднимаем лог девайс
        if (!self::$log) {
            self::$log = Logger::Create(self::$config->Query('logger'));
        }
        

        $mode = self::$config->Query('mode')->GetValue();
        if ($mode == App::ModeDevelopment || $mode == App::ModeLocal) {
            self::$isDev = true;
        }

        // определяем домен, и ключ домена по хосту
        try {
            $host = $_SERVER['HTTP_HOST'];
            $domains = self::$config->Query('hosts.domains')->AsObject();

            foreach($domains as $key => $patterns) {
                foreach($patterns as $pattern) {

                    $pattern = preg_quote($pattern);
                    $pattern = str_replace('\\*', '.*', $pattern);
                    $res = preg_match('/'.$pattern.'/', $host, $matches);
                    if($res > 0) {
                        // нашли
                        self::$domainKey = $key;
                        break 2;
                    }
                }
            }

        }
        catch(ConfigException $e) {
        }

        /**
         * Создаем обьект мониторинга
         */
        $monitoringConfig = self::$config->Query('monitoring');
        if($monitoringConfig) {
            $level = $monitoringConfig->Query('level')->GetValue();
            $logging = $monitoringConfig->Query('logging')->GetValue();    
        }
        else {
            $logging = self::$isDev ? Logger::Debug : Logger::Critical;
            $level = self::$isDev ? Monitoring::EveryTimer : Monitoring::Never;
        }
        self::$monitoring = new Monitoring(self::$log, $level, $logging);
        self::$monitoring->StartTimer('app');

        // создание всяких утилитных классов
        // без привязки к приложению, просто создаем утилиту
        Mem::Create(self::$config->Query('memcache.host', 'localhost')->GetValue(), self::$config->Query('memcache.port', '11211')->GetValue());

        // создание DAL
        if (!self::$dataAccessPoints) {
            self::$dataAccessPoints = DataAccessPoints::Create();
        }


        // в первую очеред запускаем события
        if (!self::$eventDispatcher) {
            self::$eventDispatcher = EventDispatcher::Create();
        }

        $this->DispatchEvent(EventsContainer::AppInitializing);

        if(!self::$router) {
            self::$router = new Router();
            self::$router->UpdateRequest();
        }

        // запускаем запрос
        if (!self::$request) {
            self::$request = Request::Create();
        }
        // запускаем ответ
        if (!self::$response) {
            self::$response = Response::Create();
        }


        self::$monitoring->StartTimer('modules');
        if (!self::$moduleManager) {
            self::$moduleManager = ModuleManager::Create();
            self::$moduleManager->Initialize();
        }
        self::$monitoring->EndTimer('modules');

        self::$monitoring->StartTimer('threads');
        if (!self::$threadingManager) {
            self::$threadingManager = Manager::Create();
        }
        self::$monitoring->EndTimer('threads');

        self::$monitoring->EndTimer('app');

        $this->DispatchEvent(EventsContainer::AppReady);
    }

    /**
     * Возвращает список прав для приложения
     *
     * @return array
     */
    public function GetPermissions(): array
    {

        $permissions = [];

        $permissions['app'] = 'Приложение';
        $permissions['app.load'] = 'Загрузка приложения';

        return $permissions;
    }

    public function Backup(Logger $logger, string $path): void
    {

        $logger->debug('Copying configuration, including all module configs');
        // копируем конфиг
        $configPath = App::$appRoot . 'config/';
        Directory::Copy($configPath, $path . 'config/');

        $logger->debug('Copying resources');
        $configPath = App::$webRoot . 'res/';
        Directory::Copy($configPath, $path . 'web/res/');

        $logger->debug('Copying composer.json');
        File::Copy(App::$appRoot . 'composer.json', $path . 'composer.json');

        $logger->debug('Copying composer.lock');
        File::Copy(App::$appRoot . 'composer.lock', $path . 'composer.lock');


    }

}

App::Create();
