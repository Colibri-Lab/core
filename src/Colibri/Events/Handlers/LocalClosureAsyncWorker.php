<?php

/**
 * Handlers
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Events\Handlers
 */

namespace Colibri\Events\Handlers;

use Colibri\Threading\Worker;

/**
 * Async worker for local closures
 * Runs by php-cli
 * 
 * @class
 * @extends Worker
 * 
 */
class LocalClosureAsyncWorker extends Worker
{
    /**
     * Runs the asynchronous worker task.
     * @public
     * @return void
     * 
     * @example
     * ```
     * $worker = new LocalClosureAsyncWorker();
     * $process = App::$processManager->CreateProcess($worker);
     * $process->Run(['closure' => $serializedClosure, 'event' => $event, 'args' => $args]);
     * ```
     * 
     */
    public function Run(): void
    {

        $this->_log->debug('Starting...');
        $localClosure = LocalClosure::Unserialize($this->_params->closure);
        if(!$localClosure) {
            $this->_log->debug('Can not unserialize closure to run...');
        } else {
            $this->_log->debug('Can unserialized successfuly...');
        }

        $this->_log->debug('Running invoke...');
        $args = $this->_params?->args ?? (object)[];
        $args->worker = $this;
        try {
            $result = $localClosure->Invoke($this->_params->event, $args);
        } catch(\Throwable $e) {
            $this->_log->debug('Error running event handler ' . $e->getMessage() . '; line: ' . $e->getLine() . '; file: ' . $e->getFile());
        }

        if($result && ($args?->result ?? null)) {
            $this->WriteResults($args->result);
        }

        $this->_log->debug('Complete...');

    }
}
