<?php

namespace Colibri\Queue;
use Colibri\Utils\ExtendedObject;

/**
 * @property Queue $queue Queue where the job must be placed 
 * @property string $name Name of job
 */
class Job extends ExtendedObject 
{

    public function __construct(Queue $queue, string $name)
    {
        parent::__construct([
            'queue' => $queue,
            'name' => $name
        ], '', false);
    }

}