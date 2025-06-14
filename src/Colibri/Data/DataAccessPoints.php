<?php

/**
 * Data
 *
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Data
 */

namespace Colibri\Data;

use Colibri\App;
use Colibri\Common\VariableHelper;
use Colibri\Utils\Config\ConfigException;
use Colibri\Utils\Singleton;

/**
 * Factory class for creating access points.
 *
 * @property-read object $accessPoints The access points.
 * @property-read array $pool The pool.
 *
 * @method DataAccessPoint[] getIterator() Returns an iterator for DataAccessPoint objects.
 * @method DataAccessPoint offsetGet(mixed $offset) Returns the DataAccessPoint object at the specified offset.
 * @method DataAccessPoint offsetExists(mixed $offset) Checks if a DataAccessPoint object exists at the specified offset.
 * 
 * @property-read object $drivers
 *
 */
class DataAccessPoints extends Singleton implements \ArrayAccess, \IteratorAggregate, \Countable
{


    /**
     * The list of access points.
     *
     * @var object
     */
    private $_accessPoints;

    /**
     * The list of open access points.
     *
     * @var array
     */
    private array $_accessPointsPool;

    /**
     * Constructor
     */
    public function __construct()
    {

        $this->_accessPointsPool = [];
        try {
            $this->_accessPoints = App::$config->Query('databases.access-points', (object)[])->AsObject();
            $points = $this->_accessPoints->points ?? [];
            foreach($points as $name => $point) {
                $point->name = $name;
                $point->module = 'application';
            }
        } catch(ConfigException $e) {
            $this->_accessPoints = [];
        }

        try {
            $modules = App::$config->Query('modules.entries');
        } catch(ConfigException $e) {
            $modules = [];
        }

        foreach($modules as $moduleConfig) {
            if(!$moduleConfig->Query('enabled')->GetValue()) {
                continue;
            }

            /** @var \Colibri\Utils\Config\Config $moduleConfig */
            try {

                $keysArray = $moduleConfig->Query('for', [])->ToArray();
                if(!empty($keysArray) && !in_array(App::$domainKey, $keysArray)) {
                    continue;
                }

                $databasesConfig = $moduleConfig->Query('config.databases.access-points')->AsObject();
                $points = $databasesConfig->points ?? [];
                foreach($points as $name => $point) {
                    $point->name = $name;
                    $point->module = $moduleConfig->Query('name')->GetValue();
                }
                $this->_accessPoints = VariableHelper::Extend($this->_accessPoints, $databasesConfig, true);
            } catch(ConfigException $e) {

            }
        }

        $this->_accessPoints = VariableHelper::ArrayToObject($this->_accessPoints);

    }


    /**
     * Creates an access point.
     *
     * @param string $name The name of the access point.
     * @return DataAccessPoint Returns a DataAccessPoint object.
     *
     * @example
     * ```
     * $accessPoint = App::$dataAccessPoints->Get('main');
     * ```
     */
    public function Get(string $name): DataAccessPoint
    {

        if (isset($this->_accessPointsPool[$name])) {
            return $this->_accessPointsPool[$name];
        }

        if (isset($this->_accessPoints->points) && isset($this->_accessPoints->points->$name)) {

            // берем данные точки доступа
            $accessPointData = $this->_accessPoints->points->$name;

            $accessPointConnection = $accessPointData->connection;
            if (!isset($this->_accessPoints->connections->$accessPointConnection)) {
                throw new DataAccessPointsException('Unknown access point connection: '.$accessPointConnection);
            }

            $accessPointType = $this->_accessPoints->connections->$accessPointConnection->type;
            if (!isset($this->_accessPoints->drivers->$accessPointType)) {
                throw new DataAccessPointsException('Unknown access point type: '.$accessPointType);
            }

            $driver = clone $this->_accessPoints->drivers->$accessPointType;
            if($driver?->dbms ?? false) {
                eval('$driver->dbms = '.$driver?->dbms.';');
            } else {
                $driver->dbms = DataAccessPoint::DBMSTypeRelational;
            }
            $dbmsType = $driver->dbms;

            $logqueries = $accessPointData->logqueries ?? null;
            $mindelay = $accessPointData->mindelay ?? null;



            if($dbmsType == DataAccessPoint::DBMSTypeRelational) {


                // формируем данные для инициализации точки доступа
                $accessPointInit = (object)[
                    'host' => $this->_accessPoints->connections->$accessPointConnection?->host,
                    'port' => $this->_accessPoints->connections->$accessPointConnection?->port,
                    'user' => $this->_accessPoints->connections->$accessPointConnection?->user ?? null,
                    'password' => $this->_accessPoints->connections->$accessPointConnection?->password ?? null,
                    'persistent' => (isset($this->_accessPoints->connections->$accessPointConnection?->persistent) ? $this->_accessPoints->connections->$accessPointConnection?->persistent : false),
                    'database' => $accessPointData?->database ?? '',
                    'logqueries' => $logqueries,
                    'mindelay' => $mindelay,
                    'driver' => $driver
                ];

            } else {
                $accessPointInit = VariableHelper::Extend(
                    (array)$this->_accessPoints->connections->$accessPointConnection,
                    [
                        'driver' => $driver,
                        'logqueries' => $logqueries,
                        'mindelay' => $mindelay,
                    ]
                );
                if($accessPointData?->database ?? false) {
                    $accessPointInit['database'] = $accessPointData?->database;
                }
            }

            $return = new DataAccessPoint($accessPointInit);
            $this->_accessPointsPool[$name] = $return;

        } else {
            throw new DataAccessPointsException('Unknown access point type: '.$name);
        }

        return $return;
    }

    public function Key(int $index): ?string
    {
        $keys = array_keys($this->_accessPointsPool);
        return $keys[$index] ?? null;
    }

    public function ItemAt(int $index): DataAccessPoint
    {
        $key = $this->Key($index);
        return $this->_accessPointsPool[$key];
    }

    /**
     * Геттер
     *
     * @param string $property
     * @return void
     */
    public function __get($property): mixed
    {
        $property = strtolower($property);
        $return = null;
        if ($property == 'accesspoints') {
            $return = $this->_accessPoints;
        } elseif  ($property == 'pool') {
            $return = $this->_accessPointsPool;
        } elseif ($property == 'drivers') {
            $return = $this->_accessPoints->drivers;
        } else {
            $return = $this->Get($property);
        }
        return $return;
    }

    /**
     * Устанавливает значение по индексу
     * @param string $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        throw new \RuntimeException();
    }

    /**
     * Проверяет есть ли данные по индексу
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        if (!VariableHelper::IsString($offset)) {
            throw new \InvalidArgumentException();
        }
        return isset($this->pool[$offset]);
    }

    /**
     * удаляет данные по индексу
     * @param string $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        throw new \RuntimeException();
    }

    /**
     * Возвращает значение по индексу
     *
     * @param string $offset
     * @return DataAccessPoint
     */
    public function offsetGet($offset): mixed
    {
        if (!VariableHelper::IsString($offset)) {
            throw new \InvalidArgumentException();
        }
        return $this->pool[$offset];
    }

    public function getIterator(): DataAccessPointIterator
    {
        return new DataAccessPointIterator($this);
    }

    public function Count(): int
    {
        return count($this->pool);
    }

    public function ReopenAll()
    {
        foreach($this as $accessPoint) {
            $accessPoint->Reopen();
        }
    }

}
