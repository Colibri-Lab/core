<?php

/**
 * Доступ к базе данных
 *
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Data
 * @version 1.0.0
 * 
 * Пример: 
 * 
 */

namespace Colibri\Data;

use Colibri\App;
use Colibri\Collections\ArrayList;
use Colibri\Collections\ArrayListIterator;
use Colibri\Common\VariableHelper;
use Colibri\Utils\Config\ConfigException;

/**
 * Класс фабрика для создания точек доступа
 * 
 * @property-read object $accessPoints
 * @property-read array $pool
 * 
 * @method DataAccessPoint[] getIterator()
 * @method DataAccessPoint offsetGet(mixed $offset)
 * @method DataAccessPoint offsetExists(mixed $offset)
 * 
 * @testFunction testDataAccessPoints
 */
class DataAccessPoints implements \ArrayAccess, \IteratorAggregate, \Countable
{

    /**
     * Синглтон
     *
     * @var DataAccessPoints
     */
    public static $instance;

    /**
     * Список точек доступа
     *
     * @var object
     */
    private $_accessPoints;

    /**
     * Список открытых точек доступа
     *
     * @var array
     */
    private array $_accessPointsPool;

    /**
     * Конструктор
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
        }
        catch(ConfigException $e) {
            $this->_accessPoints = []; 
        }

        try {
            $modules = App::$config->Query('modules.entries');
        }
        catch(ConfigException $e) {
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
            }
            catch(ConfigException $e) {

            }
        }

        $this->_accessPoints = VariableHelper::ArrayToObject($this->_accessPoints);

    }

    /**
     * Статический конструктор
     *
     * @return DataAccessPoints
     * @testFunction testDataAccessPointsCreate
     */
    public static function Create()
    {

        if (self::$instance) {
            return self::$instance;
        }
        self::$instance = new DataAccessPoints();
        return self::$instance;
    }

    /**
     * Создает точку доступа
     *
     * @param string $name
     * @return DataAccessPoint
     * @testFunction testDataAccessPointsGet
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

            $database = $accessPointData->database;
            $logqueries = $accessPointData->logqueries ?? false;

            // формируем данные для инициализации точки доступа
            $accessPointInit = (object)[
                'host' => $this->_accessPoints->connections->$accessPointConnection->host,
                'port' => $this->_accessPoints->connections->$accessPointConnection->port,
                'user' => $this->_accessPoints->connections->$accessPointConnection->user,
                'password' => $this->_accessPoints->connections->$accessPointConnection->password,
                'persistent' => (isset($this->_accessPoints->connections->$accessPointConnection->persistent) ? $this->_accessPoints->connections->$accessPointConnection->persistent : false),
                'database' => $database,
                'logqueries' => $logqueries,
                'driver' => $this->_accessPoints->drivers->$accessPointType
            ];

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
    public function offsetSet($offset, $value)
    {
        throw new \RuntimeException();
    }

    /**
     * Проверяет есть ли данные по индексу
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset)
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
    public function offsetUnset($offset)
    {
        throw new \RuntimeException();
    }

    /**
     * Возвращает значение по индексу
     *
     * @param string $offset
     * @return DataAccessPoint
     */
    public function offsetGet($offset)
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

}
