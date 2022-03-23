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

/**
 * Singleton для обработки и создания процессов
 * @testFunction testManager
 */
class Manager
{

    /**
     * Синглтон
     * @var Manager
     */
    public static $instance;

    /**
     * Конструктор, запускает обработку воркера, если задан
     */
    public function __construct()
    {
        $this->_processWorkers();
    }

    /**
     * Статическая функция создания Singleton
     *
     * @return self
     * @testFunction testManagerCreate
     */
    public static function Create()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Запускает обработку воркера
     *
     * @return void
     * @testFunction testManager_processWorkers
     */
    private function _processWorkers()
    {
        if (App::$request->get->worker) {
            $worker = Worker::Unserialize(App::$request->get->worker);
            $worker->Prepare(App::$request->get->params);
            $worker->Run();
            exit;
        }
    }

    /**
     * Создает процесс для заданного воркера
     *
     * @param Worker $worker воркер, который нужно запустить
     * @param bool $debug отобразить команду запуска воркера
     * @return Process созданный процесс
     * @testFunction testManagerCreateProcess
     */
    public function CreateProcess(Worker $worker, $debug = false)
    {
        return new Process($worker, $debug);
    }
}
