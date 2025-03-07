<?php

/**
 * Main application class.
 *
 * This class represents the core of the application.
 *
 * @author Vagan Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package App
 * @version 1.0.0
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
 * Main application class.
 */
final class App
{
    // Include event model functionality
    use TEventDispatcher;

    /** @var string Application mode for local machine */
    public const ModeLocal = 'local';
    /** @var string Application mode for development */
    public const ModeDevelopment = 'dev';
    /** @var string Application mode for testing */
    public const ModeTest = 'test';
    /** @var string Application mode for production */
    public const ModeRelease = 'prod';

    /** @var App|null Singleton instance */
    public static ?App $instance = null;

    /** @var Request|null Request object */
    public static ?Request $request = null;

    /** @var Response|null Response object */
    public static ?Response $response = null;

    /** @var string Application root directory */
    public static string $appRoot = '';

    /** @var string Public directory root */
    public static string $webRoot = '';

    /** @var string Path to vendor folder */
    public static string $vendorRoot = '';

    /** @var string Application mode */
    public static string $mode = 'local';

    /** @var bool Indicates whether the application is in development mode */
    public static bool $isDev = false;

    /** @var bool Indicates whether the application is running locally */
    public static bool $isLocal = false;

    /** @var Config|null Application configuration file */
    public static ?Config $config = null;

    /** @var EventDispatcher|null Event dispatcher */
    public static ?EventDispatcher $eventDispatcher = null;

    /** @var ModuleManager|null Module manager */
    public static ?ModuleManager $moduleManager = null;

    /** @var DataAccessPoints|null Data access points */
    public static ?DataAccessPoints $dataAccessPoints = null;

    /** @var Logger|null Logger device */
    public static ?Logger $log = null;

    /** @var Manager|null Process manager */
    public static ?Manager $threadingManager = null;

    /** @var Monitoring|null Monitoring */
    public static ?Monitoring $monitoring = null;

    /** @var string|null Domain key */
    public static ?string $domainKey = null;

    /** @var Router|null Router */
    public static ?Router $router = null;

    /** @var string System timezone */
    public static string $systemTimezone = 'UTC';

    /**
     * Prevents instantiation of the class.
     */
    private function __construct()
    {
        // Do nothing
    }

    /**
     * Static constructor.
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
     * Initializes the application.
     *
     * @return void
     */
    public function Initialize(): void
    {

        // try to get system timezone
        self::$systemTimezone = trim(shell_exec('cat /etc/timezone'), "\r\t\n ");
        date_default_timezone_set(self::$systemTimezone);

        // PHP CLI support block
        if (isset($_SERVER['argv']) && !isset($_SERVER['REQUEST_METHOD'])) {

            if (File::Exists(realpath(getcwd() . '/../config/app.yaml'))) {
                $_SERVER['DOCUMENT_ROOT'] = realpath(getcwd() . '/');
            } elseif (File::Exists(realpath(getcwd() . '/../../config/app.yaml'))) {
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

        // Get application location
        if (!self::$appRoot) {

            // пробуем получить DOCUMENT_ROOT
            self::$webRoot = $_SERVER['DOCUMENT_ROOT'] . '/';

            // корень приложения должен находится на уровень выше
            self::$appRoot = realpath(self::$webRoot . '/../') . '/';

            self::$vendorRoot = realpath(self::$appRoot) . '/vendor/';
        }

        // Load configurations
        if (!self::$config) {
            self::$config = Config::LoadFile('app.yaml');
        }

        // Initialize logger device
        if (!self::$log) {
            self::$log = Logger::Create(self::$config->Query('logger'));
        }

        // Set application mode
        self::$mode = self::$config->Query('mode')->GetValue();
        if (self::$mode == App::ModeDevelopment || self::$mode == App::ModeLocal) {
            self::$isDev = true;
            if (self::$mode === App::ModeLocal) {
                self::$isLocal = true;
            }
        }

        // Define domain and domain key based on the host
        try {
            $host = $_SERVER['HTTP_HOST'];
            $domains = self::$config->Query('hosts.domains')->AsObject();

            foreach ($domains as $key => $patterns) {
                foreach ($patterns as $pattern) {

                    $pattern = preg_quote($pattern);
                    $pattern = str_replace('\\*', '.*', $pattern);
                    $res = preg_match('/' . $pattern . '/', $host, $matches);
                    if ($res > 0) {
                        // нашли
                        self::$domainKey = $key;
                        break 2;
                    }
                }
            }

        } catch (ConfigException $e) {
            // do nothing
        }

        // Create monitoring object
        $monitoringConfig = self::$config->Query('monitoring');
        if ($monitoringConfig) {
            $level = $monitoringConfig->Query('level')->GetValue();
            $logging = $monitoringConfig->Query('logging')->GetValue();
        } else {
            $logging = self::$isDev ? Logger::Debug : Logger::Critical;
            $level = self::$isDev ? Monitoring::EveryTimer : Monitoring::Never;
        }
        self::$monitoring = new Monitoring(self::$log, $level, $logging);
        self::$monitoring->StartTimer('app');

        // Create utility classes
        // Utility creation without binding to the application, just creating utility
        Mem::Create(self::$config->Query(
            'memcache.host',
            'localhost'
        )->GetValue(), self::$config->Query('memcache.port', '11211')->GetValue());

        // Create DAL
        if (!self::$dataAccessPoints) {
            self::$dataAccessPoints = DataAccessPoints::Create();
        }

        // Start events
        if (!self::$eventDispatcher) {
            self::$eventDispatcher = EventDispatcher::Create();
        }

        $this->DispatchEvent(EventsContainer::AppInitializing);

        if (!self::$router) {
            self::$router = new Router();
            self::$router->UpdateRequest();
        }

        // Start request
        if (!self::$request) {
            self::$request = Request::Create();
        }
        // Start response
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
     * Returns a list of permissions for the application.
     *
     * @return array List of permissions
     */
    public function GetPermissions(): array
    {

        $permissions = [];

        $permissions['app'] = 'Приложение';
        $permissions['app.load'] = 'Загрузка приложения';

        return $permissions;
    }

    /**
     * Backs up necessary files.
     *
     * @param Logger $logger Logger instance
     * @param string $path Path to backup location
     * @return void
     */
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

    public static function GenerateNewCsfrToken(): string
    {
        session_start();
        if(!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        session_write_close();
        return $_SESSION['csrf_token'];
    }

    public static function CsfrIsCorrect(): bool
    {
        session_start();
        $return = self::$request->headers->{'x-csrf-token'} === $_SESSION['csrf_token'];
        session_write_close();
        return $return;
    }

    public static function HasCsfrInRequest(): bool
    {
        return is_string(self::$request->headers->{'x-csrf-token'});
    }

}

App::Create();
