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
use Colibri\Utils\Logs\FileLogger;
use Colibri\Utils\Logs\Logger;
use Colibri\Threading\ErrorCodes;
use Colibri\Utils\Debug;

/**
 * Класс работы в процессами, имитирует поток
 * Для работы необходимо наличие php-cli, memcached и ramdisk
 * @testFunction testWorker
 */
abstract class Worker
{

    /**
     * Лимит по времени на выполнение процесса
     *
     * @var integer
     */
    protected int $_timeLimit = 0;

    /**
     * Приоритет процесса, требуется наличие nohup
     *
     * @var integer
     */
    protected int $_prio = 0;

    /**
     * Ключ необходим для идентификации процесса в списке процессов в ps
     *
     * @var string
     */
    protected string $_key = '';

    /**
     * ID потока
     *
     * @var string
     */
    protected string $_id = '';

    /**
     * Лог воркера
     *
     * @var Logger
     */
    protected $_log;

    /**
     * Переданные в воркер параметры
     *
     * @var mixed
     */
    protected $_params;

    /**
     * Создает обьект класса Worker
     *
     * @param integer $timeLimit лимит по времени для выполнения воркера
     * @param integer $prio приоритет, требуется наличие nohup
     * @param string $key ключ процесса (уникальный ключ для того, чтобы в дальнейшем можно было идентифицировать процесс)
     */
    public function __construct(int $timeLimit = 0, int $prio = 0, string $key = '')
    {
        $this->_timeLimit = $timeLimit;
        $this->_prio = $prio;

        $this->_key = $key ? $key : uniqid();
        $this->_id = RandomizationHelper::Integer(0, 999999999);

        $mode = App::$config ? App::$config->Query('mode')->GetValue() : App::ModeDevelopment;
        $this->_log = new FileLogger($mode == App::ModeDevelopment ? Logger::Debug : Logger::Error, App::$webRoot . '_cache/log/worker_log_' . $this->_key. '.log'); // лог файл не режется на куски
    }

    /**
     * Работа по процессу/потоку, необходимо переопределить
     *
     * @return void
     */
    /**
     * @testFunction testWorkerRun
     */
    abstract public function Run() : void;

    /**
     * функция Getter для получения данных по потоку
     *
     * @param string $prop
     * @return mixed
     */
    public function __get(string $prop) : mixed
    {

        $return = null;
        $prop = strtolower($prop);
        switch ($prop) {
            case 'id':
                $return =  $this->_id;
                break;
            case 'timelimit':
                $return =  $this->_timeLimit;
                break;
            case 'prio':
                $return =  $this->_prio;
                break;
            case 'log':
                $return =  $this->_log;
                break;
            case 'key':
                $return =  $this->_key;
                break;
            default:
                throw new Exception(ErrorCodes::UnknownProperty, $prop);
        }
        return $return;
    }

    /**
     * функция Setter для ввода данных в процесс
     *
     * @param string $prop
     * @param mixed $val
     * @testFunction testWorker__set
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
     * Подготавливает параметры к отправке в поток
     *
     * @param mixed $params параметры процесса для сериализации
     * @return string
     * @testFunction testWorkerPrepareParams
     */
    public function PrepareParams($params)
    {
        return VariableHelper::Serialize($params);
    }

    /**
     * Разбирает параметры из строки в объект
     *
     * @param mixed $params параметры процесса для десериализации
     * @return void
     * @testFunction testWorkerPrepare
     */
    public function Prepare($params)
    {
        $this->_params = VariableHelper::Unserialize($params);
    }

    /**
     * Сериализует воркер
     *
     * @return string
     * @testFunction testWorkerSerialize
     */
    public function Serialize()
    {
        return VariableHelper::Serialize($this); 
    }

    /**
     * Десериализует воркер
     *
     * @param string $workerString строка содержащая сериализованный воркер
     * @return Worker десериализованный воркер
     * @testFunction testWorkerUnserialize
     */
    public static function Unserialize($workerString)
    {
        return VariableHelper::Unserialize($workerString);
    }

    /**
     * Проверяет запущен ли другой инстанс процесса
     *
     * @return bool
     * @testFunction testWorkerExists
     */
    public function Exists()
    {

        $output = [];
        $code = 0;

        exec("/bin/ps -auxww | /bin/grep " . $this->_key . " | /bin/grep -v grep", $output, $code);
        if ($code != 0 && $code != 1) {
            return false;
        }
        if (count($output) > 0) {
            return true;
        }

        return false;
    }
}
