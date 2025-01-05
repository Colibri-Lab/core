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

/**
 * Class for working with GrayLog.
 *
 * This class extends the abstract Logger class and provides functionality to log messages to a GrayLog server.
 *
 */
class ModelBasedLogger extends Logger
{
    /**
     * Constructor.
     *
     * @param integer $maxLogLevel The maximum log level.
     * @param mixed $device The device information containing the server and port.
     * @throws LoggerException If the device information is invalid.
     */
    public function __construct(int $maxLogLevel = 7, mixed $device = '')
    {
        $this->_maxLogLevel = $maxLogLevel;
        if (!is_object($device) && !is_array($device)) {
            throw new LoggerException('Invalid device information');
        }
        $this->_device = $device;
    }

    /**
     * Writes a log line to GrayLog.
     *
     * @param int $level The log level.
     * @param mixed $data The log data.
     * @return void
     */
    public function WriteLine(int $level, mixed $data): void
    {
        $tableModel = $this->_device?->model ?? null;
        if(!$tableModel) {
            return;
        }
        
        if ($level > $this->_maxLogLevel) {
            return;
        }

        if(is_array($data)) {
            $data = (object)$data;
        }

        $emptyLogMessage = $tableModel::LoadEmpty();
        $emptyLogMessage->level = $level;
        $emptyLogMessage->message = str_replace('\\', '\\\\', is_object($data) ? $data->message : $data);
        if(is_object($data)) {
            $emptyLogMessage->context = $data?->context ?? null;
        } else {
            $emptyLogMessage->context = null;
        }
        $emptyLogMessage->Save(true);

    }

    /**
     * Returns the content of the log file (not applicable for GrayLog).
     *
     * @return mixed null
     */
    public function Content(int $page = 1, int $pagesize = 100, ?string $searchTerm = null, array $filter = [], string $sortField = 'id', string $sortOrder = 'asc'): mixed
    {
        $tableModel = $this->_device?->model ?? null;
        if(!$tableModel) {
            return null;
        }
        return $tableModel::LoadBy($page, $pagesize, $searchTerm, $filter, $sortField, $sortOrder);
    }
}
