<?php


/**
 * MySql
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\Solr
 */
namespace Colibri\Data\Solr;

use Colibri\Common\StringHelper;
use Colibri\Data\NoSqlClient\IConnection;
use Colibri\Data\Solr\Exception as Exception;
use Colibri\IO\Request\Request;
use SolrClient;

/**
 * Class for connecting to the MySQL database.
 *
 * This class provides methods for establishing and managing connections to a MySQL database.
 *
 * @property-read resource $resource The MySQL connection resource.
 * @property-read resource $raw The raw MySQL connection resource.
 * @property-read resource $connection Alias for $resource.
 * @property-read object $info Alias for $resource.
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
     * @var \SolrClient|null The connection resource.
     */
    private $_resource = null;

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
            
            if(!$this->Ping()) {
                throw new Exception('Can not connect to host or host is not alive', 418);
            }

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
                return $this->Ping();
            case 'host':
                return $this->_connectioninfo->host;
            case 'port':
                return $this->_connectioninfo->port;
            case 'info':
                return $this->_connectioninfo;
            default:
                return null;
        }
    }

    public function Ping(): bool
    {
        $result = Command::Execute($this, 'get', '/admin/cores', ['wt' => 'json'], 'status');
        return $result->QueryInfo()->affected > 0;
    }

    
    public static function AllowedTypes(): array
    {
        return [
            'bool' => ['length' => false, 'generic' => 'bool', 'component' => 'Colibri.UI.Forms.Checkbox'],
            'int' => ['length' => false, 'generic' => 'int', 'component' => 'Colibri.UI.Forms.Number'],
            'bigint' => ['length' => false, 'generic' => 'int', 'component' => 'Colibri.UI.Forms.Number'],
            'float' => ['length' => false, 'generic' => 'float', 'component' => 'Colibri.UI.Forms.Number'],
            'date' => ['length' => false, 'generic' => 'DateField', 'component' => 'Colibri.UI.Forms.Date'],
            'datetime' => ['length' => false, 'generic' => 'DateTimeField', 'component' => 'Colibri.UI.Forms.DateTime'],
            'varchar' => ['length' => false, 'generic' => 'string', 'component' => 'Colibri.UI.Forms.Text'],
            'longtext' => ['length' => false, 'generic' => 'string', 'component' => 'Colibri.UI.Forms.TextArea']
        ];
    }


}
