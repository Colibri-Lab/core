<?php

/**
 * Threading
 *
 * This class manages processes for multithreading.
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Threading
 */

namespace Colibri\Threading;

use Colibri\App;
use Colibri\Common\VariableHelper;
use Colibri\IO\FileSystem\File;
use Colibri\Utils\Debug;

/**
 * Manages processes for multithreading.
 *
 * Attention: For proper functionality, ensure the presence of php_cli, memcached, and permissions to execute scripts using shell_exec and exec.
 * Before use, verify the existence of the php_cli handler in the folder specified by the Handler constant. If your handler is elsewhere, modify this variable accordingly.
 *
 * Note: Two instances of the Worker class will be present, one in the source thread and the other in the spawned thread.
 *
 * Example Usage:
 *
 * ```php
 * class TestWorker extends Worker { // Wrapper class for the worker
 *     public function Run(): void { // Defines the function and specifies the actions
 *         for ($i = 0; $i < 10; $i++)
 *             $this->_log->debug('Test Worker run ok...', $i, rout($this->_params));
 *     }
 * }
 *
 * $worker = new TestWorker();
 * $process = new Process($worker); // or Process::Create($worker);
 * $process->Run((object)['blablabla' => 'test']); // Starts the worker and passes parameters to the thread
 *
 * $workerOutput = [];
 * $worker->log->Open(); // Opens the worker's log
 * while ($process->IsRunning()) {  // Checks if the worker is still running
 *     $workerOutput = array_merge($workerOutput, $worker->log->Read()); // Reads the latest messages
 *     sleep(1);
 * }
 * $worker->log->Close(); // Closes the worker's log
 * ```
 *
 * @property-read int $pid Process ID (PID)
 * @property-read string $name Name of the process
 * @property-read string $command Command used to start the worker
 * @property-read string $request HTTP request URL for the worker
 * @property array|object $params Parameters passed to the process
 * @property-read Worker $worker Instance of the Worker class
 * @property-read ?object $results Results obtained from the worker
 */
class Process
{
    public static bool $useSudo = false;

    /**
     * Process ID (PID) of the worker process.
     *
     * @var int
     */
    private $_pid;

    /**
     * Worker instance to be launched.
     *
     * @var Worker
     */
    private $_worker;

    /**
     * Indicates whether to display the command used to start the worker.
     *
     * @var bool
     */
    private $_debug;

    /**
     * Entry point for the console stream.
     *
     * @var string
     */
    private $_entry;

    /**
     * Parameters for the process.
     *
     * @var object
     */
    private object $_params;

    /**
     * Key for the worker.
     *
     * @var string
     */
    private string $_workerKey = '';

    /**
     * Request handler, usually php_cli.
     * Modify this variable if php_cli is located elsewhere on the server.
     *
     * @var string
     */
    private string $_handler = '/usr/bin/php';

    /**
     * Initializes a new instance of the Process class.
     *
     * @param Worker $worker Worker instance to be launched
     * @param bool $debug Indicates whether to display the command used to start the worker
     * @param string $entry Entry point for the console stream
     * @param int $pid Process ID (PID) of the worker process
     */
    public function __construct(Worker $worker, bool $debug = false, string $entry = '', $pid = 0)
    {
        $this->_worker = $worker;
        $this->_workerKey = $worker->key;
        $this->_debug = $debug;
        $this->_entry = $entry;
        $this->_pid = $pid;
        $this->_params = (object) [];
    }

    /**
     * Creates a new instance of the Process class.
     *
     * @param Worker $worker Worker instance to be launched
     * @param bool $debug Indicates whether to display the command used to start the worker
     * @param string $entry Entry point for the console stream
     * @return Process
     */
    public static function Create(Worker $worker, bool $debug = false, string $entry = ''): Process
    {
        return new Process($worker, $debug, $entry);
    }



    /**
     * Sets the handler for processing requests.
     *
     * @param string $handler Request handler
     * @return void
     */
    public function SetHandler(string $handler): void
    {
        $this->_handler = $handler;
    }

    /**
     * Retrieves the results obtained from the worker.
     *
     * @param bool $removeResults Indicates whether to remove the results after retrieval
     * @return ?object Results obtained from the worker
     */
    public function GetWorkerResults(bool $removeResults = true): ?object
    {
        $workerKey = $this->_workerKey;
        $workerDataPath = App::$appRoot . App::$config->Query('runtime')->GetValue() . 'workers/';
        $results = null;
        if(File::Exists($workerDataPath . $workerKey)) {
            $results = json_decode(File::Read($workerDataPath . $workerKey));
            if($removeResults) {
                File::Delete($workerDataPath . $workerKey);
            }
        }
        return $results;
    }

