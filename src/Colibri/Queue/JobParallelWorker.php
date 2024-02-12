<?php

namespace Colibri\Queue;
use Colibri\App;
use Colibri\Threading\Worker;
use Colibri\Utils\Logs\FileLogger;
use Colibri\Utils\Logs\Logger;

class JobParallelWorker extends Worker
{
    
    /**
     * @suppress PHP0420
     */
    public function Run(): void
    {
        
        $queue = $this->_params->queue;
        $id = $this->_params->id;

        $cache = App::$config->Query('cache')->GetValue();
        $logger = new FileLogger(Logger::Debug, $cache . 'log/queue-' . $queue . '.log', true);
        $logger->info($queue . ':' . $id . ': Begin job routine for parallel');

        $job = Manager::Create()->GetJobById($id);
        if(!$job) {
            $logger->info($queue . ':' . $id . ': Job not found!');
        }

        $logger->info($queue . ':' . $id . ': Job starts');
        if(!$job->Handle($logger)) {
            $logger->info($queue . ':' . $id . ': Job fails!');
        } else {
            $logger->info($queue . ': Job success');
        }

    }

}