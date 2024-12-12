<?php


/**
 * Sphinx
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\Sphinx
 */
namespace Colibri\Data\Sphinx;

use Colibri\Data\SqlClient\IConnection;
use Colibri\Data\Sphinx\Exception as SphinxException;

/**
 * Class for connecting to the Sphinx database.
 *
 * This class provides methods for establishing and managing connections to a Sphinx database.
 *
 * @property-read resource $resource The Sphinx connection resource.
 * @property-read resource $raw The raw Sphinx connection resource.
 * @property-read resource $connection Alias for $resource.
 * @property-read object $info Connection information.
 * @property-read array $options Connection options.
 * @property-read bool $isAlive Indicates whether the connection to the Sphinx server is alive.
 *
 */
final class Connection implements IConnection
{
    /**
     * @var object|null Connection information object containing host, port, user, password, and database.
     */
    private $_connectioninfo = null;

    /**
     * @var \mysqli|null The MySQL connection resource.
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
    public function __construct(string $host, string $port, ?string $user, ?string $password, ?bool $persistent = false, ?string $database = null, array|object $options = [])
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
     * @throws SphinxException If an error occurs while establishing the connection.
     *
     */
    public function Open(): bool
    {

        if (is_null($this->_connectioninfo)) {
            throw new SphinxException('You must provide a connection info object while creating a connection.');
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
                throw new SphinxException(
                    'Connection: ' . $this->_connectioninfo->host . ' ' .
                        $this->_connectioninfo->port . ' ' . $this->_connectioninfo->user . ': ' .
                        mysqli_connect_error()
                );
            }
        } catch (\Throwable $e) {
            throw new SphinxException(
                'Connection: ' . $this->_connectioninfo->host . ' ' .
                $this->_connectioninfo->port . ' ' . $this->_connectioninfo->user . ': ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }

        if (
            !empty($this->_connectioninfo->database) &&
            !mysqli_select_db($this->_resource, $this->_connectioninfo->database)) {
            throw new SphinxException(mysqli_error($this->_resource));
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

    public function Ping(): bool {
        return mysqli_ping($this->_resource);
    }

    public static function AllowedTypes(): array
    {
        return [
            'bigint' => ['length' => false, 'generic' => 'int', 'component' => 'Colibri.UI.Forms.Number', 'index' => true],
            'bool' => ['length' => false, 'generic' => 'bool', 'component' => 'Colibri.UI.Forms.Checkbox', 'db' => 'uint', 'index' => true],
            'uint' => ['length' => false, 'generic' => 'int', 'component' => 'Colibri.UI.Forms.Number', 'index' => true],
            'float' => ['length' => false, 'generic' => 'float', 'component' => 'Colibri.UI.Forms.Number', 'index' => true],
            'timestamp' => ['length' => false, 'generic' => 'DateTimeToIntField', 'component' => 'Colibri.UI.Forms.DateTime', 'db' => 'bigint', 'index' => true],
            'string' => ['length' => false, 'generic' => 'string', 'component' => 'Colibri.UI.Forms.Text', 'index' => false],
            'field' => ['length' => false, 'generic' => 'string', 'component' => 'Colibri.UI.Forms.Text', 'index' => false],
            'field_string' => ['length' => false, 'generic' => 'string', 'component' => 'Colibri.UI.Forms.Text', 'index' => false],
        ];
    }

    public static function HasIndexes(): bool
    {
        return false;
    }

    public static function HasMultiFieldIndexes(): bool
    {
        return false;
    }

    public static function HasVirtual(): bool
    {
        return false;
    }

    public static function HasAutoincrement(): bool
    {
        return false;
    }

    public static function FieldsHasPrefix(): bool
    {
        return false;
    }

    public static function IndexTypes(): array
    {
        return [
            'NORMAL',
            'UNIQUE'
        ];
    }
    public static function IndexMethods(): array
    {
        return [
            'BTREE', 'HASH'
        ];
    }

    public function ExtractFieldInformation(array|object $field): object
    {
        $field = (object)$field;
        return (object) [
            'Field' => $field->Field,
            'Type' => $field->Type,
            'Null' => 'YES',
            'Key' => $field->Key,
            'Default' => null,
            'Extra' => '',
            'Expression' => ''
        ];

    }

    public function ExtractIndexInformation(array|object $index): object
    {
        return (object)[
            'Name' => $index->IndexName,
            'Columns' => [$index->AttrName],
            'ColumnPosition' => 1,
            'Collation' => 'A',
            'Null' => 1,
            'NonUnique' => 1,
            'Type' => $index->Type,
            'Primary' => $index->IndexName === 'PRIMARY'
        ];



    }

}
