<?php

/**
 * Fields
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Data\Storages\Fields
 */

namespace Colibri\Data\Storages\Fields;

use Colibri\Data\Storages\Storage;
use Colibri\App;
use Colibri\Common\StringHelper;
use Colibri\Data\Models\DataTable;
use Colibri\Data\Models\DataRow;
use Colibri\Data\Storages\Storages;
use Colibri\Utils\Debug;
use Colibri\Xml\XmlNode;
use Colibri\Common\VariableHelper;
use Colibri\Data\SqlClient\IDataReader;
use Colibri\Data\DataAccessPoint;
use Colibri\Data\NoSqlClient\ICommandResult;
use Colibri\Data\SqlClient\QueryInfo;

/**
 * Class representing the relationship between a field and a table
 * @author Vahan P. Grigoryan
 * @package Colibri\Data\Storages\Fields
 */
class Lookup
{
    /**
     * Storage associated with this lookup field.
     *
     * @var Storage
     */
    private ?Storage $_storage = null;

    /**
     * Field data associated with this lookup field.
     *
     * @var array
     */
    private array $_xfield;

    /**
     * Constructor
     * @param array $xfield field data
     * @param Storage|null $storage storage
     * @return void
     */
    public function __construct(array $xfield, Storage $storage)
    {
        $this->_storage = $storage;
        $this->_xfield = $xfield;
    }

    /**
     * Getter
     * @param string $prop property name
     * @return mixed value
     */
    public function __get(string $prop): mixed
    {
        if (isset($this->_xfield['lookup'][StringHelper::FromCamelCaseAttr($prop)])) {
            return $this->_xfield['lookup'][StringHelper::FromCamelCaseAttr($prop)];
        }
        return null;
    }

    /**
     * Loads data from the related table.
     * @param int $page page number, default is -1, meaning all
     * @param int $pagesize page size, default is 50
     * @return DataTable|null related data
     */
    public function Load(int $page = -1, int $pagesize = 50, mixed $parentObject = null): ?DataTable
    {
        if ($this->storage) {
            $data = (object) $this->storage;
            $storage = Storages::Instance()->Load($data->name, $data?->module ?? null);
            list($tableClass, $rowClass) = $storage->GetModelClasses();
            $accessPoint = $storage->accessPoint;
            if($accessPoint->dbms == DataAccessPoint::DBMSTypeRelational) {
                $reader = $accessPoint->Query(
                    'select * from ' . $storage->table . ($data->filter && $this->filter != '' ?
                        ' where ' . $data->filter : '') . ($data->order && $data->order != '' ?
                        ' order by ' . $data->order : ''),
                    ['type' => DataAccessPoint::QueryTypeBigData, 'page' => $page, 'pagesize' => $pagesize]
                );
                $return = new $tableClass($storage->accessPoint, $reader, $rowClass, $storage);
            } else if($accessPoint->dbms == DataAccessPoint::DBMSTypeNoSql) {
                $sort = explode(' ', $data->order);
                $return = $tableClass::LoadBy($page, $pagesize, '', [], $storage->GetRealFieldName($sort[0] ?? 'id'), $sort[1] ?? 'asc');
            }
            $return->isLookupOf($parentObject);
            return $return;
        } elseif ($this->accesspoint) {

            $data = (object) $this->accesspoint;
            $accessPoint = App::$dataAccessPoints->Get($data->point);
            $sqlQuery = $accessPoint->CreateQuery('CreateSelect', [$data->table, $data->fields, $data->filter, $data->order]);
            $params = ['type' => DataAccessPoint::QueryTypeBigData];
            if ($page > 0) {
                $params = ['type' => DataAccessPoint::QueryTypeBigData, 'page' => $page, 'pagesize' => $pagesize];
            }
            $reader = $accessPoint->Query(
                $sqlQuery,
                $params
            );
            return new DataTable($accessPoint, $reader);
        }

        return null;
    }

