<?php

namespace Colibri\Queue;
use Colibri\App;
use Colibri\Common\DateHelper;
use Colibri\Data\DataAccessPoint;
use Colibri\Utils\Logs\FileLogger;
use Colibri\Utils\Logs\Logger;

class Queue
{

    // Redis driver
    const DriverRedis = 'redis';

    private ?DataAccessPoint $_accessPoint = null;

    private string $_name = 'default';

    private ?Logger $_logger = null;

    public function __construct(string $driver, string $name, ?Logger $logger = null)
    {
        $this->_name = $name;
        $this->_logger = $logger ?? new FileLogger('_cache/log/' . $this->_name . '-' . DateHelper::ToDbString(time(), 'YmdHis') . '.log');
        if($driver !== self::DriverRedis) {
            $this->_accessPoint = App::$dataAccessPoints->Get($driver);
        } else {
            // создаем редис клиент
        }

        $this->_createStorage();
    }

    private function _createStorage()
    {
        if($this->_accessPoint) {
            $found = false;
            $tables = $this->_accessPoint->Tables();
            while($table = $tables->Read()) {
                if($table->{array_keys((array)$table)[0]} === 'jobs') {
                    $found = true;
                    break;
                }
            }

            if(!$found) {
                $result = $this->_accessPoint->Query('
                    CREATE TABLE `glavbukh-audit`.`Untitled`  (
                        `id` bigint NOT NULL AUTO_INCREMENT,
                        `datecreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP(),
                        `datemodified` timestamp NOT NULL ON UPDATE CURRENT_TIMESTAMP,
                        `datereserved` timestamp NULL,
                        `queue` varchar(255) NULL,
                        `payload` json NULL,
                        `attempts` int NULL DEFAULT 0,
                        PRIMARY KEY (`id`),
                        INDEX `jobs_datecreated`(`datecreated`),
                        INDEX `jobs_datemodified`(`datemodified`),
                        INDEX `jobs_queue`(`queue`)
                    ) ENGINE = MEMORY;
                ');
                if($result->error) {
                    throw new Exception('Can not create tables');
                }
            }

        }
    }

    public function Add(Job $job): bool
    {
        if($this->_accessPoint) {

        }
    }

}