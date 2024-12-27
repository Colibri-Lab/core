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
 * Interface for building SQL queries.
 */
interface IQueryBuilder
{
    /**
     * Creates an SQL INSERT query.
     *
     * @param string $table The name of the table to insert data into.
     * @param array|object $data The data to insert into the table.
     * @param string $returning (optional) The returning clause for the query. Default is an empty string.
     * @return string The generated INSERT query.
     */
    public function CreateInsert(string $table, array|object $data, string $returning = ''): string;

    /**
     * Creates an SQL REPLACE query.
     *
     * @param string $table The name of the table to replace data in.
     * @param array|object $data The data to replace in the table.
     * @param string $returning (optional) The returning clause for the query. Default is an empty string.
     * @return string The generated REPLACE query.
     */
    public function CreateReplace(string $table, array|object $data, string $returning = ''): string;

    /**
     * Creates an SQL INSERT OR UPDATE query.
     *
     * @param string $table The name of the table.
     * @param array|object $data The data to insert or update.
     * @param array $exceptFields (optional) Fields to be excluded from the update operation. Default is an empty array.
     * @param string $returning (optional) The returning clause for the query. Default is an empty string.
     * @return string The generated INSERT OR UPDATE query.
     */
    public function CreateInsertOrUpdate(string $table, array|object $data, array $exceptFields = [], string $returning = ''): string;

    /**
     * Creates an SQL batch INSERT query.
     *
     * @param string $table The name of the table to insert data into.
     * @param array|object $data The data to insert into the table.
     * @return string The generated batch INSERT query.
     */
    public function CreateBatchInsert(string $table, array|object $data): string;

    /**
     * Creates an SQL UPDATE query.
     *
     * @param string $table The name of the table to update.
     * @param string $condition The condition for the update operation.
     * @param array|object $data The data to update.
     * @return string The generated UPDATE query.
     */
    public function CreateUpdate(string $table, string $condition, array|object $data): string;

    /**
     * Creates an SQL DELETE query.
     *
     * @param string $table The name of the table to delete from.
     * @param string $condition The condition for the delete operation.
     * @return string The generated DELETE query.
     */
    public function CreateDelete(string $table, string $condition): string;

    /**
     * Creates an SQL SHOW TABLES query.
     *
     * @return string The generated SHOW TABLES query.
     */
    public function CreateShowTables(?string $tableFilter = null, ?string $database = null): string;

    /**
     * Creates an SQL SHOW FIELD query.
     *
     * @param string $table The name of the table.
     * @return string The generated SHOW FIELD query.
     */
    public function CreateShowField(string $table, ?string $database = null): string;

    /**
     * Creates an query for list indexes in table
     * @param string $table
     * @param mixed $database
     * @return string
     */
    public function CreateShowIndexes(string $table, ?string $database = null): string;

    /**
     * Creates an SQL BEGIN transaction query.
     *
     * @return string The generated BEGIN transaction query.
     */
    public function CreateBegin(?string $type = null): string;

    /**
     * Creates an SQL COMMIT transaction query.
     *
     * @return string The generated COMMIT transaction query.
     */
    public function CreateCommit(): string;

    /**
     * Creates an SQL ROLLBACK transaction query.
     *
     * @return string The generated ROLLBACK transaction query.
     */
    public function CreateRollback(): string;

    public function CreateDefaultStorageTable(string $table, ?string $prefix = null): string|array;

    public function CreateDrop($table): string;

    public function CreateFieldForQuery(string $field, string $table): string;

}
