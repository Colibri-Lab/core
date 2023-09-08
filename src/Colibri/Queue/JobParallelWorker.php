<?php

namespace Colibri\Queue;
use Colibri\Threading\Worker;

class JobParallelWorker extends Worker
{
    
    /**
     * @suppress PHP0420
     */
    public function Run(): void
    {
        
        $queue = $this->_params->queue;
        $id = $this->_params->id;

        $this->_log->info($queue . ':' . $id . ': Begin job routine for parallel');

        $job = Manager::Create()->GetJobById($id);
        if(!$job) {
            $this->_log->info($queue . ':' . $id . ': Job not found!');
        }

        $this->_log->info($queue . ':' . $id . ': Job starts');
        if(!$job->Handle($this->_log)) {
            $this->_log->info($queue . ':' . $id . ': Job fails!');
        } else {
            $this->_log->info($queue . ': Job success');
        }

    }

}