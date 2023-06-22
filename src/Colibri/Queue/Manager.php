<?php

namespace Colibri\Queue;
use Colibri\App;
use Colibri\Utils\Logs\Logger;

class Manager 
{

    private array $_config = [];
    private string $_driver = '';
    private array $_storages = [];
    
    public static self $instance;

    public static function Create(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        $this->_config = App::$config->Query('queue', [])->AsArray();
        $this->_driver = $this->_config['access-point'] ?? null;
        $this->_storages = $this->_config['storages'] ?? null;
    }

    public function Migrate(Logger $logger): bool
    {

        if(!$this->_driver || !$this->_storages) {
            return false;
        } 

        $logger->debug('Starting migration of queues');
        $accessPoint = App::$dataAccessPoints->Get($this->_driver);
        if($accessPoint) {
            $listFound = false;
            $successFound = false;
            $errorFound = false;

            $accessPoint->Begin();

            $tables = $accessPoint->Tables();
            while($table = $tables->Read()) {
                if($table->{array_keys((array)$table)[0]} === $this->_storages['list']) {
                    $listFound = true;
                }
                if($table->{array_keys((array)$table)[0]} === ($this->_storages['success'] ?? '')) {
                    $successFound = true;
                }
                if($table->{array_keys((array)$table)[0]} === ($this->_storages['error'] ?? '')) {
                    $errorFound = true;
                }
            }

            $logger->debug('Jobs table found: ' . ($listFound ? 'true' : 'false'));
            if(!$listFound) {
                $result = $accessPoint->Query('
                    CREATE TABLE `'.$this->_storages['list'].'`  (
                        `id` bigint NOT NULL AUTO_INCREMENT,
                        `datecreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP(),
                        `datemodified` timestamp NOT NULL ON UPDATE CURRENT_TIMESTAMP,
                        `datereserved` timestamp NULL,
                        `queue` varchar(255) NULL,
                        `payload` json NULL,
                        `attempts` int NULL DEFAULT 0,
                        PRIMARY KEY (`id`),
                        INDEX `'.$this->_storages['list'].'_datecreated`(`datecreated`),
                        INDEX `'.$this->_storages['list'].'_datemodified`(`datemodified`),
                        INDEX `'.$this->_storages['list'].'_queue`(`queue`)
                    ) ENGINE = MEMORY;
                ');
                if($result->error) {
                    throw new Exception('Can not create tables');
                }
            }
            $logger->debug('Failed jobs table found: ' . ($errorFound ? 'true' : 'false'));
            if(!$errorFound) {
                $result = $accessPoint->Query('
                    CREATE TABLE `'.$this->_storages['error'].'`  (
                        `id` bigint NOT NULL AUTO_INCREMENT,
                        `datecreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP(),
                        `queue` varchar(255) NULL,
                        `payload` json NULL,
                        `exception` json NULL,
                        PRIMARY KEY (`id`),
                        INDEX `'.$this->_storages['error'].'_datecreated`(`datecreated`),
                        INDEX `'.$this->_storages['error'].'_queue`(`queue`)
                    );
                ');
                if($result->error) {
                    throw new Exception('Can not create tables');
                }
            }
            $logger->debug('Successed jobs table found: ' . ($successFound ? 'true' : 'false'));
            if(!$successFound) {
                $result = $accessPoint->Query('
                    CREATE TABLE `'.$this->_storages['success'].'`  (
                        `id` bigint NOT NULL AUTO_INCREMENT,
                        `datecreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP(),
                        `queue` varchar(255) NULL,
                        `payload` json NULL,
                        `result` json NULL,
                        PRIMARY KEY (`id`),
                        INDEX `'.$this->_storages['success'].'_datecreated`(`datecreated`),
                        INDEX `'.$this->_storages['success'].'_queue`(`queue`)
                    );
                ');
                if($result->error) {
                    throw new Exception('Can not create tables');
                }
            }

            $accessPoint->Commit();
        }

        $logger->debug('All complete successfuly');

        return true;

    }

}