<?php

namespace Colibri\Queue;
use Colibri\Threading\Worker;

class JobWorker extends Worker
{
    
    /**
     * @suppress PHP0420
     */
    public function Run(): void
    {
        
        $queue = $this->_params->queue;

        $this->_log->info($queue . ': Begin job routine');
        while(true) {

            $job = Manager::Create()->GetNextJob($queue);
            if(!$job) {
                sleep(10);
                continue;
            }

            $this->_log->info($queue . ': Job starts');
            if(!$job->Handle()) {
                $this->_log->info($queue . ': Job fails!');
            } else {
                $this->_log->info($queue . ': Job success');
            }
        }
        $this->_log->info($queue . ': Job routine ends');

    }

}