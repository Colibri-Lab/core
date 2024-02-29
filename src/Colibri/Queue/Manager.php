<?php

namespace Colibri\Queue;

use Colibri\App;
use Colibri\Common\DateHelper;
use Colibri\Common\StringHelper;
use Colibri\Data\DataAccessPoint;
use Colibri\Data\SqlClient\Command;
use Colibri\Threading\Process;
use Colibri\Utils\ExtendedObject;
use Colibri\Utils\Logs\FileLogger;
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
                        `reservation_key` varchar(32) NULL,
                        `queue` varchar(255) NULL,
                        `class` varchar(512) NULL,
                        `payload_class` varchar(512) NULL,
                        `payload` json NULL,
                        `attempts` int NULL DEFAULT 0,
                        `parallel` tinyint(1) NULL DEFAULT 0,
                        PRIMARY KEY (`id`),
                        INDEX `'.$this->_storages['list'].'_datecreated`(`datecreated`),
                        INDEX `'.$this->_storages['list'].'_datemodified`(`datemodified`),
                        INDEX `'.$this->_storages['list'].'_datereserved`(`datereserved`),
                        INDEX `'.$this->_storages['list'].'_reservation_key`(`reservation_key`),
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
                        `payload_class` varchar(512) NULL,
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
                        `payload_class` varchar(512) NULL,
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

    public function AddJob(IJob $job): bool
    {
        $accessPoint = App::$dataAccessPoints->Get($this->_driver);

        if($job->id) {
            throw new Exception('Job allready exists, please use Update method');
        }

        $res = $accessPoint->Insert($this->_storages['list'], $job->ToArray());
        if($res->insertid !== -1) {
            $this->id = $res->insertid;
            return true;
        }


        return false;
    }

    public function UpdateJob(IJob $job): bool
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

    public function DeleteJob(IJob $job): bool
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

    public function FailJob(IJob $job, \Throwable $e): bool
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
            'payload_class' => get_class($job->payload),
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

    public function SuccessJob(IJob $job, array|object $result): bool
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
            'payload_class' => get_class($job->payload),
            'payload' => json_encode($job->payload),
            'result' => json_encode($result)
        ]);
        if(!$res->error) {
            return true;
        }
        return false;

    }

    public function GetNextJob(array $queue): ?IJob
    {
        $accessPoint = App::$dataAccessPoints->Get($this->_driver);

        // сначала бронируем и потом уже забираем
        $reservation_key = StringHelper::GUID(false);
        $accessPoint->Query('
            update jobs
            inner join (
                select id
                from jobs 
                where datereserved is null and queue in (\''.implode('\',\'', $queue).'\')
                order by id asc
                limit 1
            ) j2 on jobs.id=j2.id
            set `datereserved`=\''.DateHelper::ToDbString(time()).'\',`reservation_key`=\''.$reservation_key.'\'',
            ['type' => DataAccessPoint::QueryTypeNonInfo]
        );
        
        $reader = $accessPoint->Query(
            'select * from '.$this->_storages['list'].' where reservation_key=\''.$reservation_key.'\' limit 1'
        );
        
        $data = $reader->Read();
        if(!$data) {
            return null;
        }

        $class = $data->class;
        $payloadClass = $data->payload_class ?? 'ExtendedObject';
        $data->payload = new $payloadClass(json_decode($data->payload));
        return new $class($data);
        
    }

    public function GetJobById(int $id): ?IJob
    {
        $accessPoint = App::$dataAccessPoints->Get($this->_driver);
        $reader = $accessPoint->Query(
            'select
                *
            from
                '.$this->_storages['list'].'
            where
                id=\''.$id.'\'
        ');
        $data = $reader->Read();
        if(!$data) {
            return null;
        }
        $class = $data->class;
        $payloadClass = $data->payload_class ?? 'ExtendedObject';
        $data->payload = new $payloadClass(json_decode($data->payload));
        return new $class($data);
    }

    /**
     * @suppress PHP0420
     */
    public function ProcessJobs(string $queue): never
    {

        while(true) {

            $job = Manager::Create()->GetNextJob(explode(',', $queue));
            if(!$job) {
                sleep($this->_config['timeout'] ?? 3);
                continue;
            }

            $cache = App::$config->Query('cache')->GetValue();
            $logger = new FileLogger(Logger::Debug, $cache . 'log/queue-' . $job->queue . '.log', true);
            $logger->info($job->queue . ': Begin job routine');
            
            $logger->info($job->queue . ': ' . $job->id);

            if($job->IsParallel()) {

                $worker = new JobParallelWorker();
                $process = new Process($worker);
                $process->Run((object)['queue' => $job->queue, 'id' => $job->id]);

            } else {
                $logger->info($job->queue . ': Job starts');
                if(!$job->Handle($logger)) {
                    $logger->info($job->queue . ': Job fails!');
                } else {
                    $logger->info($job->queue . ': Job success');
                }
            }

        }
    }


}
