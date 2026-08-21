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

use Colibri\Utils\Singleton;
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
use Colibri\Web\Session;

/**
 * Main application class.
 * @class
 * @extends Singleton
 * @used TEventDispatcher
 */
final class App extends Singleton
{
    // Include event model functionality
    use TEventDispatcher;

    /** 
     * Application mode for local machine
     * @const string 
     * @public
     */
    public const ModeLocal = 'local';
    /** 
     * Application mode for development
     * @const string 
     * @public
     */
    public const ModeDevelopment = 'dev';
    /** 
     * Application mode for testing
     * @const string 
     * @public
     */
    public const ModeTest = 'test';
    /** 
     * Application mode for production
     * @const string 
     * @public
     */
    public const ModeRelease = 'prod';

    /**
     * Session object
     * @var Session|null
     * @public
     * @static
     */
    public static ?Session $session = null;

    /** 
     * Request object
     * @var Request|null 
     * @public
     * @static
     */
    public static ?Request $request = null;

    /** 
     * Response object
     * @var Response|null 
     * @public
     * @static
     */
    public static ?Response $response = null;

    /** 
     * Application root directory
     * @var string 
     * @public
     * @static
     */
    public static string $appRoot = '';

    /** 
     * Public directory root
     * @var string 
     * @public
     * @static
     */
    public static string $webRoot = '';

    /** 
     * Path to vendor folder
     * @var string 
     * @public
     * @static
     */
    public static string $vendorRoot = '';

    /** 
     * Application mode
     * @var string 
     * @public
     * @static
     */
    public static string $mode = 'local';

    /** 
     * Indicates whether the application is in development mode
     * @var bool 
     * @public
     * @static
     */
    public static bool $isDev = false;

    /** 
     * Indicates whether the application is running locally
     * @var bool 
     * @public
     * @static
     */
    public static bool $isLocal = false;

    /** 
     * Application configuration file
     * @var Config|null 
     * @public
     * @static
     */
    public static ?Config $config = null;

    /** 
     * Event dispatcher
     * @var EventDispatcher|null
     * @public
     * @static
     */
    public static ?EventDispatcher $eventDispatcher = null;

    /** 
     * Module manager
     * @var ModuleManager|null 
     * @public
     * @static
     */
    public static ?ModuleManager $moduleManager = null;

    /** 
     * Data access points
     * @var DataAccessPoints|null
     * @public
     * @static
     */
    public static ?DataAccessPoints $dataAccessPoints = null;

    /** 
     * Logger device
     * @var Logger|null 
     * @public
     * @static
     */
    public static ?Logger $log = null;

    /** 
     * Process manager
     * @var Manager|null 
     * @public
     * @static
     */
    public static ?Manager $threadingManager = null;

    /** 
     * Monitoring object
     * @var Monitoring|null 
     * @public
     * @static
     */
    public static ?Monitoring $monitoring = null;

    /** 
     * Domain key
     * @var string|null 
     * @public
     * @static
     */
    public static ?string $domainKey = null;

    /** 
     * Router object
     * @var ?Router
     * @public
     * @static
     */
    public static ?Router $router = null;

    /** 
     * System timezone
     * @var ?string 
     * @public
     * @static
     */
    public static ?string $systemTimezone = 'UTC';
    /** 
     * System locale
     * @var ?string 
     * @public
     * @static
     */
    public static ?string $systemLocale = 'en_US';
    /** 
     * System charset
     * @var ?string 
     * @public
     * @static
     */
    public static ?string $systemCharset = 'UTF-8';

    /**
     * Prevents instantiation of the class.
     * @protected
     * @constructor
     */
    protected function __construct()
    {
        // Do nothing
    }

