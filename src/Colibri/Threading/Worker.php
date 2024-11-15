<?php

/**
 * Threading
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @package Colibri\Threading
 *
 */

namespace Colibri\Threading;

use Colibri\App;
use Colibri\Common\RandomizationHelper;
use Colibri\Common\VariableHelper;
use Colibri\IO\FileSystem\File;
use Colibri\Utils\Logs\FileLogger;
use Colibri\Utils\Logs\Logger;
use Colibri\Threading\ErrorCodes;
use Colibri\Utils\Debug;

/**
 * Worker
 *
 * Represents a worker class for managing processes.
 * 
 * This abstract class serves as a base for defining specific actions to be performed in separate processes.
 * 
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @package Colibri\Threading
 */
abstract class Worker
{
    /**
     * Time limit for executing the process.
     *
     * @var integer
     */
    protected int $_timeLimit = 0;

    /**
     * Process priority; requires the presence of 'nohup'.
     *
     * @var integer
     */
    protected int $_prio = 0;

    /**
     * Unique key for identifying the process.
     *
     * @var string
     */
    protected string $_key = '';

    /**
     * Thread ID.
     *
     * @var string
     */
    protected string $_id = '';

    /**
     * Worker's logger.
     *
     * @var Logger
     */
    protected $_log;

    /**
     * Parameters passed to the worker.
     *
     * @var mixed
     */
    protected $_params;

    /**
     * Creates a new instance of the Worker class.
     *
     * @param integer $timeLimit Time limit for executing the worker
     * @param integer $prio Process priority
     * @param string $key Unique key for identifying the process
     */
    public function __construct(int $timeLimit = 0, int $prio = 0, string $key = '')
    {
        $this->_timeLimit = $timeLimit;
        $this->_prio = $prio;

        $this->_key = $key ? $key : uniqid();
        $this->_id = RandomizationHelper::Integer(0, 999999999);

        $cache = App::$config->Query('cache')->GetValue();
        $this->_log = new FileLogger(
            App::$isDev || App::$isLocal ? Logger::Debug : Logger::Error,
            App::$webRoot . $cache . 'log/worker_log_' . $this->_key . '.log'
        ); // лог файл не режется на куски
    }

    /**
     * Runs the worker process.
     * 
     * This method must be implemented in subclasses to define specific actions.
     *
     * @return void
     */
    abstract public function Run(): void;

    /**
     * Getter function to retrieve data related to the worker.
     *
     * @param string $prop Property name
     * @return mixed Property value
     */
    public function __get(string $prop): mixed
    {

        $return = null;
        $prop = strtolower($prop);
        switch ($prop) {
            case 'id':
                $return = $this->_id;
                break;
            case 'timelimit':
                $return = $this->_timeLimit;
                break;
            case 'prio':
                $return = $this->_prio;
                break;
            case 'log':
                $return = $this->_log;
                break;
            case 'key':
                $return = $this->_key;
                break;
            default:
                throw new Exception(ErrorCodes::UnknownProperty, $prop);
        }
        return $return;
    }

    /**
     * Setter function to set data in the worker process.
     *
     * @param string $prop Property name
     * @param mixed $val Property value
     */
    public function __set($prop, $val)
    {
        $prop = strtolower($prop);
        switch ($prop) {
            case 'timelimit':
                $this->_timeLimit = $val;
                break;
            case 'prio':
                $this->_prio = $val;
                break;
            default:
                throw new Exception(ErrorCodes::UnknownProperty, $prop);
        }
    }

    /**
     * Prepares parameters for passing to the process.
     *
     * @param mixed $params Parameters to be serialized
     * @return string Serialized parameters
     */
    public function PrepareParams($params)
    {
        return VariableHelper::Serialize($params);
    }

    /**
     * Parses parameters from a string into an object.
     *
     * @param mixed $params Serialized parameters to be deserialized
     * @return void
     */
    public function Prepare($params)
    {
        $this->_params = VariableHelper::Unserialize($params);
    }

    /**
     * Serializes the worker object.
     *
     * @return string Serialized worker object
     */
    public function Serialize()
    {
        return VariableHelper::Serialize($this);
    }

    /**
     * Deserializes the worker object.
     *
     * @param string $workerString Serialized worker object string
     * @return Worker Deserialized worker object
     */
    public static function Unserialize($workerString)
    {
        return VariableHelper::Unserialize($workerString);
    }

    /**
     * Checks if another instance of the process is running.
     *
     * @return bool true if running, false otherwise
     */
    public function Exists()
    {

        $output = [];
        $code = 0;

        exec((Process::$useSudo ? 'sudo ' : '') . "/bin/ps -auxww | /bin/grep " . $this->_key . " | /bin/grep -v grep", $output, $code);
        if ($code != 0 && $code != 1) {
            return false;
        }
        if (count($output) > 0) {
            return true;
        }

        return false;
    }

    /**
     * Writes results obtained from the worker.
     *
     * @param object $args Results obtained from the worker
     * @return bool true if successfully written, false otherwise
     */
    public function WriteResults(object $args): bool
    {
        $workerDataPath = App::$appRoot . App::$config->Query('runtime')->GetValue() . 'workers/';
        File::Write($workerDataPath . $this->_key, json_encode($args), true, '777');
        return File::Exists($workerDataPath . $this->_key);
    }

}
