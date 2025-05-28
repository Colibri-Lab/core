<?php


/**
 * Queue
 *
 * Manages the job queue.
 *
 * This class provides functionality for managing jobs in the queue, including adding, updating, deleting,
 * and processing jobs, as well as migrating the queue and retrieving dashboard data.
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Queue
 */

namespace Colibri\Queue;

use Colibri\App;
use Colibri\Common\DateHelper;
use Colibri\Common\StringHelper;
use Colibri\Data\DataAccessPoint;
use Colibri\Data\SqlClient\Command;
use Colibri\Data\Storages\Fields\DateTimeField;
use Colibri\Events\EventsContainer;
use Colibri\Events\TEventDispatcher;
use Colibri\Threading\Process;
use Colibri\Utils\ExtendedObject;
use Colibri\Utils\Logs\FileLogger;
use Colibri\Utils\Logs\Logger;

/**
 * Manages the job queue.
 */
class Manager
{
    use TEventDispatcher;

    /**
     * Array containing configuration settings.
     *
     * @var array
     */
    private array $_config = [];

    /**
     * The driver used for accessing the queue.
     *
     * @var string
     */
    private string $_driver = '';

    /**
     * Array containing storage settings.
     *
     * @var array
     */
    private array $_storages = [];

    /**
     * The instance of the Manager class.
     *
     * @var Manager|null
     */
    public static ?self $instance = null;