    /**
     * Initializes the application.
     *
     * @public
     * @param Request|null $request The request object (optional).
     * @param Response|null $response The response object (optional).
     * @param string|null $webRootPath The web root path (optional).
     * @param bool $forceReinitializeAll Whether to force reinitialization of all components (default: false).
     * @return void
     */
    public function Initialize(?Request $request = null, ?Response $response = null, ?string $webRootPath = null, bool $forceReinitializeAll = false): void
    {

        // try to get system timezone
        self::$systemTimezone = trim(shell_exec('cat /etc/timezone 2>/dev/null'), "\r\t\n ");
        if(!self::$systemTimezone) {
            self::$systemTimezone = trim(shell_exec('timedatectl show -p Timezone --value'), "\r\t\n ");
        }
        if(!self::$systemTimezone) {
            self::$systemTimezone = 'UTC';
        }
        date_default_timezone_set(self::$systemTimezone);

        $locale = str_replace('System Locale: LANG=', '', trim(shell_exec('localectl status | grep "System Locale"')));
        if($locale) {
            $locale = trim($locale, "\r\t\n ");
            $locale = explode('.', $locale);
            $charset = $locale[1] ?? 'UTF-8';
            self::$systemLocale = $locale[0];
            self::$systemCharset = $charset;
        }

        // PHP CLI support block
        if (!$request && isset($_SERVER['argv']) && !isset($_SERVER['REQUEST_METHOD'])) {

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
            self::$webRoot = ($webRootPath ?? $_SERVER['DOCUMENT_ROOT']) . '/';

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
            $host = $request ? $request->host : $_SERVER['HTTP_HOST'];
            $domains = self::$config->Query('hosts.domains')->AsObject();

            foreach ($domains as $key => $patterns) {
                foreach ((object)$patterns as $pattern) {
                    if(!str_starts_with($pattern, '/') || !str_ends_with($pattern, '/')) {
                        $pattern = preg_quote($pattern);
                        $pattern = str_replace('\\*', '.*', $pattern);
                        $pattern = '/' . $pattern . '/';
                    }
                    $res = preg_match($pattern, $host, $matches);
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
            self::$dataAccessPoints = DataAccessPoints::Instance();
            self::$dataAccessPoints->Initialize();
        }

        // Start events
        if (!self::$eventDispatcher || $forceReinitializeAll) {
            self::$eventDispatcher = EventDispatcher::Instance();
            self::$eventDispatcher->Clear();
        }

        $this->DispatchEvent(EventsContainer::AppInitializing);

        if($request) {
            self::$request = $request;
        } else if (!self::$request) {
            self::$request = Request::Instance();
        }
        if ($response) {
            self::$response = $response;
        } else if (!self::$response) {
            self::$response = Response::Instance();
        }

        if (!self::$router) {
            self::$router = new Router();
        }
        self::$router->UpdateRequest();

        if(!self::$session) {
            $sessionTtl = self::$config->Query('session.ttl', 86400)->GetValue();
            self::$session = new Session($sessionTtl);
        }

        self::$monitoring->StartTimer('modules');
        if (!self::$moduleManager || $forceReinitializeAll) {
            self::$moduleManager = ModuleManager::Instance();
            self::$moduleManager->Initialize();
        }
        self::$monitoring->EndTimer('modules');

        self::$monitoring->StartTimer('threads');
        if (!self::$threadingManager) {
            self::$threadingManager = Manager::Instance();
        }
        self::$monitoring->EndTimer('threads');

        self::$monitoring->EndTimer('app');

        $this->DispatchEvent(EventsContainer::AppReady);
    }

    /**
     * Clones the application instance with a new request and response.
     * @public
     *
     * @param Request $request The new request object.
     * @param Response $response The new response object.
     * @return App A new instance of the application with the provided request and response.
     */
    public function Clone(Request $request, Response $response): App
    {
        $app = new App();
        $app->Initialize($request, $response);
        return $app;
    }

    /**
     * Returns a list of permissions for the application.
     * @public
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
     * @public
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

    /**
     * Generates a new CSRF token and stores it in the session.
     * @public
     * @static
     *
     * @return string The generated CSRF token.
     */
    public static function GenerateNewCsfrToken(): string
    {
        if(!self::$session->csrf_token) {
            self::$session->csrf_token = bin2hex(random_bytes(32));
        }
        return self::$session->csrf_token;
    }

    /**
     * Checks if the CSRF token in the request headers matches the one stored in the session.
     * @static
     * @public
     *
     * @param object|null $headers Optional headers object. If not provided, uses the request headers.
     * @return bool True if the CSRF token is correct, false otherwise.
     */
    public static function CsfrIsCorrect($headers = null): bool
    {
        if(!$headers) {
            $headers = self::$request->headers;
        }
        $return = $headers->{'x-csrf-token'} === self::$session->csrf_token;
        return $return;
    }

    /**
     * Checks if the CSRF token is present in the request headers.
     * @public
     * @static
     *
     * @param object|null $headers Optional headers object. If not provided, uses the request headers.
     * @return bool True if the CSRF token is present, false otherwise.
     */
    public static function HasCsfrInRequest($headers = null): bool
    {
        if(!$headers) {
            $headers = self::$request->headers;
        }   
        return is_string($headers->{'x-csrf-token'});
    }

}

