<?php

/**
 * Models
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Data\Models
 */

namespace Colibri\Data\Models;

use ArrayAccess;
use Colibri\App;
use Colibri\Collections\ArrayList;
use Colibri\Collections\IArrayList;
use Colibri\Common\Encoding;
use Colibri\Data\DataAccessPoint;
use Colibri\Data\SqlClient\IDataReader;
use Colibri\Common\VariableHelper;
use Colibri\Data\SqlClient\NonQueryInfo;
use Colibri\Utils\ExtendedObject;
use Countable;
use Colibri\Utils\Debug;

/**
 * Представление таблицы данных
 *
 * @property-read boolean $hasrows
 * @property-read integer $count
 * @property-read integer $affected
 * @property-read integer $loaded
 * @method mixed methodName()
 * @testFunction testDataTable
 */
class DataTable implements Countable, ArrayAccess, \IteratorAggregate
{

    /**
     * Точка доступа
     *
     * @var DataAccessPoint
     */
    protected $_point;

    /**
     * Ридер
     *
     * @var IDataReader
     */
    protected $_reader;

    /**
     * Кэш загруженных строк
     *
     * @var ArrayList
     */
    protected $_cache;

    /**
     * Название класса представления строк
     *
     * @var ExtendedObject
     */
    protected $_returnAs;

    /**
     * Конструктор
     *
     * @param DataAccessPoint $point
     * @param IDataReader $reader
     * @param string $returnAs
     */
    public function __construct(DataAccessPoint $point, IDataReader $reader = null, $returnAs = 'Colibri\\Data\\Models\\DataRow')
    {
        $this->_point = $point;
        $this->_reader = $reader;
        $this->_cache = new ArrayList();
        $this->_returnAs = $returnAs;
    }

    /**
     * Статический конструктор
     *
     * @param DataAccessPoint|string $point
     * @param string $returnAs
     * @return DataTable
     * @testFunction testDataTableCreate
     */
    public static function Create($point, $returnAs = 'Colibri\\Data\\Models\\DataRow')
    {
        if (is_string($point)) {
            $point = App::$dataAccessPoints->Get($point);
        }
        return new DataTable($point, null, $returnAs);
    }

    /**
     * Возвращает итератор
     *
     * @return DataTableIterator
     * @testFunction testDataTableGetIterator
     */
    public function getIterator()
    {
        return new DataTableIterator($this); 
    }

    /**
     * Загружает данные из запроса или таблицы
     *
     * @param string $query название таблицы или запрос
     * @param string $params
     * @return DataTable
     * @testFunction testDataTableLoad
     */
    public function Load($query, $params = [])
    {
        $params = (object)$params;

        if ($this->_cache) {
            $this->_cache->Clear();
        }

        if ($this->_reader) {
            $this->_reader->Close();
        }

        if (strstr(strtolower($query), 'select') === false) {
            // если нет слова select, то это видимо название таблицы, надо проверить
            if (strstr($query, ' ') !== false) {
                // есть пробел, значит это не название таблицы нужно выдать ошибку
                throw new DataModelException('Param query can be only the table name or select query');
            } else {
                $query = 'select * from ' . $query;
                $condition = [];
                if (isset($params->params)) {
                    foreach ($params->params as $key => $value) {
                        $condition[] = $key . '=[[' . $key . ']]';
                    }
                }
                if (!empty($condition)) {
                    $query .= ' where ' . implode(' and ', $condition);
                }
            }
        }

        $this->_reader = $this->_point->Query($query, $params);
        return $this;
    }

    /**
     * Возвращает количество строк
     *
     * @return int
     * @testFunction testDataTableCount
     */
    public function Count()
    {
        return $this->_reader->Count();
    }

    /**
     * Возвращает общее количество строк
     *
     * @return int
     * @testFunction testDataTableAffected
     */
    public function Affected()
    {
        return $this->_reader->affected;
    }

    /**
     * Возвращает наличие строк
     *
     * @return boolean
     * @testFunction testDataTableHasRows
     */
    public function HasRows()
    {
        return $this->_reader->hasrows;
    }

    /**
     * Список полей
     *
     * @return mixed
     * @testFunction testDataTableFields
     */
    public function Fields()
    {
        return VariableHelper::ChangeArrayKeyCase($this->_reader->Fields(), CASE_LOWER);
    }

    /**
     * Возвращает точку доступа
     *
     * @return DataAccessPoint
     * @testFunction testDataTablePoint
     */
    public function Point()
    {
        return $this->_point;
    }

