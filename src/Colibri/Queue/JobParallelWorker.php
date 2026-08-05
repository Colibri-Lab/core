<?php

/**
 * Queue
 *
 * Represents a worker class for parallel job execution.
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Queue
 */

namespace Colibri\Queue;

use Colibri\App;
use Colibri\Common\RandomizationHelper;
use Colibri\Threading\Worker;
use Colibri\Utils\Logs\FileLogger;
use Colibri\Utils\Logs\Logger;

/**
 * Worker class for parallel job execution.
 * @class
 * @extends Worker
 */
class JobParallelWorker extends Worker
{
    /**
     * The logger instance for logging job execution details.
     * @var Logger|null 
     * @private
     */
    private ?Logger $_logger = null;

    /**
     * The job instance being executed by the worker.
     * @var IJob|null 
     * @private
     */
    private ?IJob $_job = null;

    /**
     * Runs the job routine.
     *
     * @suppress PHP0420
     * @public
     */
    public function Run(): void
    {

        $queue = $this->_params->queue;
        $id = $this->_params->id;

        sleep(RandomizationHelper::Integer(1, 5));

        $cache = App::$config->Query('cache')->GetValue();
        $this->_logger = new FileLogger(Logger::Debug, (string)$cache . 'log/queue-' . $queue . '.log', true);
        $this->_logger->info((string)$queue . ':' . $id . ': Begin job routine for parallel');

        $this->_job = Manager::Instance()->GetJobById($id);
        if(!$this->_job) {
            $this->_logger->info((string)$queue . ':' . $id . ': Job not found!');
        }

        $this->_logger->info((string)$queue . ':' . $id . ': Job starts');
        if(!$this->_job->Handle($this->_logger)) {
            $this->_logger->info((string)$queue . ':' . $id . ': Job fails!');
        } else {
            $this->_logger->info((string)$queue . ':' . $id . ': Job success');
        }

    }

}
