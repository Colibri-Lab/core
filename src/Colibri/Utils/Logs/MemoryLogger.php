<?php

/**
 * Logs
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2020 ColibriLab
 * @package Colibri\Utils\Logs
 *
 */

namespace Colibri\Utils\Logs;

use Colibri\Common\DateHelper;
use DateTime;

/**
 * Class for working with in-memory logs
 */
class MemoryLogger extends Logger
{
    /**
     * Constructor
     *
     * @param integer $maxLogLevel
     * @param array $dummy
     */
    public function __construct(int $maxLogLevel = 7, mixed $dummy = [])
    {
        $this->_maxLogLevel = $maxLogLevel;
        $this->_device = [];
    }

    /**
     * Writes data to the log
     *
     * @param int $level error level
     * @param mixed $data data
     * @return void
     */
    public function WriteLine(int $level, mixed $data): void
    {
        $now = DateTime::createFromFormat('U.u', microtime(true));
        if (!$now) {
            return;
        }
        $now = $now->format("m-d-Y H:i:s.u");

        $args = !is_array($data) ? [$data] : $data;
        if (isset($args['context'])) {
            $args['context'] = implode("\t", $args['context']);
        }
        $args = implode("\t", $args);
        $args = $now . "\t" . $args;
        $this->_device[] = $args;
    }

    /**
     * Returns the content of the log file
     *
     * @return mixed
     */
    public function Content(): mixed
    {
        return $this->_device;
    }
}
