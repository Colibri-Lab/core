<?php

/**
 * Threading
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Threading
 */

namespace Colibri\Threading;

use Colibri\App;

/**
 * Singleton for handling and creating processes.
 *
 */
class Manager
{

    /**
     * Singleton instance.
     *
     * @var Manager
     */
    public static $instance;

    /**
     * Constructor, initiates worker processing if specified.
     */
    public function __construct()
    {
        $this->_processWorkers();
    }

    /**
     * Static function to create Singleton.
     *
     * @return self
     */
    public static function Create(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initiates worker processing.
     *
     * @return void
     */
    private function _processWorkers(): void
    {
        if (App::$request->get->{'worker'}) {
            $worker = Worker::Unserialize(App::$request->get->{'worker'});
            $worker->Prepare(App::$request->get->{'params'});
            $worker->Run();
            exit;
        }
    }

    /**
     * Creates a process for the specified worker.
     *
     * @param Worker $worker The worker to be executed.
     * @param bool $debug Indicates whether to display the worker's execution command.
     * @return Process The created process.
     */
    public function CreateProcess(Worker $worker, bool $debug = false): Process
    {
        return new Process($worker, $debug);
    }
}