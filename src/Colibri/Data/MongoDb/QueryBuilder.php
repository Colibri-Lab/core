<?php


/**
 * MySql
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\MySql
 */

namespace Colibri\Data\MongoDb;

use Colibri\Common\DateHelper;
use Colibri\Common\StringHelper;
use Colibri\Common\VariableHelper;
use Colibri\Data\SqlClient\IQueryBuilder;
use Colibri\Data\Storages\Storage;

/**
 * Class for generating queries for the MongoDb driver.
 * @class
 *
 */
class QueryBuilder
{
    /**
     * The MongoDB connection instance. 
     * @var Connection
     * @private  
     */
    private Connection $_connection;

    /**
     * Initializes a new instance of the QueryBuilder class with the specified MongoDB connection.
     *
     * @param Connection $connection The MongoDB connection instance.
     * @constructor
     * @public
     */
    public function __construct(Connection $connection)
    {
        $this->_connection = $connection;
    }

    /** 
     * Mutation types for MongoDB operations.
     * @public
     * @const string
     */
    public const MutationInsert = 'insert';

    /** 
     * Mutation type for updating documents in MongoDB. 
     * @public
     * @const string
     */
    public const MutationUpdate = 'update';

    /** 
     * Mutation type for deleting documents in MongoDB.
     * @public
     * @const string
     */
    public const MutationDelete = 'delete';

    /** 
     * Generates a query for the specified fields and term, recursively traversing nested fields.
     *
     * @param mixed $term The search term.
     * @param array $fields The fields to search.
     * @param string $parent The parent field name.
     * @param Storage $storage The storage instance.
     * @param array $query The query array to populate.
     * @private
     */
    private function _getFieldQuery($term, $fields, $parent, $storage, &$query)
    {
        foreach ($fields as $field) {
            if ($field->class === 'string') {
                $query[($parent ? $parent.'.' : '').$storage->GetRealFieldName($field->name)] = '/' . $storage->accessPoint->EscapeQuery($term) . '/i';
            } elseif (!empty($field->fields)) {
                $this->_getFieldQuery($term, $field->fields, $field->name, $storage, $query);
            }
        }
    }

    /**
     * Processes filters, search terms, and sorting options to generate a MongoDB query.
     *
     * @param Storage $storage The storage instance.
     * @param string $term The search term.
     * @param array|null $filterFields The filter fields.
     * @param string|null $sortField The field to sort by.
     * @param string|null $sortOrder The sort order.
     * @param bool $useAsManageFilter Whether to use as manage filter.
     * @return array The generated MongoDB query.
     * @public
     */
    public function ProcessFilters(Storage $storage, string $term, ?array $filterFields, ?string $sortField, ?string $sortOrder, bool $useAsManageFilter = true): array
    {

        $filterFields = VariableHelper::ToJsonFilters($filterFields);

        $searchFilters = [];
        foreach($filterFields as $key => $filterData) {
            $searchFilters[str_replace('[0]', '', $key)] = $filterData;
        }

        $fields = [];
        foreach($searchFilters as $fieldName => $fieldParams) {
            if(in_array($fieldName, ['id', 'datecreated', 'datemodified'])) {
                $field = (object)[
                    'component' => $fieldName === 'id' ? 'Colibri.UI.Forms.Number' : 'Colibri.UI.Forms.DateTime',
                    'desc' => [
                        'id' => 'ID',
                        'datecreated' => 'Дата создания',
                        'datemodified' => 'Дата изменения'
                    ][$fieldName],
                    'type' => [
                        'id' => 'int',
                        'datecreated' => 'datetime',
                        'datemodified' => 'datetime'
                    ][$fieldName],
                    'param' => [
                        'id' => 'integer',
                        'datecreated' => 'string',
                        'datemodified' => 'string'
                    ][$fieldName],
                ];
            } else {
                $field = $storage->GetField(str_replace('.', '/', $fieldName));
            }

            $fields[$fieldName] = [$field, $fieldParams];

        }

        $filters = [];
        $query = [];
        if($term) {
            $this->_getFieldQuery($term, $storage->fields, '', $storage, $query);
        }

        foreach($fields as $fieldName => $fieldData) {
            $field = $fieldData[0];
            $value = $fieldData[1];

            $fieldName = $storage->GetRealFieldName($fieldName);

            if(in_array($field->component, [
                'Colibri.UI.Forms.Date',
                'Colibri.UI.Forms.DateTime',
            ])) {
                $filters[$fieldName] = [];
                if($value[0]) {
                    $filters[$fieldName]['$gte'] = DateHelper::ToISODate($value[0]);
                }
                if($value[1]) {
                    $filters[$fieldName]['$lte'] = DateHelper::ToISODate($value[1]);
                }
            } elseif (in_array($field->component, [
                'Colibri.UI.Forms.Number'
            ])) {
                $filters[$fieldName] = [];
                if(is_array($value) && count($value) == 2 && $useAsManageFilter) {
                    $filters[$fieldName] = [];
                    if($value[0]) {
                        $filters[$fieldName]['$gte'] = $value[0];
                    }
                    if($value[1]) {
                        $filters[$fieldName]['$lte'] = $value[1];
                    }
                } elseif(is_array($value) && count($value) > 1) {
                    $filters[$fieldName] = ['$in' => $value];
                } else {
                    $filters[$fieldName] = ['$eq' => is_array($value) ? $value[0] : $value];
                }
            } else {
                if(is_array($value)) {
                    if(empty($value)) {
                        $filters[$fieldName] = ['$size' => 0];
                    } else {
                        $filters[$fieldName] = ['$in' => $value];
                    }
                } else {
                    $filters[$fieldName] = ['$regex' => $value, '$options' => 'i'];
                }
            }

        }

        if (!$sortField) {
            $sortField = $storage->GetRealFieldName('id');
        } else {
            $sortField = $storage->GetRealFieldName($sortField);
        }
        if (!$sortOrder) {
            $sortOrder = 'asc';
        }

        return [$query, $filters, [$sortField => $sortOrder === 'asc' ? 1 : -1]];

    }

