<?php


/**
 * MySql
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\MySql
 */
namespace Colibri\Data\Solr;

use Colibri\Common\DateHelper;
use Colibri\Common\StringHelper;
use Colibri\Common\VariableHelper;
use Colibri\Data\SqlClient\IQueryBuilder;
use Colibri\Data\Storages\Storage;

/**
 * Class for generating queries for the Solr driver.
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
            foreach ($storage->fields as $field) {
                if ($field->class === 'string') {
                    $query[$storage->GetRealFieldName($field->name)] = '*' . $storage->accessPoint->EscapeQuery($term) . '*';
                }
            }
        } else {
            $query = ['*' => '*'];
        }

        foreach($fields as $fieldName => $fieldData) {
            $field = $fieldData[0];
            $value = $fieldData[1];
            
            $fieldName = $storage->GetRealFieldName($fieldName);

            if(in_array($field->type, [
                'date',
                'datetime',
            ])) {
                $filters[$fieldName] = '['.($value[0] ? $storage->accessPoint->EscapeQuery(DateHelper::ToISODate($value[0])) : '*').' TO '.($value[1] ? $storage->accessPoint->EscapeQuery(DateHelper::ToISODate($value[1])) : '*').']';
            } elseif (in_array($field->type, [
                'bigint', 'int', 'float'
            ])) {
                $filters[$fieldName] = [];
                if(count($value) == 2) {
                    $filters[$fieldName] = '['.($value[0] ? $value[0] : '*').' TO '.($value[1] ? $value[1] : '*').']';
                } elseif(count($value) > 1) {
                    $filters[$fieldName] = '(' . implode(' or ', array_map(fn($v) => $storage->accessPoint->EscapeQuery($v), $value)) . ')';
                } else {
                    $filters[$fieldName] = $value[0];
                }
            } elseif (in_array($field->type, [
                'bool'
            ])) {
                if($value == 1) {
                    $filters[$fieldName] = true;
                } else {
                    $filters['-' . $fieldName] = '*';
                }
            } else {
                if(!is_array($value)) {
                    $filters[$fieldName] = '*' . $storage->accessPoint->EscapeQuery($value) . '*';
                } else {
                    $filters[$fieldName] = '(' . implode(' or ', array_map(fn($v) => '*' . $storage->accessPoint->EscapeQuery($v) . '*', $value)) . ')';
                }
            }

        }

        $sortField = $storage->GetRealFieldName($sortField ?: 'id');
        $sortOrder = $sortOrder ?: 'asc';

        return [$query, $filters, [$sortField => $sortOrder]];

    }

    public function ProcessMutationData(mixed $row, string $mutationType): array|object
    {

        if(is_object($row) && method_exists($row, 'GetData')) {
            $data = (array)$row->GetData();
        } else {
            $data = (array)$row;
        }

        if($mutationType === self::MutationUpdate) {
            $fieldValues = [];
            foreach ($data as $key => $value) {
                if(is_object($row) && method_exists($row, 'IsPropertyChanged')) {
                    $f = $row->Storage()->GetField($key);
                    if ($row->IsPropertyChanged($key)) {
                        if (in_array($f->type, ['date', 'datetime'])) {
                            $fieldValues[$key] = DateHelper::ToISODate($value);
                        } else {
                            $fieldValues[$key] = $value;
                        }
                    }
                } else {
                    $fieldValues[$key] = $value;
                }
            }
            $fieldValues['datemodified'] = DateHelper::ToISODate();
            return $fieldValues;
        } elseif ($mutationType === self::MutationDelete) {
            return $data;
        } elseif ($mutationType === self::MutationInsert) {

            $data['datecreated'] = DateHelper::ToISODate($data['datecreated']) ?? DateHelper::ToISODate();
            $data['datemodified'] = DateHelper::ToISODate($data['datemodified']) ?? DateHelper::ToISODate();
            $data['datedeleted'] = $data['datedeleted'] ? DateHelper::ToISODate($data['datedeleted']) : null;
            unset($data['id']);

            foreach ($data as $key => $value) {
                $f = $row->Storage()->GetField($key);
                if (in_array($f->type, ['date', 'datetime'])) {
                    $data[$key] = DateHelper::ToISODate($value);
                }
            }

            return $data;

        }

    }

}