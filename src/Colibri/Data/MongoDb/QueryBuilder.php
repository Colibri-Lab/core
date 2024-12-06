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
 *
 */
class QueryBuilder
{

    public const MutationInsert = 'insert';
    public const MutationUpdate = 'update';
    public const MutationDelete = 'delete';
    
    public function ProcessFilters(Storage $storage, string $term, ?array $filterFields, ?string $sortField, ?string $sortOrder)
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
            function getFieldQuery($term, $fields, $parent, $storage, &$query) {
                foreach ($fields as $field) {
                    if ($field->class === 'string') {
                        $query[($parent ? $parent.'.' : '').$storage->GetRealFieldName($field->name)] = '/' . $storage->accessPoint->EscapeQuery($term) . '/i';
                    } elseif ($field->fields) {
                        getFieldQuery($term, $field->fields, $field->name, $storage, $query);
                    }
                }
            }
            
            getFieldQuery($term, $storage->fields, '', $storage, $query);

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
                if(count($value) == 2) {
                    if($value[0]) {
                        $filters[$fieldName] = ['$gte' => $value[0]];
                    }
                    if($value[1]) {
                        $filters[$fieldName] = ['$lte' => $value[1]];
                    }
                } elseif(count($value) > 1) {
                    $filters[$fieldName] = ['$in' => $value];
                } else {
                    $filters[$fieldName] = ['$eq' => $value[0]];
                }
            } else {
                if(is_array($value)) {
                    $filters[$fieldName] = ['$in' => $value];
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

    public function ProcessMutationData(mixed $row, string $mutationType): array|object
    {

        if(is_object($row) && method_exists($row, 'GetValidationData')) {
            $data = (array)$row->GetValidationData();
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
            return $fieldValues;
        } elseif ($mutationType === self::MutationDelete) {
            return $data;
        } elseif ($mutationType === self::MutationInsert) {

            $data['datecreated'] = $data['datecreated'] ?? DateHelper::ToDBString();
            $data['datemodified'] = $data['datemodified'] ?? DateHelper::ToDBString();
            $data['datedeleted'] = $data['datedeleted'] ?? null;
            unset($data['id']);
            return $data;

        }

    }

    


}