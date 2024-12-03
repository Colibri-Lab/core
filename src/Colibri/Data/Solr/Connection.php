<?php


/**
 * Solr
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\Solr
 */
namespace Colibri\Data\Solr;

use Colibri\Data\SqlClient\IConnection;
use Colibri\Data\Solr\Exception as SolrException;
use SolrClient;

/**
 * Class for connecting to the Solr database.
 *
 * This class provides methods for establishing and managing connections to a Solr database.
 *
 * @property-read resource $resource The Solr connection resource.
 * @property-read resource $raw The raw Solr connection resource.
 * @property-read resource $connection Alias for $resource.
 * @property-read bool $isAlive Indicates whether the connection to the Solr server is alive.
 *
 */
final class Connection implements IConnection
{
    /**
     * @var array Connection information object containing host, port, user, password, and database.
     */
    private ?array $_connectioninfo = null;

    /**
     * @var SolrClient|null The Solr connection resource.
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
     */
    public function __construct(string $host, string $port, string $user, ?string $password, bool $persistent = false, string $database = null)
    {
        $this->_connectioninfo = [
            'hostname' => $host,
            'port' => $port,
            'login' => $user,
            'password' => $password,
            'persistent' => $persistent,
            'path' => $database,
            'wt' => 'json',
        ];

    }

    /**
     * Opens a connection to the MySQL database server.
     *
     * @return bool Returns true if the connection was successful; otherwise, false.
     *
     * @throws SolrException If an error occurs while establishing the connection.
     *
     */
    public function Open(): bool
    {

        if (is_null($this->_connectioninfo)) {
            throw new SolrException('You must provide a connection info object while creating a connection.');
        }

        try {
            $this->_resource = new SolrClient($this->_connectioninfo);
            if (!$this->_resource) {
                throw new SolrException(
                    'Connection: ' . $this->_connectioninfo['host'] . ' ' .
                        $this->_connectioninfo['port'] . ' ' . $this->_connectioninfo['login'] . ': ' .
                        mysqli_connect_error()
                );
            }
        } catch (\Throwable $e) {
            throw new SolrException(
                'Connection: ' . $this->_connectioninfo['host'] . ' ' .
                $this->_connectioninfo['port'] . ' ' . $this->_connectioninfo['login'] . ': ' . $e->getMessage(),
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
        if (is_resource($this->_resource)) {
            $this->_resource->close();
        }
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
            case "isAlive":
                $return = $this->_resource->ping();
                return $return->success();
            case 'database':
                return $this->_connectioninfo['path'];
            case 'host':
                return $this->_connectioninfo['host'];
            case 'port':
                return $this->_connectioninfo['port'];
            case 'symbol':
                return '';
            default:
                return null;
        }
    }
}
