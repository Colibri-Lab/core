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
            $storage = Storages::Create()->Load($this->storage);
            list($tableClass, $rowClass) = $storage->GetModelClasses();
            $accessPoint = $storage->accessPoint;
            $reader = $accessPoint->Query('select * from ' . $storage->name . ($this->filter && $this->filter != '' ? ' where ' . $this->filter : '') . ($this->order && $this->order != '' ? ' order by ' . $this->order : ''), ['page' => $page, 'pagesize' => $pagesize]);
            return new $tableClass($storage->accessPoint, $reader, $rowClass, $storage);
        }
        else if ($this->accessPoint) {

            $accessPoint = App::$dataAccessPoints->Get($this->accessPoint);
            if ($page > 0) {
                $reader = $accessPoint->Query('select ' . $this->fields . ' from ' . $this->table . ($this->filter && $this->filter != '' ? ' where ' . $this->filter : '') . ($this->order && $this->order != '' ? ' order by ' . $this->order : ''), ['page' => $page, 'pagesize' => $pagesize]);
            }
            else {
                $reader = $accessPoint->Query('select ' . $this->fields . ' from ' . $this->table . ($this->filter && $this->filter != '' ? ' where ' . $this->filter : '') . ($this->order && $this->order != '' ? ' order by ' . $this->order : ''));
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
            $storage = Storages::Create()->Load($this->storage);
            list($tableClass, $rowClass) = $storage->GetModelClasses();
            $accessPoint = $storage->accessPoint;
            $filter = $storage->GetRealFieldName($this->value ?: 'id') . '=\'' . (is_object($value) ? $value->value : $value) . '\'';
            $reader = $accessPoint->Query('select * from ' . $storage->name . ($filter && $filter != '' ? ' where ' . $filter : ''), ['page' => 1, 'pagesize' => 1]);
            $table = new $tableClass($storage->accessPoint, $reader, $rowClass, $storage);
            $v = $table->First();
            $v->value = $v->{ $this->value};
            $v->title = $v->{ $this->title};
            return $v;
        }
        else if ($this->accessPoint) {
            $accessPoint = App::$dataAccessPoints->Get($this->accessPoint);
            $filter = $this->value . '=\'' . (is_object($value) ? $value->value : $value) . '\'';
            $reader = $accessPoint->Query('select * from (select ' . $this->fields . ' from ' . $this->table . ') t where ' . $filter . ($this->order ? ' order by ' . $this->order : ''));
            $table = new DataTable($accessPoint, $reader);
            $v = $table->First();
            $v->value = $v->{ $this->value};
            $v->title = $v->{ $this->title};
            return $v;
        }
    }

}
