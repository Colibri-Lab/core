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
    public function __construct(Worker $worker, $debug = false, $entry = '/.rpc')
    {
        $this->_worker = $worker;
        $this->_debug = $debug;
        $this->_entry = $entry;
    }

    /**
     * Создает Process
     *
     * @param Worker $worker
     * @param bool $debug отобразить команду запуска воркера
     * @return Process
     * @testFunction testProcessCreate
     */
    public static function Create(Worker $worker, $debug = false)
    {
        return new Process($worker, $debug);
    }

    /**
     * Getter
     *
     * @param string $prop свойство
     * @return mixed
     */
    public function __get($prop)
    {
        $prop = strtolower($prop);
        if ($prop == 'pid') {
            return $this->_pid;
        }
        return null;
    }

    /**
     * Запускает Worker
     *
     * @param object $params параметры для передачи в процесс
     * @return void
     * @testFunction testProcessRun
     */
    public function Run($params)
    {
        if($this->_debug) {
            Debug::Out('cd ' . App::$request->server->document_root . $this->_entry . '/ && ' . Process::Handler . ' index.php ' . App::$request->host . ' / key="' . $this->_worker->key . '" worker="' . $this->_worker->Serialize() . '" params="' . $this->_worker->PrepareParams($params) . '"');
            Debug::Out($this->_entry . '/?key=' . $this->_worker->key . '&worker=' . $this->_worker->Serialize() . '&params=' . $this->_worker->PrepareParams($params));
        }
        $pid = shell_exec('cd ' . App::$request->server->document_root . $this->_entry . '/ && ' . Process::Handler . ' index.php ' . App::$request->host . ' / key="' . $this->_worker->key . '" worker="' . $this->_worker->Serialize() . '" params="' . $this->_worker->PrepareParams($params) . '" > '.App::$webRoot.'/_cache/process.log & echo $!');
        $this->_pid = trim($pid, "\n\r\t ");
    }

    /**
     * Проверяет запущен ли Worker
     *
     * @return boolean true если запущен, false если нет
     * @testFunction testProcessIsRunning
     */
    public function IsRunning()
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
    public function Stop()
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
    public static function IsProcessRunning($pid)
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
    public static function StopProcess($pid)
    {
        if (Process::IsProcessRunning($pid)) {
            exec('kill -KILL ' . $pid);
            return Process::IsProcessRunning($pid);
        } else {
            return true;
        }
    }
}
