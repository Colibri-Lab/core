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
use Colibri\Utils\Logs\Logger;
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
    protected ?Storage $_storage = null;

    protected string $_returnAsExtended;

    protected static $fullSelection = false;

    /**
     * Конструктор
     * @param DataAccessPoint $point точка доступа
     * @param IDataReader|null $reader ридер
     * @param string|\Closure $returnAs возвращать в виде класса
     * @param Storage|null $storage хранилище
     * @return void
     */
    public function __construct(
        DataAccessPoint $point,
        IDataReader $reader = null,
        string $returnAs = 'Colibri\\Data\\Storages\\Models\\DataRow',
        ?Storage $storage = null
    ) {
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

    private static function _replaceFields(string $value, Storage $storage): string
    {
        $res = preg_match_all('/\{([^\}]+)\}/', $value, $matches, \PREG_SET_ORDER);
        if ($res > 0) {
            foreach ($matches as $match) {
                if(preg_match('/[\s\":;]/', $match[0]) === 0) {
                    $value = str_replace(
                        $match[0],
                        $storage->GetRealFieldName($match[1]),
                        $value
                    );
                }
            }
        }
        return $value;
    }

    protected static function _loadByFilter(
        Storage $storage,
        int $page = -1,
        int $pagesize = 20,
        string $filter = null,
        string $order = null,
        array $params = [],
        bool $calculateAffected = true
    ): ?static {
        $joinTables = isset($params['__joinTables']) ? ' ' . implode(' ', $params['__joinTables']) : '';
        unset($params['__joinTables']);
        $groupBy = isset($params['__groupBy']) ? ' ' . $params['__groupBy'] : '';
        unset($params['__groupBy']);
        $selectFields = isset($params['__selectFields']) ? ' ' . $params['__selectFields'] : '';
        unset($params['__selectFields']);

        $additionalParams = [
            'page' => $page,
            'pagesize' => $pagesize,
            'params' => $params
        ];
        $filter = $filter ? ['('.$filter.')'] : [];
        if(!self::$fullSelection &&
            (isset($storage?->{'params'}['softdeletes']) && $storage?->{'params'}['softdeletes'])) {
            $filter[] = $storage->table . '.' . $storage->name . '_datedeleted is null';
        }
        $additionalParams['type'] = $calculateAffected ?
            DataAccessPoint::QueryTypeReader : DataAccessPoint::QueryTypeBigData;
        return self::LoadByQuery(
            $storage,
            'select '. ($selectFields ? $selectFields : '*') .' from ' . $storage->table . $joinTables .
                (!empty($filter) ? ' where ' . implode(' and ', $filter) : '') .
                ($groupBy ? ' group by ' . $groupBy : '') . ($order ? ' order by ' . $order : ''),
            $additionalParams
        );
    }

    public static function LoadByQuery(
        Storage|string $storage,
        string $query,
        array $params
    ): ?static {
        if (is_string($storage)) {
            $storage = Storages::Create()->Load($storage);
        }

        $query = self::_replaceFields($query, $storage);

        list(, $rowClass) = $storage->GetModelClasses();

        $reader = $storage->accessPoint->Query($query, $params);
        if ($reader instanceof IDataReader) {
            return new static ($storage->accessPoint, $reader, $rowClass, $storage);
        } else {
            App::$log->debug($reader->error . ' ' . $reader->query);
            return null;
        }
    }

    protected static function DeleteByFilter(
        Storage|string $storage,
        string $filter
    ): bool {
        if (is_string($storage)) {
            $storage = Storages::Create()->Load($storage);
        }

        $filter = self::_replaceFields($filter, $storage);

        $params = (object)$storage?->{'params'};
        if($params?->{'softdeletes'} === true) {
            $res = $storage->accessPoint->Update(
                $storage->table,
                [$storage->name . '_datedeleted' => DateHelper::ToDbString()],
                $filter
            );
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

    protected static function UpdateByFilter(
        Storage|string $storage,
        string $filter,
        array $fields
    ): bool {
        if (is_string($storage)) {
            $storage = Storages::Create()->Load($storage);
        }

        $filter = self::_replaceFields($filter, $storage);


        $newFields = [];
        foreach($fields as $key => $value) {
            if(substr($value, 0, 1) === '^') {
                $value = self::_replaceFields($value, $storage);
            }
            $newFields[$storage->GetRealFieldName($key)] = $value;
        }

        $res = $storage->accessPoint->Update(
            $storage->table,
            $newFields,
            $filter
        );
        if (!$res->error) {
            return true;
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
    public function SaveRow(
        DataRow|BaseDataRow $row,
        ?string $idField = null,
        ?bool $convert = true
    ): QueryInfo|bool {

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
                } elseif ($field && in_array($field->{'type'}, [
                    'integer',
                    'int',
                    'smallint',
                    'tinyint',
                    'medium',
                    'bigint',
                    'decimal',
                    'numeric'
                ])) {
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
            $res = $this->_storage->accessPoint->Insert(
                $this->_storage->table,
                $fieldValues,
                '',
                $params
            );
            if ($res->insertid == 0) {
                App::$log->debug($res->error . ' query: ' . $res->query);
                return $res;
            }
            $row->$idf = $res->insertid;
            $row->$idc = DateHelper::ToDBString(time());
            $row->$idm = DateHelper::ToDBString(time());
        } else {
            $res = $this->_storage->accessPoint->Update(
                $this->_storage->table,
                $fieldValues,
                $idf . '=' . $id,
                $params
            );
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

        $langModule = App::$moduleManager->lang;

        $stream = File::Create($file);
        $header = [];
        foreach ($this->_storage->fields as $field) {
            $header[] = $field->name ? Encoding::Convert($field->name, Encoding::CP1251, Encoding::UTF8) : null;
        }
        fputcsv($stream->stream, $header, ';');
        $header = [];
        foreach ($this->_storage->fields as $field) {
            if($langModule) {
                $header[] = $field->desc ? $langModule->Translate(
                    Encoding::Convert($field->desc, Encoding::CP1251, Encoding::UTF8)
                ) : null;
            } else {
                $header[] = $field->desc ? Encoding::Convert($field->desc, Encoding::CP1251, Encoding::UTF8) : null;
            }
        }
        fputcsv($stream->stream, $header, ';');

        foreach ($this->getIterator() as $row) {
            $ar = (array) $row->Original();
            $r = [];
            foreach ($this->_storage->fields as $field) {
                $val = $ar[$this->_storage->GetRealFieldName($field->name)];
                $r[] = $val ? Encoding::Convert($val, Encoding::CP1251, Encoding::UTF8) : null;
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
        $langModule = App::$moduleManager->lang;

        $stream = XmlNode::LoadNode('<table></table>', 'utf-8');
        $header = [];
        foreach ($this->_storage->fields as $field) {
            if($langModule) {
                $header[$field->name] = $langModule->Translate($field->desc);
            } else {
                $header[$field->name] = $field->desc;
            }
        }
        $stream->Append(XmlNode::LoadNode(XmlHelper::Encode($header, 'row')));

        foreach ($this->getIterator() as $row) {
            $r = [];
            foreach ($this->_storage->fields as $field) {
                $r[$field->name] = (string)$row->{$field->name};
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
    public function ImportCSV(string $file, int $firstrow = 1, ?Logger $logger = null): bool
    {
        $stream = File::Open($file);

        $header = fgetcsv($stream->stream, 0, ';');
        $this->Load('select * from ' . $this->_storage->name . ' where false');
        $hasErrors = false;
        while ($row = fgetcsv($stream->stream, 0, ';')) {
            if ($firstrow-- > 1) {
                continue;
            }

            $datarow = $this->CreateEmptyRow();
            foreach ($row as $index => $v) {
                $datarow->{$header[$index]} = Encoding::Convert($row[$index], Encoding::UTF8, Encoding::CP1251);
            }
            $res = $this->SaveRow($datarow);
            if($res !== true) {
                $hasErrors = true;
                if($logger) {
                    $logger->emergency(Debug::ROut($res));
                }
            }

        }
        return $hasErrors;
    }

    /**
     * Импортировать из XML
     * @param string $file файл источник
     * @param int $firstrow номер строки, с которой начинаются данные
     * @return void
     */
    public function ImportXML(string $file, int $firstrow = 1, ?Logger $logger = null): bool
    {
        $xml = XmlNode::Load($file, true);
        $rows = $xml->Query('//row');
        $this->Load('select * from ' . $this->_storage->name . ' where false');
        $hasErrors = false;
        foreach ($rows as $row) {
            if ($firstrow-- > 1) {
                continue;
            }
            $row = XmlHelper::ToObject($row->xml);
            $datarow = $this->CreateEmptyRow();
            foreach ($row as $k => $v) {
                $datarow->$k = $row->$k;
            }
            $res = $this->SaveRow($datarow);
            if($res !== true) {
                $hasErrors = true;
                if($logger) {
                    $logger->emergency(Debug::ROut($res));
                }
            }
        }
        return $hasErrors;
    }

    protected static function _loadFromFileXML(
        Storage|string $storage,
        string|File $file,
        string $tag,
        array $fieldsMap,
        array $additionalFields = []
    ): bool {

        if (is_string($storage)) {
            $storage = Storages::Create()->Load($storage);
        }

        $variables = [];
        $values = [];
        foreach($fieldsMap as $key => $value) {
            $variables[] = $key;
            if(is_array($value)) {
                foreach($value as $v) {
                    $values[] = self::_replaceFields($v, $storage);
                }
            } else {
                $values[] = self::_replaceFields($value, $storage);
            }
        }

        $additionalFieldsString = [];
        foreach($additionalFields as $key => $value) {
            $key = self::_replaceFields($key, $storage);
            $additionalFieldsString[] = $key . '=\'' . $value . '\'';
        }

        $result = $storage->accessPoint->Query('
            LOAD XML LOCAL INFILE \''.($file instanceof File ? $file->path : $file) . '\'
            INTO TABLE '.$storage->table.'
            CHARACTER SET utf8mb4
            ROWS IDENTIFIED BY \''.$tag.'\'
            ('.implode(',', $variables).')
            SET '.implode(',', $additionalFieldsString).','.implode(',', $values).';
        ', [
            'type' => DataAccessPoint::QueryTypeNonInfo
        ]);

        if($result->error) {
            throw new DataModelException($result->error);
        }

        return true;

    }

    public static function SetFullSelect(bool $value)
    {
        static::$fullSelection = $value;
    }

}
