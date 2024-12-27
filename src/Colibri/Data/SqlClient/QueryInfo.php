<?php

/**
 * SqlClient
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\SqlClient
 */

namespace Colibri\Data\SqlClient;

/**
 * Represents information about a database query.
 */
class QueryInfo
{
    /**
     * The type of the query (e.g., SELECT, INSERT, UPDATE, DELETE).
     *
     * @var string
     */
    public string $type;

    /**
     * The ID of the last inserted row (if applicable).
     *
     * @var int
     */
    public int $insertid;

    /**
     * The number of affected rows by the query.
     *
     * @var int
     */
    public int $affected;

    /**
     * Any error message associated with the query execution.
     *
     * @var string
     */
    public string $error;

    /**
     * The SQL query string.
     *
     * @var string
     */
    public string $query;

    /**
     * Constructs a new QueryInfo object.
     *
     * @param string $type The type of the query.
     * @param int $insertid The ID of the last inserted row (if applicable).
     * @param int $affected The number of affected rows by the query.
     * @param string $error Any error message associated with the query execution.
     * @param string $query The SQL query string.
     */
    public function __construct(string $type, int $insertid, int $affected, string $error, string $query)
    {
        $this->type = $type;
        $this->insertid = $insertid;
        $this->affected = $affected;
        $this->error = $error;
        $this->query = $query;
    }

}