    /**
     * Magic getter method.
     *
     * @param string $prop Property name
     * @return mixed Property value
     */
    public function __get(string $prop): mixed
    {
        $prop = strtolower($prop);
        if ($prop == 'pid') {
            return $this->_pid;
        } elseif ($prop === 'name') {
            $parts = explode('\\', get_class($this->_worker));
            return end($parts);
        } elseif ($prop === 'worker') {
            return $this->_worker;
        } elseif ($prop == 'command') {
            return 'cd ' . App::$request->server->{'document_root'} . $this->_entry . '/ && ' . (self::$useSudo ? 'sudo ' : '') . $this->_handler . ' index.php ' . App::$request->host . ' / name="'.$this->name.'" key="' . $this->_worker->key . '" worker="' . $this->_worker->Serialize() . '" params="' . $this->_worker->PrepareParams($this->_params) . '"';
        } elseif ($prop == 'request') {
            return $this->_entry . '/?name='.$this->name.'&key=' . $this->_worker->key . '&worker=' . $this->_worker->Serialize() . '&params=' . $this->_worker->PrepareParams($this->_params);
        } elseif ($prop == 'params') {
            return $this->_params;
        } elseif ($prop == 'results') {
            return $this->GetWorkerResults();
        }
        return null;
    }

    /**
     * Magic setter method.
     *
     * @param string $property Property name
     * @param mixed $value Property value
     * @return void
     */
    public function __set(string $property, mixed $value): void
    {
        if ($property === 'params') {
            $this->_params = (object) $value;
        }
    }

    /**
     * Starts the worker process.
     *
     * @param object|null $params Parameters to pass to the process
     * @return void
     */
    public function Run(?object $params = null): void
    {
        if ($params) {
            $this->_params = $params;
        }

        $command = $this->command;
        $request = $this->request;

        if ($this->_debug) {
            App::$log->debug('Executing command');
            App::$log->debug($command);
            App::$log->debug($request);
        }
        $cmd = $command . ' > /dev/null & echo $!';
        $pid = shell_exec($cmd);
        $this->_pid = trim($pid, "\n\r\t ");
    }

    /**
     * Checks if the worker process is running.
     *
     * @return boolean true if running, false if not
     */
    public function IsRunning(): bool
    {
        if ($this->_pid) {
            exec((self::$useSudo ? 'sudo ' : '') . 'ps ' . $this->_pid, $state);
            return count($state) >= 2;
        }
        return false;
    }

    /**
     * Stops the worker process.
     *
     * @return bool true if stopped successfully, false if not
     */
    public function Stop(): bool
    {
        if ($this->IsRunning()) {
            exec((self::$useSudo ? 'sudo ' : '') . 'kill -KILL ' . $this->_pid);
            return $this->IsRunning();
        } else {
            return true;
        }
    }

    /**
     * Checks if a process is running by its PID.
     *
     * @param integer $pid Process ID (PID)
     * @return boolean true if running, false if not
     */
    public static function IsProcessRunning(int $pid): bool
    {
        exec((self::$useSudo ? 'sudo ' : '') . 'ps ' . $pid, $state);
        return (count($state) >= 2);
    }

    /**
     * Kills a process by its PID and returns true if successful, false if not.
     *
     * @param integer $pid Process ID (PID)
     * @return boolean true if killed, false if not
     */
    public static function StopProcess(int $pid): bool
    {
        if (Process::IsProcessRunning($pid)) {
            exec((self::$useSudo ? 'sudo ' : '') . 'kill -KILL ' . $pid);
            return Process::IsProcessRunning($pid);
        } else {
            return true;
        }
    }


    /**
     * Retrieves a Process instance id by name
     *
     * @param string $workerName Name of the worker
     * @return int|null
     */
    public static function PidByWorkerName(string $workerName): ?int
    {
        exec((self::$useSudo ? 'sudo ' : '') . 'ps -ax | grep ' . $workerName, $console);

        $pid = 0;
        $worker = null;
        foreach($console as $line) {
            if(strstr($line, $workerName) !== false && strstr($line, 'index.php') !== false) {
                $line = trim($line);
                $line = preg_replace('/\s+/', ' ', $line);
                $parts = explode(' ', $line);
                $pid = $parts[0];
                break;
            }
        }

        if($pid === 0) {
            return null;
        }

        return $pid;

    }


    /**
     * Retrieves a Process instance by worker name.
     *
     * @param string $workerName Name of the worker
     * @param bool $debug Indicates whether to display the command used to start the worker
     * @param string $entry Entry point for the console stream
     * @return Process|null
     */
    public static function ByWorkerName(string $workerName, bool $debug = false, string $entry = ''): ?Process
    {
        exec((self::$useSudo ? 'sudo ' : '') . 'ps -ax | grep ' . $workerName, $console);

        $pid = 0;
        $worker = null;
        foreach($console as $line) {
            if(strstr($line, $workerName) !== false && strstr($line, 'index.php') !== false) {
                $line = trim($line);
                $line = preg_replace('/\s+/', ' ', $line);
                $parts = explode(' ', $line);
                $pid = $parts[0];
                $worker = VariableHelper::Unserialize(str_replace('worker=', '', $parts[10]));
                $worker->Prepare(str_replace('params=', '', $parts[11]));
                break;
            }
        }

        if($pid === 0) {
            return null;
        }

        return new Process($worker, $debug, $entry, $pid);

    }

}