    /**
     * Создает обьект данных представления строки
     *
     * @param mixed $result
     * @return mixed
     * @testFunction testDataTable_createDataRowObject
     */
    protected function _createDataRowObject($result)
    {
        $className = $this->_returnAs;

        // ищем класс, если нету то добавляем неймспейс App\Models
        if (is_string($className) && !class_exists($className)) {
            $className = 'App\\Models\\' . $className;
            // ищем модель в приложении, если не нашли то берем стандартную модель
            if (!class_exists($className)) {
                $className = 'Colibri\\Data\\Models\\DataRow';
            }
        }

        return new $className($this, $result);
    }

    /**
     * Считывает еще одну строку из источника
     *
     * @return mixed
     * @testFunction testDataTable_read
     */
    protected function _read()
    {
        return $this->_createDataRowObject(
            $this->_reader->Read()
        );
    }

    /**
     * Считывает строки до указнного индекса
     *
     * @param integer $index
     * @return mixed
     * @testFunction testDataTable_readTo
     */
    protected function _readTo($index)
    {
        while ($this->_cache->Count() < $index) {
            $this->_cache->Add($this->_read());
        }
        return $this->_cache->Add($this->_read());
    }

    /**
     * Возвращает строку по выбранному индексу
     *
     * @param integer $index
     * @return mixed
     * @testFunction testDataTableItem
     */
    public function Item($index)
    {
        if ($index >= $this->_cache->Count()) {
            return $this->_readTo($index);
        } else {
            return $this->_cache->Item($index);
        }
    }

    /**
     * Возвращает первую строку
     *
     * @return mixed
     * @testFunction testDataTableFirst
     */
    public function First()
    {
        return $this->Item(0);
    }

    /**
     * Скачивает и кэширует все
     *
     * @param boolean $closeReader
     * @return mixed
     * @testFunction testDataTableCacheAll
     */
    public function CacheAll($closeReader = true)
    {
        $this->_readTo($this->Count() - 1);
        if ($closeReader) {
            $this->_reader->Close();
        }
        return $this->_cache;
    }

    /**
     * Создает пустую строку
     * 
     * @param object|array $data данные строки
     *
     * @return mixed
     * @testFunction testDataTableCreateEmptyRow
     */
    public function CreateEmptyRow($data = [])
    {
        return $this->_createDataRowObject($data);
    }

    /**
     * Получаем кодировку таблицы
     * @param string $table название таблицы, без схемы
     * @return object encoding, collation - кодировка и коллейшен
     * @testFunction testDataTable_getTableEncoding
     */
    private function _getTableEncoding($table)
    {
        // получаем кодировку таблицы
        $table = explode('.', $table);
        $reader = $this->_point->Query('show table status in '.$table[0].' like \'' . $table[1] . '\'');
        $status = $reader->Read();
        $collation = $status->Collation;

        // получаем что то типа <encoding>_<collation type>
        $parts = explode('_', $collation);
        return (object)['encoding' => reset($parts), 'collation' => $collation];
    }

    /**
     * Сохраняет переданную строку в базу данных
     * @param DataRow $row строка для сохранения
     * @param string|null $idField поле для автоинкремента, если сложный запрос
     * @return bool
     * @throws DataModelException
     * @testFunction testDataTableSaveRow
     */
    public function SaveRow($row, $idField = null, $convert = true)
    {
        if (!$row->changed) {
            return false;
        }

        $tables = [];
        $idFields = [];
        $notNullFields = [];
        $fields = $row->properties;
        foreach ($fields as $field) {
            if (in_array('PRI_KEY', $field->flags)) {
                $idFields[] = strtolower($field->name);
            }
            if (in_array('NOT_NULL', $field->flags)) {
                $notNullFields[] = strtolower($field->name);
            }
            $table = (isset($field->originalTable) ? $field->originalTable : $field->table); 
            if($table) {
                $tables[$field->db.'.'.$table] = $field->db.'.'.$table;
            }
        }

        $table = reset($tables);

        if($idField && empty($idFields)) {
            $idFields[] = $idField;
        }

        if (empty($idFields)) {
            throw new DataModelException('table does not have and autoincrement and can not be saved in standart mode');
        }

        $encoding = $this->_getTableEncoding($table);

        // устанавливаем кодировку клиента      
        $this->_point->Query('set names ' . $encoding->encoding, (object)['type' => DataAccessPoint::QueryTypeNonInfo]);

        $fieldValues = [];
        foreach ($row as $key => $value) {
            if ($row->IsPropertyChanged($key, $convert)) {
                $fieldValues[$key] = $encoding->encoding != Encoding::UTF8 && $convert ? Encoding::Convert((string)$value, $encoding->encoding) : $value;
            }
        }

        $isFilled = false;
        foreach ($idFields as $field) {
            if ($row->{$field}) {
                $isFilled = true;
            }
        }

        if ($isFilled) {
            if (!empty($fieldValues)) {
                // есть обновляем
                $flt = [];
                foreach ($idFields as $field) {
                    $flt[] = $field . '=\'' . $row->{$field} . '\'';
                }

                $res = $this->_point->Update($table, $fieldValues, implode(' and ', $flt));
                if ($res->affected == 0 && $res->error) {
                    return false;
                }
            }
        } else {
            $res = $this->_point->Insert($table, $fieldValues);
            if ($res->affected == 0) {
                return false;
            }
            
            // если это ID то сохраняем
            if (count($idFields) == 1) {
                foreach ($idFields as $f) {
                    $row->$f = $res->insertid;
                }
            }
            
        }


        $row->UpdateOriginal();

        return true;
    }

