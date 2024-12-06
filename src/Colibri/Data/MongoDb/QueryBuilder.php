<?php


/**
 * MySql
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\MySql
 */
namespace Colibri\Data\MongoDb;

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
                    $query[$storage->GetRealFieldName($field->name)] = '/.*' . $storage->accessPoint->EscapeQuery($term) . '.*/i';
                }
            }
        }

        foreach($fields as $fieldName => $fieldData) {
            $field = $fieldData[0];
            $value = $fieldData[1];
            
            $fieldName = $storage->GetRealFieldName($fieldName);

            if(in_array($field->component, [
                'Colibri.UI.Forms.Date',
                'Colibri.UI.Forms.DateTime',
            ])) {
                $filters[$fieldName] = ['datetime', 'between', ...array_map(fn($v) => $storage->accessPoint->EscapeQuery((new \DateTime(trim($v)))->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\\TH:i:s\\Z')), $value)];
            } elseif (in_array($field->component, [
                'Colibri.UI.Forms.Number'
            ])) {
                $filters[$fieldName] = ['number', 'between', array_map(fn($v) => $storage->accessPoint->EscapeQuery(trim($v)), $value)];
            } else {
                if(!is_array($value)) {
                    $value = [$value];
                }
                $filters[$fieldName] = ['string', 'in', array_map(fn($v) => '/.*' . $storage->accessPoint->EscapeQuery($v) . '.*/i', $value)];
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

        return [$query, $filters, $sortField . ' ' . $sortOrder];

    }

}