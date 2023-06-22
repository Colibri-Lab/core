<?php

namespace Colibri\Queue;
use Colibri\App;
use Colibri\Common\DateHelper;
use Colibri\Data\DataAccessPoint;
use Colibri\Utils\Logs\Logger;

class Manager 
{

    private array $_config = [];
    private string $_driver = '';
    private array $_storages = [];
    
    public static ?self $instance = null;

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
                        `datemodified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP,
                        `datereserved` timestamp NULL,
                        `queue` varchar(255) NULL,
                        `class` varchar(512) NULL,
                        `payload` json NULL,
                        `attempts` int NULL DEFAULT 0,
                        PRIMARY KEY (`id`),
                        INDEX `'.$this->_storages['list'].'_datecreated`(`datecreated`),
                        INDEX `'.$this->_storages['list'].'_datemodified`(`datemodified`),
                        INDEX `'.$this->_storages['list'].'_datereserved`(`datereserved`),
                        INDEX `'.$this->_storages['list'].'_queue`(`queue`),
                        INDEX `'.$this->_storages['list'].'_class`(`class`)
                    );
                ', ['type' => DataAccessPoint::QueryTypeNonInfo]);
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
                        `class` varchar(512) NULL,
                        `payload` json NULL,
                        `exception` json NULL,
                        PRIMARY KEY (`id`),
                        INDEX `'.$this->_storages['error'].'_datecreated`(`datecreated`),
                        INDEX `'.$this->_storages['error'].'_queue`(`queue`),
                        INDEX `'.$this->_storages['list'].'_class`(`class`)
                    );
                ', ['type' => DataAccessPoint::QueryTypeNonInfo]);
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
                        `class` varchar(512) NULL,
                        `payload` json NULL,
                        `result` json NULL,
                        PRIMARY KEY (`id`),
                        INDEX `'.$this->_storages['success'].'_datecreated`(`datecreated`),
                        INDEX `'.$this->_storages['success'].'_queue`(`queue`),
                        INDEX `'.$this->_storages['list'].'_class`(`class`)
                    );
                ', ['type' => DataAccessPoint::QueryTypeNonInfo]);
                if($result->error) {
                    throw new Exception('Can not create tables');
                }
            }

            $accessPoint->Commit();
        }

        $logger->debug('All complete successfuly');

        return true;

    }

    public function AddJob(Job $job): bool
    {
        $accessPoint = App::$dataAccessPoints->Get($this->_driver);

        if($job->id) {
            throw new Exception('Job allready exists, please use Update method');
        }

        $res = $accessPoint->Insert($this->_storages['list'], $job->ToArray());
        if($res->insertid !== -1) {
            return true;
        }

        return false;
    }

    public function UpdateJob(Job $job): bool
    {

        $accessPoint = App::$dataAccessPoints->Get($this->_driver);

        if(!$job->id) {
            throw new Exception('Job does not exists, please use Add method');
        }

        $res = $accessPoint->Update($this->_storages['list'], $job->ToArray(), 'id='.$job->id);
        if(!$res->error) {
            return true;
        }
        return false;
    }

    public function DeleteJob(Job $job): bool
    {
        $accessPoint = App::$dataAccessPoints->Get($this->_driver);

        if(!$job->id) {
            throw new Exception('Job does not exists, can not delete');
        }

        $res = $accessPoint->Delete($this->_storages['list'], 'id='.$job->id);
        if(!$res->error) {
            return true;
        }
        return false;
    }

    public function FailJob(Job $job, \Throwable $e): bool
    {
        $accessPoint = App::$dataAccessPoints->Get($this->_driver);

        if(!$job->id) {
            throw new Exception('Job does not exists, can not delete');
        }

        if(!$this->_storages['error']) {
            return true;
        }

        $res = $accessPoint->Insert($this->_storages['error'], [
            'queue' => $job->queue,
            'class' => $job->class,
            'payload' => json_encode($job->payload),
            'exception' => json_encode([
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'trace' => $e->getTrace()
            ])
        ]);
        if(!$res->error) {
            return true;
        }
        return false;

    }

    public function SuccessJob(Job $job, array|object $result): bool
    {
        $accessPoint = App::$dataAccessPoints->Get($this->_driver);

        if(!$job->id) {
            throw new Exception('Job does not exists, can not delete');
        }

        if(!$this->_storages['success']) {
            return true;
        }

        $res = $accessPoint->Insert($this->_storages['success'], [
            'queue' => $job->queue,
            'class' => $job->class,
            'payload' => json_encode($job->payload),
            'result' => json_encode($result)
        ]);
        if(!$res->error) {
            return true;
        }
        return false;

    }

    public function GetNextJob(string $queue): Job
    {
        $accessPoint = App::$dataAccessPoints->Get($this->_driver);
        $reader = $accessPoint->Query('select * from '.$this->_storages['list'].' where not datereserved is null order by id limit 1');
        $data = $reader->Read();
        $class = $data->class;
        $job = new $class($data);
        return $job;
    }

    public function ProcessJobs(string $queue): void
    {

        $worker = new JobWorker();
        $process = App::$threadingManager->CreateProcess($worker);
        $process->Run((object)[
            'queue' => $queue
        ]);

        if(!$process->IsRunning()) {
            throw new Exception('Can not start process for queue: ' . $queue);
        }

    }
    

}