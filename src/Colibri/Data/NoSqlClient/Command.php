<?php

/**
 * SqlClient
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\SqlClient
 */

namespace Colibri\Data\NoSqlClient;

use Colibri\Utils\Logs\Logger;

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
     * @param IConnection|null $connection (optional) The database connection. Default is null.
     */
    public function __construct(?IConnection $connection = null)
    {
        $this->_connection = $connection;
    }

    /**
     * Magic method to get properties dynamically.
     *
     * @param string $property The name of the property.
     * @return mixed The value of the property, or null if the property does not exist.
     */
    public function __get(string $property)
    {
        $return = null;
        switch (strtolower($property)) {
            case 'connection': {
                    $return = $this->_connection;
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
     * Executes the command and returns a data results if exists.
     *
     * @param IConnection $connection
     * @param string $type request type
     * @param string $command command name
     * @param mixed[] $arguments command arguments
     * @return ICommandResult The command result.
     */
    abstract public static function Execute(IConnection $connection, string $type, string $command, array $arguments): ICommandResult;

    abstract public function Migrate(Logger $logger, string $storage, array $xstorage): void;
    
}