    /**
     * Loads the selected value from the related table.
     * @param mixed $value the selected value
     * @param mixed $parentObject the parent object
     * @return mixed
     */
    public function Selected(mixed $value, mixed $parentObject = null): mixed
    {
        if ($this->storage) {
            $data = (object) $this->storage;
            $storage = Storages::Instance()->Load($data->name, $data?->module ?? null);
            if (!$storage) {
                return null;
            }
            list($tableClass, $rowClass) = $storage->GetModelClasses();
            $isMultiple = $this->_xfield['params']['multiple'] ?? false;
            if ($isMultiple) {
                $value = is_string($value) ? json_decode($value) : $value;
            }
            $accessPoint = $storage->accessPoint;
            if($accessPoint->dbms == DataAccessPoint::DBMSTypeRelational) {
                
                if (is_null($value)) {
                    $filter = $storage->GetRealFieldName(
                        $data->value ?? 'id'
                    ) . ' is null';
                } elseif (!is_array($value)) {
                    $filter = $storage->GetRealFieldName(
                        $data->value ?? 'id'
                    ) . '=\'' . (is_object($value) ? $value->value : $value) . '\'';
                } else {
                    $filter = $storage->GetRealFieldName(
                        $data->value ?? 'id'
                    ) . ' in (\'' . implode('\', \'', array_map(function ($v) {
                        return is_object($v) ? $v->value : $v;
                    }, (array) $value)) . '\')';
                }
                
                $symbol = $accessPoint->symbol;
                /** @var IDataReader */
                $reader = $accessPoint->Query(
                    'select * from ' . $symbol . $storage->table . $symbol . ($filter && $filter != '' ? ' where ' . $filter : ''),
                    [
                        'type' => DataAccessPoint::QueryTypeBigData,
                        'page' => 1,
                        'pagesize' => is_array($value) ? count($value) : 1
                    ]
                );
                if (($reader instanceof QueryInfo || $reader instanceof ICommandResult) || $reader->Count() == 0) {
                    return null;
                }
                $table = new $tableClass($storage->accessPoint, $reader, $rowClass, $storage);
                $table->isLookupOf($parentObject);

                if ($table->Count() === 1 && !$isMultiple) {
                    $v = $table->First();
                    // if( ($lookupParent = $this->isRecursiveLookup($v, $value)) !== null ) {
                    //     return $lookupParent;
                    // }
                    if (isset($data->value)) {
                        $v->value = $v->{$data->value};
                    }
                    if (isset($data->title)) {
                        $v->title = $v->{$data->title};
                    }
                    return $v;
                } else {
                    $ret = [];
                    foreach ($table as $v) {
                        // if( ($lookupParent = $this->isRecursiveLookup($v, $value)) !== null ) {
                        //     return $lookupParent;
                        // }
                        if (isset($data->value)) {
                            $v->value = $v->{$data->value};
                        }
                        if (isset($data->title)) {
                            $v->title = $v->{$data->title};
                        }
                        $ret[] = $v;
                    }
                    return $ret;
                }
            } else if($accessPoint->dbms == DataAccessPoint::DBMSTypeNoSql) {

                $filter = [];
                if (is_null($value) || (is_array($value) && empty($value))) {
                    $filter[$storage->GetRealFieldName($data->value ?? 'id')] = [null, null];
                } elseif (!is_array($value)) {
                    $filter[$storage->GetRealFieldName($data->value ?? 'id')] = is_object($value) ? $value->value : $value;
                } else {
                    $filter[$storage->GetRealFieldName($data->value ?? 'id')] = array_map(function ($v) {
                        return is_object($v) ? $v->value : $v;
                    }, (array) $value);
                }
                $table = $tableClass::LoadBy(-1, 0, '', $filter, $storage->GetRealFieldName($data->value ?? 'id'), 'asc', false);
                if(!$table) {
                    return null;
                }

                $table->isLookupOf($parentObject);

                if ($table->Count() === 1 && !$isMultiple) {
                    $v = $table->First();
                    // if( ($lookupParent = $this->isRecursiveLookup($v, $value)) !== null ) {
                    //     return $lookupParent;
                    // }

                    if (isset($data->value)) {
                        $v->value = $v->{$data->value};
                    }
                    if (isset($data->title)) {
                        $v->title = $v->{$data->title};
                    }
                    return $v;
                } else {
                    $ret = [];
                    foreach ($table as $v) {
                        // if( ($lookupParent = $this->isRecursiveLookup($v, $value)) !== null ) {
                        //     return $lookupParent;
                        // }
                        if (isset($data->value)) {
                            $v->value = $v->{$data->value};
                        }
                        if (isset($data->title)) {
                            $v->title = $v->{$data->title};
                        }
                        $ret[] = $v;
                    }
                    return $ret;
                }


            }
        } elseif ($this->accesspoint) {
            $data = (object) $this->accesspoint;
            $accessPoint = App::$dataAccessPoints->Get($data->point);
            $sqlQuery = $accessPoint->CreateQuery('CreateSelect', [$data->table, $data->fields, [$data->value => ['=', (is_object($value) ? $value->value : $value)]], $data->order]);
            /** @var IDataReader */
            $reader = $accessPoint->Query(
                $sqlQuery,
                ['type' => DataAccessPoint::QueryTypeBigData]
            );
            if ($reader->Count() == 0) {
                return null;
            }
            $table = new DataTable($accessPoint, $reader);
            $v = $table->First();
            $v->value = $v->{$data->value};
            $v->title = $v->{$data->title};
            return $v;
        }

        return null;
    }

    /**
     * Returns the value field associated with this lookup.
     * @return string|null The value field name or null if not set.
     */
    public function GetValueField(): ?string
    {
        if ($this->storage) {
            $data = (object) $this->storage;
            return $data?->value ?? null;
        } elseif ($this->accessPoint) {
            $data = (object) $this->accessPoint;
            return $data->value;
        }
        return null;
    }

    /**
     * Returns the parent lookup fields for a given row.
     * @param mixed $row The row to get the parent lookups for.
     * @return array An array of parent lookup fields.
     */
    public function GetLookupParents(mixed $row): array 
    {
        $ret = [];
        while($row && $row instanceof DataRow) {
            if($row->lookupParent) {
                $ret[] = $row->lookupParent;
                $row = $row->lookupParent;
            } else {
                break;
            }
        }
        return $ret;
    }

    /**
     * Checks if the given value exists in the parent lookups of the provided row.
     * @param mixed $row The row to check for parent lookups.
     * @param mixed $value The value to check for in the parent lookups.
     * @return mixed The parent lookup field if found, otherwise null.
     */
    public function isRecursiveLookup(mixed $row, mixed $value): mixed
    {
        $parents = $this->GetLookupParents($row);
        foreach($parents as $parent) {
            if($parent instanceof DataRow) {
                $valueField = $this->GetValueField();
                if($valueField && $parent->$valueField == $value) {
                    return $parent;
                }
            }
        }
        
        return null;
    }

}
