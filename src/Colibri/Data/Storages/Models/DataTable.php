<?php

/**
 * Models
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Data\Storages\Models
 */

namespace Colibri\Data\Storages\Models;

use Colibri\App;
use Colibri\Data\Storages\Storage;
use Colibri\Data\DataAccessPoint;
use Colibri\Data\Models\DataTable as BaseDataTable;
use Colibri\Data\Models\DataRow as BaseDataRow;
use Colibri\Data\Models\DataTableIterator;
use Colibri\Data\SqlClient\IDataReader;
use Colibri\Common\DateHelper;
use Colibri\IO\FileSystem\File;
use Colibri\Common\Encoding;
use Colibri\Common\XmlHelper;
use Colibri\Data\Storages\Storages;
use Colibri\Utils\Debug;
use Colibri\Xml\XmlNode;
use Colibri\Utils\ExtendedObject;
use Colibri\Data\Models\DataModelException;
use Colibri\Data\SqlClient\QueryInfo;

/**
 * Таблица, представление данных в хранилище
 * @author Vahan P. Grigoryan
 * @package Colibri\Data\Storages\Models
 */
class DataTable extends BaseDataTable
{

    /**
     * Хранилише
     * @var Storage
     */
    protected ? Storage $_storage = null;

    protected string $_returnAsExtended;

    /**
     * Конструктор
     * @param DataAccessPoint $point точка доступа
     * @param IDataReader|null $reader ридер
     * @param string|\Closure $returnAs возвращать в виде класса
     * @param Storage|null $storage хранилище
     * @return void 
     */
    public function __construct(DataAccessPoint $point, IDataReader $reader = null, string $returnAs = 'Colibri\\Data\\Storages\\Models\\DataRow', ? Storage $storage = null)
    {
        $this->_returnAsExtended = $returnAs;
        parent::__construct($point, $reader);
        $this->_storage = $storage;
    }

    /**
     * Возвращает итератор
     * @return DataTableIterator итератор
     */
    public function getIterator(): DataTableIterator
    {
        return new DataTableIterator($this);
    }

    /**
     * Возвращает обьект хранилище
     * @return Storage 
     */
    public function Storage(): Storage
    {
        return $this->_storage;
    }

    /**
     * Создает обьект данных представления строки
     *
     * @param ExtendedObject $result
     * @return mixed
     */
    protected function _createDataRowObject(mixed $result): mixed
    {
        $className = $this->_returnAsExtended;
        if (is_callable($className)) {
            $className = $className($this, $result);
        }

        return new $className($this, $result, $this->_storage);
    }

    public static function LoadByQuery(Storage|string $storage, string $query, array $params): ?static
    {
        if (is_string($storage)) {
            $storage = Storages::Create()->Load($storage);
        }

        $res = preg_match_all('/\{([^\}]+)\}/', $query, $matches, \PREG_SET_ORDER);
        if ($res > 0) {
            foreach ($matches as $match) {
                $query = str_replace($match[0], $storage->name . '_' . $match[1], $query);
            }
        }

        list(, $rowClass) = $storage->GetModelClasses();

        $reader = $storage->accessPoint->Query($query, $params);
        if ($reader instanceof IDataReader) {
            return new static ($storage->accessPoint, $reader, $rowClass, $storage);
        } else {
            App::$log->debug($reader->error . ' ' . $reader->query);
            return null;
        }
    }

    protected static function DeleteByFilter(Storage|string $storage, string $filter): bool
    {
        if (is_string($storage)) {
            $storage = Storages::Create()->Load($storage);
        }

        $res = preg_match_all('/\{([^\}]+)\}/', $filter, $matches, \PREG_SET_ORDER);
        if ($res > 0) {
            foreach ($matches as $match) {
                $filter = str_replace($match[0], $storage->name . '_' . $match[1], $filter);
            }
        }

        if($storage?->{'params'}?->{'softdeletes'} === true) {
            $res = $storage->accessPoint->Update($storage->table, [$storage->name . '_datedeleted' => DateHelper::ToDbString()], $filter);
            if (!$res->error) {
                return true;
            }    
        } else {
            $res = $storage->accessPoint->Delete($storage->table, $filter);
            if (!$res->error) {
                return true;
            }    
        }


        App::$log->debug('Error: ' . $res->error . ', query: ' . $res->query);
        return false;
    }