    /**
     * Creates an instance of the Manager class.
     *
     * @return self The created instance of the Manager class.
     */
    public static function Create(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor for the Manager class.
     */
    public function __construct()
    {
        $this->_config = App::$config->Query('queue', [])->AsArray();
        $this->_driver = $this->_config['access-point'] ?? null;
        $this->_storages = $this->_config['storages'] ?? null;
    }

    /**
     * Migrates the job queue.
     *
     * @param Logger $logger The logger instance.
     * @return bool True if migration was successful, false otherwise.
     * @throws Exception When tables cannot be created.
     */
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
                        `datestart` timestamp NULL,
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
                        INDEX `'.$this->_storages['list'].'_datestart`(`datestart`),
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

    /**
     * Adds a job to the queue.
     *
     * @param IJob $job The job to add.
     * @param string|null $startDate The start date of the job.
     * @return bool True if the job is added successfully, false otherwise.
     * @throws Exception If the job already exists.
     */
    public function AddJob(IJob $job, ?string $startDate = null): bool
    {
        $accessPoint = App::$dataAccessPoints->Get($this->_driver);

        if($job->id) {
            throw new Exception('Job allready exists, please use Update method');
        }

        if(!$startDate) {
            $startDate = (string)(new DateTimeField('now'));
        }

        $job->SetHeaders();

        $jobArray = $job->ToArray();
        $jobArray['datestart'] = $startDate;

        $res = $accessPoint->Insert($this->_storages['list'], $jobArray);
        if($res->insertid !== -1) {

            $this->DispatchEvent('JobAdded', ['id' => $res->insertid]);

            $job->id = $res->insertid;

            if(!self::IsRunning($job->queue)) {
                runx(App::$appRoot . 'bin/queue', [$job->queue]);
            }

            return true;
        }


        return false;
    }

    /**
     * Updates a job in the queue.
     *
     * @param IJob $job The job to update.
     * @param string|null $startDate The start date of the job.
     * @return bool True if the job is updated successfully, false otherwise.
     * @throws Exception If the job does not exist.
     */
    public function UpdateJob(IJob $job, ?string $startDate = null): bool
    {

        $accessPoint = App::$dataAccessPoints->Get($this->_driver);

        if(!$job->id) {
            throw new Exception('Job does not exists, please use Add method');
        }

        $job->SetHeaders();
        $jobArray = $job->ToArray();
        if($startDate) {
            $jobArray['datestart'] = $startDate;
        }

        $res = $accessPoint->Update($this->_storages['list'], $jobArray, 'id='.$job->id);
        if(!$res->error) {
            $this->DispatchEvent('JobUpdated', ['id' => $job->id]);

            return true;
        }
        return false;
    }

    /**
     * Deletes a job from the queue.
     *
     * @param IJob $job The job to delete.
     * @return bool True if the job is deleted successfully, false otherwise.
     * @throws Exception If the job does not exist.
     */
    public function DeleteJob(IJob $job): bool
    {
        $accessPoint = App::$dataAccessPoints->Get($this->_driver);

        if(!$job->id) {
            throw new Exception('Job does not exists, can not delete');
        }

        $res = $accessPoint->Delete($this->_storages['list'], 'id='.$job->id);
        if(!$res->error) {
            $this->DispatchEvent('JobDeleted', ['id' => $job->id]);
            return true;
        }
        return false;
    }

    /**
     * Marks a job as failed in the queue.
     *
     * @param IJob $job The job that failed.
     * @param \Throwable $e The exception that caused the job to fail.
     * @return bool True if the job is marked as failed successfully, false otherwise.
     * @throws Exception If the job does not exist.
     */
    public function FailJob(IJob $job, \Throwable $e): bool
    {
        $accessPoint = App::$dataAccessPoints->Get($this->_driver);

        if(!$job->id) {
            throw new Exception('Job does not exists, can not delete');
        }

        if(!$this->_storages['error']) {
            return true;
        }

        $this->DispatchEvent('JobFailed', ['id' => $job->id]);

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

    /**
     * Marks a job as successfully completed in the queue.
     *
     * @param IJob $job The job that was successfully completed.
     * @param array|object $result The result of the job execution.
     * @return bool True if the job is marked as successfully completed, false otherwise.
     * @throws Exception If the job does not exist.
     */
    public function SuccessJob(IJob $job, array|object $result): bool
    {
        $accessPoint = App::$dataAccessPoints->Get($this->_driver);

        if(!$job->id) {
            throw new Exception('Job does not exists, can not delete');
        }

        if(!$this->_storages['success']) {
            return true;
        }

        $this->DispatchEvent('JobSuccesed', ['id' => $job->id]);

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

    public function UpdateSuccessedJob(int $id, object|array $result): mixed
    {
        $accessPoint = App::$dataAccessPoints->Get($this->_driver);
        return $accessPoint->Update($this->_storages['success'], ['result' => json_encode(['result' => $result])], 'id='.$id);
    }

    /**
     * Retrieves the next job from the specified queue.
     *
     * @param array $queue The queue from which to retrieve the next job.
     * @return IJob|null The next job if available, or null if no jobs are available in the queue.
     */
    public function GetNextJob(array $queue): ?IJob
    {
        $accessPoint = App::$dataAccessPoints->Get($this->_driver);

        // сначала бронируем и потом уже забираем
        $reservation_key = StringHelper::GUID(false);
        $accessPoint->Query(
            '
            update jobs
            inner join (
                select id
                from jobs
                where datereserved is null and queue in (\''.implode('\',\'', $queue).'\') and
                    datestart<=\''.(string)(new DateTimeField('now')).'\'
                order by id asc
                limit 1
            ) j2 on jobs.id=j2.id
            set `datereserved`=\''.(string)(new DateTimeField('now')).'\',`reservation_key`=\''.$reservation_key.'\'',
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
        $payloadContent = (array)json_decode($data->payload);
        $payloadData = $payloadContent['data'];
        $headers = (array)$payloadContent['headers'];
        if(!empty($headers)) {
            App::$request->ModifyHeaders($headers);
        }
        $data->payload = new $payloadClass($payloadData);
        return new $class($data);

    }

    /**
     * Retrieves a job from the queue by its ID.
     *
     * @param int $id The ID of the job to retrieve.
     * @return IJob|null The job if found, or null if no job with the specified ID exists.
     */
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
        '
        );
        $data = $reader->Read();
        if(!$data) {
            return null;
        }
        $class = $data->class;
        $payloadClass = $data->payload_class ?? 'ExtendedObject';
        $payloadContent = (array)json_decode($data->payload);
        $payloadData = $payloadContent['data'];
        $headers = (array)$payloadContent['headers'];
        if(!empty($headers)) {
            App::$request->ModifyHeaders($headers);
        }
        $data->payload = new $payloadClass($payloadData);
        return new $class($data);
    }

    /**
     * Checks the job exists and is running
     *
     * @param string $class The job class
     * @param ?string $payloadClass The job payload class
     * @param array|object|null $payloadFilter object of payload to check
     * @return boolean true if the job exists and running, false if job exists
     *                 and not running, and null if job does not exists
     */
    public function JobIsRunning(string $class, ?string $payloadClass, object|array|null $payloadFilter = null, string|array $dateStart = null): ?bool
    {
        $accessPoint = App::$dataAccessPoints->Get($this->_driver);

        $pfilter = [];
        if($payloadFilter) {
            foreach($payloadFilter as $key => $value) {
                if(is_string($value)) {
                    $value = '\'' . $value . '\'';
                } elseif (is_bool($value)) {
                    $value = 'CAST(\''.($value ? 'true' : 'false').'\' AS JSON)';
                } elseif (is_null($value)) {
                    $value = 'CAST(\'null\' AS JSON)';
                }
                $pfilter[] = 'JSON_EXTRACT(payload, \'$.data.'.$key.'\')=' . $value;
            }
        }

        $dateFilter = '';
        if($dateStart !== null) {
            if(is_array($dateStart)) {
                $dateFilter = 'datestart between \'' . $dateStart[0] . '\' and \'' . $dateStart[1] . '\'';
            } else {
                $dateFilter = 'Date(datestart) = \''.$dateStart.'\'';
            }
        }

        $reader = $accessPoint->Query(
            'select
                *
            from
                '.$this->_storages['list'].'
            where
                class=\''.str_replace('\\', '\\\\', $class).'\''.
                ($payloadClass ? ' and payload_class=\''.str_replace('\\', '\\\\', $payloadClass).'\'' : '') .
                ($dateFilter ? ' and ' . $dateFilter : '') .
                (!empty($pfilter) ? ' and ' . implode(' and ', $pfilter) : '').'
        '
        );
        $data = $reader->Read();
        if(!$data) {
            return null;
        }

        return $data->reservation_key !== null;
    }

    /**
     * Find job
     *
     * @param string $class The job class
     * @param ?string $payloadClass The job payload class
     * @param array|object|null $payloadFilter object of payload to check
     * @return [IJob]|null
     */
    public function FindJob(string $class, ?string $payloadClass, object|array|null $payloadFilter = null, string|array $dateStart = null): ?array
    {
        $accessPoint = App::$dataAccessPoints->Get($this->_driver);

        $pfilter = [];
        if($payloadFilter) {
            foreach($payloadFilter as $key => $value) {
                if(is_string($value)) {
                    $value = '\'' . $value . '\'';
                } elseif (is_bool($value)) {
                    $value = 'CAST(\''.($value ? 'true' : 'false').'\' AS JSON)';
                } elseif (is_null($value)) {
                    $value = 'CAST(\'null\' AS JSON)';
                }
                $pfilter[] = 'JSON_EXTRACT(payload, \'$.data.'.$key.'\')=' . $value;
            }
        }

        $dateFilter = '';
        if($dateStart !== null) {
            if(is_array($dateStart)) {
                $dateFilter = 'datestart between \'' . $dateStart[0] . '\' and \'' . $dateStart[1] . '\'';
            } else {
                $dateFilter = 'Date(datestart) = \''.$dateStart.'\'';
            }
        }

        $reader = $accessPoint->Query(
            'select
                *
            from
                '.$this->_storages['list'].'
            where
                class=\''.str_replace('\\', '\\\\', $class).'\''.
                ($payloadClass ? ' and payload_class=\''.str_replace('\\', '\\\\', $payloadClass).'\'' : '') .
                ($dateFilter ? ' and ' . $dateFilter : '') .
                (!empty($pfilter) ? ' and ' . implode(' and ', $pfilter) : '').'
        '
        );
        if($reader->Count() == 0) {
            return null;
        }

        $ret = [];
        while($data = $reader->Read()) {
            $ret[] = $class::Create(
                new $payloadClass(json_decode($data->payload)),
                $data->queue,
                $data->attempts,
                $data->parallel,
                $data->id
            );
        }

        return $ret;
    }

    /**
     * Find job in specific queue
     *
     * @param string $class The job class
     * @param ?string $payloadClass The job payload class
     * @param array|object|null $payloadFilter object of payload to check
     * @return [IJob]|null
     */
    public function FindJobInQueue(string $queue, string $class, ?string $payloadClass, object|array|null $payloadFilter = null, string|array $dateStart = null): ?array
    {
        $accessPoint = App::$dataAccessPoints->Get($this->_driver);

        $pfilter = [];
        if($payloadFilter) {
            foreach($payloadFilter as $key => $value) {
                if(is_string($value)) {
                    $value = '\'' . $value . '\'';
                } elseif (is_bool($value)) {
                    $value = 'CAST(\''.($value ? 'true' : 'false').'\' AS JSON)';
                } elseif (is_null($value)) {
                    $value = 'CAST(\'null\' AS JSON)';
                }
                $pfilter[] = 'JSON_EXTRACT(payload, \'$.data.'.$key.'\')=' . $value;
            }
        }

        $dateFilter = '';
        if($dateStart !== null) {
            if(is_array($dateStart)) {
                $dateFilter = 'datestart between \'' . $dateStart[0] . '\' and \'' . $dateStart[1] . '\'';
            } else {
                $dateFilter = 'Date(datestart) = \''.$dateStart.'\'';
            }
        }

        $reader = $accessPoint->Query(
            'select
                *
            from
                '.$this->_storages['list'].'
            where
                queue=\''.$queue.'\' and 
                class=\''.str_replace('\\', '\\\\', $class).'\''.
                ($payloadClass ? ' and payload_class=\''.str_replace('\\', '\\\\', $payloadClass).'\'' : '') .
                ($dateFilter ? ' and ' . $dateFilter : '') .
                (!empty($pfilter) ? ' and ' . implode(' and ', $pfilter) : '').'
        '
        );
        if($reader->Count() == 0) {
            return null;
        }

        $ret = [];
        while($data = $reader->Read()) {
            $ret[] = $class::Create(
                new $payloadClass(json_decode($data->payload)),
                $data->queue,
                $data->attempts,
                $data->parallel,
                $data->id
            );
        }

        return $ret;
    }

    public function GetSuccessed(string $class, ?string $payloadClass, object|array|null $payloadFilter = null, object|array|null $resultFilter = null): array
    {
        $accessPoint = App::$dataAccessPoints->Get($this->_driver);

        $pfilter = [];
        if($payloadFilter) {
            foreach($payloadFilter as $key => $value) {
                if(is_string($value)) {
                    $value = '\'' . $value . '\'';
                } elseif (is_bool($value)) {
                    $value = 'CAST(\''.($value ? 'true' : 'false').'\' AS JSON)';
                } elseif (is_null($value)) {
                    $value = 'CAST(\'null\' AS JSON)';
                }
                $pfilter[] = 'JSON_EXTRACT(payload, \'$.'.$key.'\')=' . $value;
            }
        }
        $rfilter = [];
        if($resultFilter) {
            foreach($resultFilter as $key => $value) {
                if(is_string($value)) {
                    $value = '\'' . $value . '\'';
                } elseif (is_bool($value)) {
                    $value = 'CAST(\''.($value ? 'true' : 'false').'\' AS JSON)';
                } elseif (is_null($value)) {
                    $value = 'CAST(\'null\' AS JSON)';
                }
                $rfilter[] = 'JSON_EXTRACT(result, \'$.result.'.$key.'\')=' . $value;
            }
        }

        $reader = $accessPoint->Query(
            'select
                *
            from
                '.$this->_storages['success'].'
            where
                class=\''.str_replace('\\', '\\\\', $class).'\''.
                ($payloadClass ? ' and payload_class=\''.str_replace('\\', '\\\\', $payloadClass).'\'' : '') .
                (!empty($pfilter) ? ' and ' . implode(' and ', $pfilter) : '').
                (!empty($rfilter) ? ' and ' . implode(' and ', $rfilter) : '').
            '
            order by datecreated desc
            '
        );

        $d= [];
        while($data = $reader->Read()) {
            $d[] = $data;
        }
        return $d;

    }

    /**
     * Processes jobs from the specified queue indefinitely.
     *
     * @param string $queue The queue to process jobs from.
     * @return never This method never returns.
     * @suppress PHP0420
     */
    public function ProcessJobs(string $queue): never
    {
        $activeParallelProcesses = [];
        while(true) {

            $job = Manager::Create()->GetNextJob(explode(',', $queue));
            if(!$job) {
                foreach($activeParallelProcesses as $index => $process) {
                    if(!$process->IsRunning()) {
                        $this->DispatchEvent(EventsContainer::ParallelJobIsEnded, ['process' => $process]);
                        array_splice($activeParallelProcesses, $index, 1);
                    }
                }
                sleep($this->_config['timeout'] ?? 3);
                continue;
            }

            $cache = App::$config->Query('cache')->GetValue();
            $logger = new FileLogger(Logger::Debug, $cache . 'log/queue-' . $job->queue . '.log', true);
            $logger->info($job->queue . ': Begin job routine');

            $logger->info($job->queue . ': ' . $job->id);

            $this->DispatchEvent('JobStarted', ['id' => $job->id]);

            if($job->IsParallel()) {

                $worker = new JobParallelWorker(0, 0, $job->Key());
                $process = new Process($worker);
                $process->Run((object)['queue' => $job->queue, 'id' => $job->id]);
                $activeParallelProcesses[] = $process;

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

    /**
     * Retrieves dashboard data including active, error, and success jobs.
     *
     * @return array An array containing dashboard data.
     */
    public function Dashboard(): array
    {

        $ret = [
            'active' => [],
            'errors' => [],
            'success' => []
        ];

        $accessPoint = App::$dataAccessPoints->Get($this->_driver);
        $reader = $accessPoint->Query(
            'select
                *
            from
                '.$this->_storages['list'].'
            order by id
            limit 10
        '
        );
        while($d = $reader->Read()) {
            $ret['active'][] = $d;
        }

        $reader = $accessPoint->Query(
            'select
                *
            from
                '.$this->_storages['success'].'
            order by id desc
            limit 10
        '
        );
        while($d = $reader->Read()) {
            $ret['success'][] = $d;
        }

        $reader = $accessPoint->Query(
            'select
                *
            from
                '.$this->_storages['error'].'
            order by id desc
            limit 10
        '
        );
        while($d = $reader->Read()) {
            $ret['errors'][] = $d;
        }

        return $ret;
    }


    /**
     * Retrieves when queue is running
     *
     * @param string $queueName Name of the worker
     * @return bool
     */
    public static function IsRunning(string $queueName): bool
    {
        exec('ps -ax | grep "command=queue"', $console);

        foreach($console as $line) {
            if(strstr($line, $queueName . ',') !== false || strstr($line, ',' . $queueName) !== false || strstr($line, '=' . $queueName) !== false) {
                return true;
            }
        }
        return false;
    }

    public static function Run(string|array $queueName)
    {
        runx(App::$appRoot . 'bin/queue', [is_string($queueName) ? $queueName : implode(',', $queueName)]);
    }


}
