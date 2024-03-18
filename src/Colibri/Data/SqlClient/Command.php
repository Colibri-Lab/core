<?php

/**
 * Interface for database drivers
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Utils\Config
 * @version 1.0.0
 *
 */
namespace Colibri\Data\SqlClient;

/**
 * Represents a database command abstraction.
 *
 * @property-read string $query The command text.
 * @property-read IConnection|null $connection The database connection associated with the command.
 * @property-read string $type The type of the command (e.g., SELECT, INSERT, UPDATE, DELETE).
 * @property-read int $page The current page number.
 * @property-read int $pagesize The page size for pagination.
 * @property-read array|null $params The parameters for the command.
 *
 */
abstract class Command
{
    /**
     * The database connection associated with the command.
     *
     * @var IConnection|null
     */
    protected ?IConnection $_connection = null;

    /**
     * The command text.
     *
     * @var string
     */
    protected string $_commandtext = '';

    /**
     * The page size for pagination.
     *
     * @var int
     */
    protected int $_pagesize = 10;

    /**
     * The current page number.
     *
     * @var int
     */
    protected int $_page = -1;

    /**
     * The parameters for the command.
     *
     * @var array|null
     */
    protected ?array $_params = null;

    /**
     * Constructs a new Command object.
     *
     * @param string $commandtext The command text.
     * @param IConnection|null $connection (optional) The database connection. Default is null.
     */
    public function __construct(string $commandtext = '', ?IConnection $connection = null)
    {
        $this->_commandtext = $commandtext;
        $this->_connection = $connection;
    }

    /**
     * Magic method to get properties dynamically.
     *
     * @param string $property The name of the property.
     * @return mixed|null The value of the property, or null if the property does not exist.
     */
    public function __get(string $property): mixed|null
    {
        $return = null;
        switch (strtolower($property)) {
            case 'query':
            case 'commandtext':
            case 'text': {
                    $return = $this->_commandtext;
                    break;
                }
            case 'connection': {
                    $return = $this->_connection;
                    break;
                }
            case 'type': {
                    $parts = explode(' ', $this->query);
                    $return = strtolower($parts[0]);
                    break;
                }
            case 'page': {
                    $return = $this->_page;
                    break;
                }
            case 'pagesize': {
                    $return = $this->_pagesize;
                    break;
                }
            case 'params': {
                    $return = $this->_params;
                    break;
                }
            default: {
                    $return = null;
                }
        }
        return $return;
    }

    /**
     * Magic method to set properties dynamically.
     *
     * @param string $property The name of the property.
     * @param mixed $value The value to set.
     * @return void
     */
    public function __set(string $property, mixed $value): void
    {
        switch (strtolower($property)) {
            case 'query':
            case 'commandtext':
            case 'text': {
                    $this->_commandtext = $value;
                    break;
                }
            case 'connection': {
                    $this->_connection = $value;
                    break;
                }
            case "page": {
                    $this->_page = $value;
                    break;
                }
            case "pagesize": {
                    $this->_pagesize = $value;
                    break;
                }
            case 'params': {
                    $this->_params = $value;
                    break;
                }
            default:
        }
    }

    /**
     * Executes the command and returns a data reader.
     *
     * @param bool $info (optional) Whether to include query info. Default is true.
     * @return IDataReader The data reader.
     */
    abstract public function ExecuteReader(bool $info = true): IDataReader;

    /**
     * Executes the command and returns query information.
     *
     * @param string|null $returning (optional) The returning clause for the query. Default is null.
     * @return QueryInfo The query information.
     */
    abstract public function ExecuteNonQuery(?string $returning = null): QueryInfo;

    /**
     * Prepares the query string before execution.
     *
     * @return string The prepared query string.
     */
    abstract public function PrepareQueryString(): string;
}
