<?php


/**
 * MongoDb
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\MongoDb
 */

namespace Colibri\Data\MongoDb;

use Colibri\Common\StringHelper;
use Colibri\Data\NoSqlClient\IConnection;
use Colibri\Data\MongoDb\Exception as Exception;
use Colibri\IO\Request\Request;
use MongoDB\Client as MongoDbClient;
use MongoDB\Database as MongoDbDatabase;

/**
 * Class for connecting to the MySQL database.
 *
 * This class provides methods for establishing and managing connections to a MySQL database.
 *
 * @property-read MongoDbClient $resource The MySQL connection resource.
 * @property-read MongoDbClient $raw The raw MySQL connection resource.
 * @property-read MongoDbClient $connection Alias for $resource.
 * @property-read MongoDbDatabase $database Alias for $resource.
 * @property-read object $info Alias for $resource.
 * @property-read array $options Alias for $resource.
 * @property-read bool $isAlive Indicates whether the connection to the MySQL server is alive.
 *
 */
final class Connection implements IConnection
{
    /**
     * @var object|null Connection information object containing host, port, user, password, and database.
     */
    private $_connectioninfo = null;

    /**
     * @var MongoDbClient|null The connection resource.
     */
    private ?MongoDbClient $_resource = null;

    /**
     * @var MongoDbDatabase|null The database resource.
     */
    private ?MongoDbDatabase $_database = null;

    /**
     * Connection constructor.
     *
     * Initializes a new Connection object with the provided connection information.
     *
     * @param object $connectionInfoObject information about connection.
     */
    public function __construct(object $connectionInfoObject)
    {
        $this->_connectioninfo = $connectionInfoObject;
    }

    public static function FromConnectionInfo(object|array $connectionInfo): static
    {
        return new static((object)$connectionInfo);
    }


    /**
     * Opens a connection to the MySQL database server.
     *
     * @return bool Returns true if the connection was successful; otherwise, false.
     *
     * @throws Exception If an error occurs while establishing the connection.
     *
     */
    public function Open(): bool
    {

        if (is_null($this->_connectioninfo)) {
            throw new Exception('You must provide a connection info object while creating a connection.');
        }

        try {

            $this->_resource = new MongoDbClient('mongodb://'.$this->_connectioninfo->host.':' . $this->_connectioninfo->port, );
            $this->_database = $this->_resource->getDatabase($this->_connectioninfo->database);

        } catch (\Throwable $e) {
            throw new Exception(
                'Connection: ' . $this->_connectioninfo->host . ' ' .
                $this->_connectioninfo->port . ' ' . $this->_connectioninfo->user . ': ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }

        return true;
    }

    /**
     * Reopens the MySQL database connection.
     *
     * This method is an alias for Open().
     *
     * @return bool Returns true if the connection was successfully reopened; otherwise, false.
     *
     */
    public function Reopen(): bool
    {
        return $this->Open();
    }

    /**
     * Closes the MySQL database connection.
     *
     * @return void
     *
     */
    public function Close(): void
    {
        // do nothing
    }

    /**
     * Magic getter method.
     *
     * Allows access to read-only properties such as $resource, $raw, $connection, and $isAlive.
     *
     * @param string $property The name of the property to retrieve.
     * @return mixed Returns the value of the requested property, or null if the property does not exist.
     *
     */
    public function __get(string $property): mixed
    {
        switch (strtolower($property)) {
            case "resource":
            case "raw":
            case "connection":
                return $this->_resource;
            case "database":
                return $this->_database;
            case "isAlive":
                return $this->Ping();
            case 'host':
                return $this->_connectioninfo->host;
            case 'port':
                return $this->_connectioninfo->port;
            case 'info':
                return $this->_connectioninfo;
            case 'options':
                return $this->_connectioninfo->options;
            default:
                return null;
        }
    }

    public function Ping(): bool
    {
        try {
            $names = $this->_database->listCollectionNames();
            return true;
        } catch(\Throwable $e) {
            return false;
        }
    }



}