    /** 
     * Processes mutation data for MongoDB operations (insert, update, delete) based on the provided row and mutation type.
     *
     * @param mixed $row The data row to process.
     * @param string $mutationType The type of mutation (insert, update, delete).
     * @return array|object The processed mutation data.
     * @public
     */
    public function ProcessMutationData(mixed $row, string $mutationType): array|object
    {

        if(is_object($row) && method_exists($row, 'GetValidationData')) {
            $data = (array)$row->GetValidationData(false);
        } else {
            $data = (array)$row;
        }

        if($mutationType === self::MutationUpdate) {
            $fieldValues = ['$set' => []];
            foreach ($data as $key => $value) {
                if(is_object($row) && method_exists($row, 'IsPropertyChanged')) {
                    if ($row->IsPropertyChanged($key)) {
                        $fieldValues['$set'][$key] = $value;
                    }
                } else {
                    $fieldValues['$set'][$key] = $value;
                }
            }
            $fieldValues['$set']['datemodified'] = $data['datemodified'] ?? DateHelper::ToDBString();
            return $fieldValues;
        } elseif ($mutationType === self::MutationDelete) {
            return $data;
        } elseif ($mutationType === self::MutationInsert) {

            $data['datecreated'] = $data['datecreated'] ?? DateHelper::ToDBString();
            $data['datemodified'] = $data['datemodified'] ?? DateHelper::ToDBString();
            $data['datedeleted'] = $data['datedeleted'] ?? null;
            unset($data['id']);
            return $data;

        } else {
            return (object)[];
        }

    }

    /** 
     * Creates a field name for a query, optionally including the table name as a prefix.
     *
     * @param string $field The field name.
     * @param string $table The table name.
     * @return string The field name for the query.
     * @public
     */
    public function CreateFieldForQuery(string $field, string $table): string
    {
        return $field;
    }

    /**
     * Creates a query for soft deletion, checking if the specified soft delete field is null.
     *
     * @param string $softDeleteField The soft delete field name.
     * @param string $table The table name.
     * @return array The soft delete query.
     * @public
     */
    public function CreateSoftDeleteQuery(string $softDeleteField = 'datedeleted', string $table = ''): array
    {
        return [$this->CreateFieldForQuery($softDeleteField, $table) => null];
    }


}