    /**
     * Удаляет строку
     * @param DataRow $row строка
     * @return NonQueryInfo
     * @testFunction testDataTableDeleteRow
     */
    public function DeleteRow($row)
    {
        $tables = [];
        $idFields = [];
        $fields = $row->properties;
        foreach ($fields as $field) {
            if (in_array('PRI_KEY', $field->flags)) {
                $idFields[] = $field;
            }
            $table = (isset($field->originalTable) ? $field->originalTable : $field->table); 
            if($table) {
                $tables[$table] = $table;
            }
        }

        if (count($tables) != 1) {
            throw new DataModelException('Can not find any table name to use in save operation');
        }
        $table = reset($tables);

        if (empty($idFields)) {
            throw new DataModelException('table does not have and autoincrement and can not be saved in standart mode');
        }

        $condition = [];
        foreach ($idFields as $f) {
            $condition[] = $f->escaped . '=\'' . $row->{$f->name} . '\'';
        }
        
        return $this->_point->Delete($table, implode(' and ', $condition));
    }

    /**
     * Устанавливает строку по выбранному индексу в кэш
     *
     * @param integer $index
     * @param ExtendedObject $data
     * @return void
     * @testFunction testDataTableSet
     */
    public function Set($index, $data)
    {
        $this->_cache->Set($index, $data);
    }

    /**
     * Возвращает таблицу в виде массива
     *
     * @param boolean $noPrefix
     * @return array
     * @testFunction testDataTableToArray
     */
    public function ToArray($noPrefix = false)
    {
        $ret = [];
        foreach ($this as $row) {
            $ret[] = $row->ToArray($noPrefix);
        }
        return $ret;
    }

    /**
     * Сохраняет таблицу
     *
     * @return void
     * @testFunction testDataTableSaveAllRows
     */
    public function SaveAllRows()
    {
        foreach ($this as $row) {
            $this->SaveRow($row);
        }
    }

    /**
     * Удаляет таблицу
     *
     * @return void
     * @testFunction testDataTableDeleteAllRows
     */
    public function DeleteAllRows()
    {
        foreach ($this as $row) {
            $this->DeleteRow($row);
        }
    }

    /**
     * Очищает таблицу
     * @return void
     * @testFunction testDataTableClear
     */
    public function Clear()
    {
        $this->_point->Delete($this->_storage->name, ''); // используется truncate
    }

    /**
     * Устанавливает значение по индексу
     * @param int $offset
     * @param DataRow $value
     * @return void
     * @testFunction testDataTableOffsetSet
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->_cache->Add($value);
        } else {
            $this->_cache->Set($offset, $value);
        }
    }

    /**
     * Проверяет есть ли данные по индексу
     * @param int $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $offset < $this->_cache->Count();
    }

    /**
     * удаляет данные по индексу
     * @param int $offset
     * @return void
     * @testFunction testDataTableOffsetUnset
     */
    public function offsetUnset($offset)
    {
        $this->_cache->DeleteAt($offset);
    }

    /**
     * Возвращает значение по индексу
     *
     * @param int $offset
     * @return DataRow
     * @testFunction testDataTableOffsetGet
     */
    public function offsetGet($offset)
    {
        return $this->Item($offset);
    }
}
