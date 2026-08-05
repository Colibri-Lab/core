<?php


/**
 * MySql
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\MySql
 */

namespace Colibri\Data\MySql;

use Colibri\Data\SqlClient\IConnection;
use Colibri\Data\MySql\Exception as MySqlException;

/**
 * Class for connecting to the MySQL database.
 *
 * This class provides methods for establishing and managing connections to a MySQL database.
 *
 * @final
 * @class
 * @implements IConnection 
 *
 * @property-read resource $resource The MySQL connection resource.
 * @property-read resource $raw The raw MySQL connection resource.
 * @property-read resource $connection Alias for $resource.
 * @property-read object $info Connection information.
 * @property-read array $options Connection options.
 * @property-read bool $isAlive Indicates whether the connection to the MySQL server is alive.
 *
 */
final class Connection implements IConnection
{
    /**
     * Connection information object containing host, port, user, password, and database.
     * @var object|null
     * @private
     */
    private $_connectioninfo = null;

    /**
     * The MySQL connection resource.
     * @var \mysqli|null 
     * @private
     */
    private $_resource = null;

    /**
     * Connection constructor.
     *
     * Initializes a new Connection object with the provided connection information.
     *
     * @param string $host The hostname or IP address of the MySQL server.
     * @param string $port The port number of the MySQL server.
     * @param string $user The MySQL username.
     * @param string $password The MySQL password.
     * @param bool $persistent Whether to use a persistent connection (true) or not (false).
     * @param string|null $database (Optional) The name of the default database to connect to.
     * @constructor
     * @public
     */
    public function __construct(string $host, string $port, string $user, string $password, ?bool $persistent = false, ?string $database = null, array|object $options = [])
    {
        $this->_connectioninfo = (object) [
            'host' => $host,
            'port' => $port,
            'user' => $user,
            'password' => $password,
            'persistent' => $persistent,
            'database' => $database,
            'options' => (array)$options
        ];
    }

    /**
     * Destructor for the Connection class.
     *
     * Closes the MySQL connection when the Connection object is destroyed.
     * @public
     */
    public function __destruct()
    {
        $this->Close();
    }

    /**
     * Creates a new Connection instance from the provided connection information.
     *
     * @param object|array $connectionInfo The connection information as an object or array.
     * @return static A new instance of the Connection class.
     * @public
     * @static
     */
    public static function FromConnectionInfo(object|array $connectionInfo): static
    {
        $connectionInfo = (object)$connectionInfo;
        return new static(
            $connectionInfo->host,
            $connectionInfo->port,
            $connectionInfo->user,
            $connectionInfo->password,
            $connectionInfo?->persistent ?? false,
            $connectionInfo?->database ?? null,
            $connectionInfo?->options ?? []
        );
    }

    /**
     * Opens a connection to the MySQL database server.
     *
     * @return bool Returns true if the connection was successful; otherwise, false.
     *
     * @throws MySqlException If an error occurs while establishing the connection.
     * @public
     *
     */
    public function Open(): bool
    {

        if (is_null($this->_connectioninfo)) {
            throw new MySqlException('You must provide a connection info object while creating a connection.');
        }

        try {
            $this->_resource = mysqli_connect(
                ($this->_connectioninfo->persistent ? 'p:' : '') .
                    $this->_connectioninfo->host .
                    ($this->_connectioninfo->port ? ':' . $this->_connectioninfo->port : ''),
                $this->_connectioninfo->user,
                $this->_connectioninfo->password
            );
            if (!$this->_resource) {
                throw new MySqlException(
                    'Connection: ' . $this->_connectioninfo->host . ' ' .
                        $this->_connectioninfo->port . ' ' . $this->_connectioninfo->user . ': ' .
                        mysqli_connect_error()
                );
            }
        } catch (\Throwable $e) {
            throw new MySqlException(
                'Connection: ' . $this->_connectioninfo->host . ' ' .
                $this->_connectioninfo->port . ' ' . $this->_connectioninfo->user . ': ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }

        if (
            !empty($this->_connectioninfo->database) &&
            !mysqli_select_db($this->_resource, $this->_connectioninfo->database)) {
            throw new MySqlException(mysqli_error($this->_resource));
        }

        mysqli_query($this->_resource, 'SET NAMES utf8mb4 COLLATE utf8mb4_general_ci');

        return true;
    }

    /**
     * Reopens the MySQL database connection.
     *
     * This method is an alias for Open().
     *
     * @return bool Returns true if the connection was successfully reopened; otherwise, false.
     * @public
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
     * @public
     *
     */
    public function Close(): void
    {
        if (is_resource($this->_resource)) {
            mysqli_close($this->_resource);
        }
    }

    /**
     * Magic getter method.
     *
     * Allows access to read-only properties such as $resource, $raw, $connection, and $isAlive.
     *
     * @param string $property The name of the property to retrieve.
     * @return mixed Returns the value of the requested property, or null if the property does not exist.
     * @public
     * @magic
     *
     */
    public function __get(string $property): mixed
    {
        switch (strtolower($property)) {
            case "resource":
            case "raw":
            case "connection":
                return $this->_resource;
            case "isAlive":
                return $this->Ping();
            case 'database':
                return $this->_connectioninfo->database;
            case 'host':
                return $this->_connectioninfo->host;
            case 'port':
                return $this->_connectioninfo->port;
            case 'symbol':
                return '`';
            case 'info':
                return $this->_connectioninfo;
            case 'options':
                return $this->_connectioninfo->options;
            default:
                return null;
        }
    }

    /**
     * Checks if the MySQL connection is alive.
     *
     * @return bool Returns true if the connection is alive; otherwise, false.
     * @public
     *
     */
    public function Ping(): bool
    {
        try {
            return mysqli_ping($this->_resource);
        } catch(\Throwable $e) {
            return false;
        }
    }


}
