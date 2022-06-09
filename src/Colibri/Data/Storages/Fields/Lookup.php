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

/**
 * Класс представление связи поля и таблицы
 * @author Vahan P. Grigoryan
 * @package Colibri\Data\Storages\Fields
 */
class Lookup
{

    /**
     * Хранилище
     *
     * @var Storage
     */
    private ?Storage $_storage = null;

    /**
     * Данные поля
     *
     * @var array
     */
    private array $_xfield;

    /**
     * Конструктор
     * @param array $xfield данные поля
     * @param Storage|null $storage хранилище
     * @return void
     */
    public function __construct(array $xfield, Storage $storage)
    {
        $this->_storage = $storage;
        $this->_xfield = $xfield;
    }

    /**
     * Геттер
     * @param string $prop свойство
     * @return mixed значение
     */
    public function __get(string $prop): mixed
    {
        if (isset($this->_xfield['lookup'][StringHelper::FromCamelCaseAttr($prop)])) {
            return $this->_xfield['lookup'][StringHelper::FromCamelCaseAttr($prop)];
        }
        return null;
    }

    /**
     * Запрашивает данные из связанной таблицы
     * @param int $page страница, по умолчанию -1, значит все
     * @param int $pagesize размер страницы, по умолачнию 20
     * @return DataTable|null данные по связке
     */
    public function Load(int $page = -1, int $pagesize = 50): ?DataTable
    {
        if ($this->storage) {
            $data = (object)$this->storage;
            $storage = Storages::Create()->Load($data->name);
            list($tableClass, $rowClass) = $storage->GetModelClasses();
            $accessPoint = $storage->accessPoint;
            $reader = $accessPoint->Query('select * from ' . $storage->name . ($data->filter && $this->filter != '' ? ' where ' . $data->filter : '') . ($data->order && $data->order != '' ? ' order by ' . $data->order : ''), ['type' => DataAccessPoint::QueryTypeBigData, 'page' => $page, 'pagesize' => $pagesize]);
            return new $tableClass($storage->accessPoint, $reader, $rowClass, $storage);
        }
        else if ($this->accessPoint) {

            $data = (object)$this->accessPoint;
            $accessPoint = App::$dataAccessPoints->Get($data->name);
            if ($page > 0) {
                $reader = $accessPoint->Query('select ' . $data->fields . ' from ' . $data->table . ($data->filter && $data->filter != '' ? ' where ' . $data->filter : '') . ($data->order && $data->order != '' ? ' order by ' . $data->order : ''), ['type' => DataAccessPoint::QueryTypeBigData, 'page' => $page, 'pagesize' => $pagesize]);
            }
            else {
                $reader = $accessPoint->Query('select ' . $data->fields . ' from ' . $data->table . ($data->filter && $data->filter != '' ? ' where ' . $data->filter : '') . ($data->order && $data->order != '' ? ' order by ' . $data->order : ''), ['type' => DataAccessPoint::QueryTypeBigData]);
            }

            return new DataTable($accessPoint, $reader);
        }

        return null;
    }

    /**
     * Загружает значение по связке (выбранное)
     * @param mixed $value значение
     * @return mixed
     */
    public function Selected(mixed $value): mixed
    {
        if ($this->storage) {
            $data = (object)$this->storage;
            $storage = Storages::Create()->Load($data->name);
            list($tableClass, $rowClass) = $storage->GetModelClasses();
            $accessPoint = $storage->accessPoint;
            $filter = $storage->GetRealFieldName($data->value ?? 'id') . '=\'' . (is_object($value) ? $value->value : $value) . '\'';
            /** @var IDataReader */
            $reader = $accessPoint->Query('select * from ' . $data->name . ($filter && $filter != '' ? ' where ' . $filter : ''), ['type' => DataAccessPoint::QueryTypeBigData, 'page' => 1, 'pagesize' => 1]);
            if($reader->Count() == 0) {
                return null;
            }
            $table = new $tableClass($storage->accessPoint, $reader, $rowClass, $storage);
            $v = $table->First();
            if(isset($data->value)) {
                $v->value = $v->{ $data->value };
            }
            if(isset($v->title)) {
                $v->title = $v->{ $data->title };
            }
            return $v;
        }
        else if ($this->accessPoint) {
            $data = (object)$this->accessPoint;
            $accessPoint = App::$dataAccessPoints->Get($data->name);
            $filter = $data->value . '=\'' . (is_object($value) ? $value->value : $value) . '\'';
            /** @var IDataReader */
            $reader = $accessPoint->Query('select * from (select ' . $data->fields . ' from ' . $data->table . ') t where ' . $filter . ($data->order ? ' order by ' . $data->order : ''), ['type' => DataAccessPoint::QueryTypeBigData]);
            if($reader->Count() == 0) {
                return null;
            }
            $table = new DataTable($accessPoint, $reader);
            $v = $table->First();
            $v->value = $v->{ $data->value};
            $v->title = $v->{ $data->title};
            return $v;
        }
    }

}