    /**
     * Сохраняет переданную строку в базу данных
     * @param DataRow|BaseDataRow $row строка для сохранения
     * @param string|null $idField поле для автоинкремента, если не найдется в таблице
     * @return QueryInfo|bool
     * @throws DataModelException
     */
    public function SaveRow(DataRow|BaseDataRow $row, ?string $idField = null, ?bool $convert = true): QueryInfo|bool
    {

        $idf = $this->_storage->GetRealFieldName('id');
        $idc = $this->_storage->GetRealFieldName('datecreated');
        $idm = $this->_storage->GetRealFieldName('datemodified');
        $id = $row->id;

        // получаем сконвертированные данные
        $data = $row->GetData();

        unset($data[$idf]);
        $data[$idm] = DateHelper::ToDBString(time());

        $params = [];
        $fieldValues = [];
        foreach ($data as $key => $value) {
            if (!$id || $row->IsPropertyChanged($key)) {
                $fieldName = $this->_storage->GetFieldName($key);
                /** @var \Colibri\Data\Storages\Fields\Field $field */
                $field = $this->_storage->fields->$fieldName ?? null;
                $paramType = 'string';
                if ($field && in_array($field->{'type'}, ['blob', 'tinyblob', 'longblob'])) {
                    $paramType = 'blob';
                } elseif ($field && in_array($field->{'type'}, ['integer', 'int', 'smallint', 'tinyint', 'medium', 'bigint', 'decimal', 'numeric'])) {
                    $paramType = 'integer';
                } elseif ($field && in_array($field->{'type'}, ['double', 'float'])) {
                    $paramType = 'double';
                } elseif ($field && in_array($field->{'type'}, ['bool', 'boolean'])) {
                    $paramType = 'integer';
                    $value = $value === true ? 1 : 0;
                }

                $params[$key] = $value;
                $fieldValues[$key] = '[[' . $key . ':' . $paramType . ']]';
            }
        }

        if (empty($fieldValues)) {
            return $id != 0;
        }

        if (!$id) {
            $res = $this->_storage->accessPoint->Insert($this->_storage->table, $fieldValues, '', $params);
            if ($res->insertid == 0) {
                App::$log->debug($res->error . ' query: ' . $res->query);
                return $res;
            }
            $row->$idf = $res->insertid;
            $row->$idc = DateHelper::ToDBString(time());
            $row->$idm = DateHelper::ToDBString(time());
        } else {
            $res = $this->_storage->accessPoint->Update($this->_storage->table, $fieldValues, $idf . '=' . $id, $params);
            if ($res->error) {
                App::$log->debug($res->error . ' query: ' . $res->query);
                return $res;
            }
        }

        return true;
    }


    /**
     * Экспорт в csv
     * @param string $file файл, куда выгружать
     * @return void 
     */
    public function ExportCSV(string $file): void
    {
        if (File::Exists($file)) {
            File::Delete($file);
        }

        $stream = File::Create($file);
        $header = [];
        foreach ($this->_storage->fields as $field) {
            $header[] = Encoding::Convert($field->name, Encoding::CP1251, Encoding::UTF8);
        }
        fputcsv($stream->stream, $header, ';');
        $header = [];
        foreach ($this->_storage->fields as $field) {
            $header[] = Encoding::Convert($field->desc, Encoding::CP1251, Encoding::UTF8);
        }
        fputcsv($stream->stream, $header, ';');

        foreach ($this->getIterator() as $row) {
            $ar = (array) $row->Original();
            $r = [];
            foreach ($this->_storage->fields as $field) {
                $val = $ar[$this->_storage->GetRealFieldName($field->name)];
                $r[] = Encoding::Convert($val, Encoding::CP1251, Encoding::UTF8);
            }
            fputcsv($stream->stream, $r, ';');
        }

        $stream->close();
    }

    /**
     * Выгрузить в XML
     * @param string $file файл, куда выгружать
     * @return void 
     */
    public function ExportXML(string $file): void
    {
        if (File::Exists($file)) {
            File::Delete($file);
        }

        $stream = XmlNode::LoadNode('<table></table>', 'utf-8');
        $header = [];
        foreach ($this->_storage->fields as $field) {
            $header[$field->name] = $field->desc;
        }
        $stream->Append(XmlNode::LoadNode(XmlHelper::Encode($header, 'row')));

        foreach ($this->getIterator() as $row) {
            $r = [];
            foreach ($this->_storage->fields as $field) {
                $r[$field->name] = $row->{$field->name};
            }
            $stream->Append(XmlNode::LoadNode(XmlHelper::Encode($r, 'row')));
        }

        $stream->Save($file);
    }

    /**
     * Выгрузить в XML
     * @param string $file файл, куда выгружать
     * @return void 
     */
    public function ExportJson(string $file): void
    {

        if (File::Exists($file)) {
            File::Delete($file);
        }

        File::Create($file, true);
        File::Append($file, '[' . "\n");

        foreach ($this as $row) {
            File::Append($file, $row->ToJSON() . ", \n");
        }

        File::Append($file, ']');

    }

    /**
     * Импортировать из CSV
     * @param string $file файл источник
     * @param int $firstrow номер строки, с которой начинаются данные
     * @return void 
     */
    public function ImportCSV(string $file, int $firstrow = 1): void
    {
        $stream = File::Open($file);

        $header = fgetcsv($stream->stream, 0, ';');
        $dataTable = DataTable::Create($this->_storage->accessPoint);
        $dataTable->Load('select * from ' . $this->_storage->name . ' where false');
        while ($row = fgetcsv($stream->stream, 0, ';')) {
            if ($firstrow-- > 1) {
                continue;
            }

            $datarow = $dataTable->CreateEmptyRow();
            foreach ($row as $index => $v) {
                $datarow->{$header[$index]} = Encoding::Convert($row[$index], Encoding::UTF8, Encoding::CP1251);
            }
            $dataTable->SaveRow($datarow);
        }
    }

    /**
     * Импортировать из XML
     * @param string $file файл источник
     * @param int $firstrow номер строки, с которой начинаются данные
     * @return void 
     */
    public function ImportXML(string $file, int $firstrow = 1): void
    {
        $xml = XmlNode::Load($file, true);
        $rows = $xml->Query('//row');
        $dataTable = DataTable::Create($this->_storage->accessPoint);
        $dataTable->Load('select * from ' . $this->_storage->name . ' where false');
        foreach ($rows as $row) {
            if ($firstrow-- > 1) {
                continue;
            }
            $row = XmlHelper::Decode($row->xml);
            $datarow = $dataTable->CreateEmptyRow();
            foreach ($row as $k => $v) {
                $datarow->$k = $row->$k;
            }
            $dataTable->SaveRow($datarow);
        }
    }
}