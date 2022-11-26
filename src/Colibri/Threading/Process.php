<?php

/**
 * Threading
 * 
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2020 ColibriLab
 * @package Colibri\Threading
 * 
 */

namespace Colibri\Threading;

use Colibri\App;
use Colibri\Utils\Debug;

/**
 * Класс для работы с процессами
 * Внимание: для корректной работы требуется наличие php_cli, memcached и прав на запуск скриптов через shell_exec и exec
 * Внимание: перед использованием проверьте наличие обарботчика php_cli в папке указанной в константе Handler,
 * если у вас обработчик лежит в другом месте, то замените эту переменную
 *
 * Необходимо учитывать, что будет присутствовать 2 объекта класса Worker, первый в источнике потока, второй в самом потоке
 *
 * пример работы:
 *
 * class TestWorker extends Worker { // класс обертка для воркера
 *      public function Run() { // описываем функцию, конкретные действия
 *          for($i=0;$i<10;$i++)
 *              $this->_log->debug('Test Worker run ok...', $i, rout($this->_params));
 *      }
 * }
 *
 * $worker = new TestWorker();
 * $process = new Process($worker); // или Process::Create($worker);
 * $process->Run((object)['blablabla' => 'test']); // запускаем воркер, передаем параметры потоку
 *
 * $workerOutput = array();
 * $worker->log->Open(); // открываем лог воркера
 * while($process->IsRunning()) {  // проверяем работает ли все еще воркер
 *      $workerOutput = array_merge($workerOutput, $worker->log->Read()); // считываем последние сообщения
 *      sleep(1);
 * }
 * $worker->log->Close(); // закрываем лог воркера
 *
 *
 *
 * @testFunction testProcess
 * 
 * @property-read int $pid
 * @property-read string $command
 * @property-read string $request
 * @property array|object $params
 * 
 */
class Process
{

    /**
     * PID процесса Worker-а
     *
     * @var int
     */
    private $_pid;

    /**
     * Worker который нужно запустить
     * 
     * @var Worker
     */
    private $_worker;

    /**
     * Отобразить команду запуска воркера
     * @var bool
     */
    private $_debug;

    /**
     * Точка входа для консольного потока
     * @var string
     */
    private $_entry;

    /**
     * Параметры
     */
    private object $_params;

    /**
     * Обработчик запросов, в большинстве случаев php_cli
     * Если у вас на сервере php_cli лежит в другом месте, необходимо изменить эту переменную
     */
    const Handler = '/usr/bin/php';

    /**
     * Выполняет Worker по имени класса в отдельном потоке
     *
     * @param Worker $worker
     * @param bool $debug отобразить команду запуска воркера
     */
    public function __construct(Worker $worker, bool $debug = false, string $entry = '')
    {
        $this->_worker = $worker;
        $this->_debug = $debug;
        $this->_entry = $entry;
        $this->_params = (object)[];
    }

    /**
     * Создает Process
     *
     * @param Worker $worker
     * @param bool $debug отобразить команду запуска воркера
     * @return Process
     * @testFunction testProcessCreate
     */
    public static function Create(Worker $worker, bool $debug = false) : Process
    {
        return new Process($worker, $debug);
    }

    /**
     * Getter
     *
     * @param string $prop свойство
     * @return mixed
     */
    public function __get(string $prop) : mixed
    {
        $prop = strtolower($prop);
        if ($prop == 'pid') {
            return $this->_pid;
        }
        else if($prop == 'command') {
            return 'cd ' . App::$request->server->document_root . $this->_entry . '/ && ' . Process::Handler . ' index.php ' . App::$request->host . ' / key="' . $this->_worker->key . '" worker="' . $this->_worker->Serialize() . '" params="' . $this->_worker->PrepareParams($this->_params) . '"';
        }
        else if($prop == 'request') {
            return $this->_entry . '/?key=' . $this->_worker->key . '&worker=' . $this->_worker->Serialize() . '&params=' . $this->_worker->PrepareParams($this->_params);
        }
        else if($prop == 'params') {
            return $this->_params;
        }
        return null;
    }

    public function __set(string $property, mixed $value): void
    {
        if($property === 'params') {
            $this->_params = (object)$value;
        }
    }

    /**
     * Запускает Worker
     *
     * @param object $params параметры для передачи в процесс
     * @return void
     * @testFunction testProcessRun
     */
    public function Run(?object $params = null) : void
    {
        if($params) {
            $this->_params = $params;
        }

        $command = $this->command;
        $request = $this->request;

        if($this->_debug) {
            App::$log->debug('Executing command');
            App::$log->debug($command);
            App::$log->debug($request);
        }
        $pid = shell_exec($command . ' > '.App::$webRoot.'/_cache/log/process.log & echo $!');
        $this->_pid = trim($pid, "\n\r\t ");
    }

    /**
     * Проверяет запущен ли Worker
     *
     * @return boolean true если запущен, false если нет
     * @testFunction testProcessIsRunning
     */
    public function IsRunning() : bool
    {
        if ($this->_pid) {
            exec('ps ' . $this->_pid, $state);
            return (count($state) >= 2);
        }
        return false;
    }

    /**
     * Останавливает Worker
     *
     * @return bool true если удалось остановить, false если нет
     * @testFunction testProcessStop
     */
    public function Stop() : bool
    {
        if ($this->IsRunning()) {
            exec('kill -KILL ' . $this->_pid);
            return $this->IsRunning();
        } else {
            return true;
        }
    }

    /**
     * Проверяет живой ли процесс по PID—у
     *
     * @param integer $pid PID процесса
     * @return boolean
     * @testFunction testProcessIsProcessRunning
     */
    public static function IsProcessRunning(int $pid) : bool
    {
        exec('ps ' . $pid, $state);
        return (count($state) >= 2);
    }

    /**
     * Убивает процесс и возвращает true если получилось и false если нет
     *
     * @param integer $pid PID процесса
     * @return boolean
     * @testFunction testProcessStopProcess
     */
    public static function StopProcess(int $pid) : bool
    {
        if (Process::IsProcessRunning($pid)) {
            exec('kill -KILL ' . $pid);
            return Process::IsProcessRunning($pid);
        } else {
            return true;
        }
    }
}
