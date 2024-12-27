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
use Colibri\Data\Models\DataCollection as BaseDataTable;
use Colibri\Data\Models\DataRow as BaseDataRow;
use Colibri\Data\Models\DataTableIterator;
use Colibri\Data\SqlClient\IDataReader;
use Colibri\Common\DateHelper;
use Colibri\IO\FileSystem\File;
use Colibri\Common\Encoding;
use Colibri\Common\XmlHelper;
use Colibri\Data\DataAccessPointsException;
use Colibri\Data\Storages\Storages;
use Colibri\Utils\Debug;
use Colibri\Utils\Logs\Logger;
use Colibri\Xml\XmlNode;
use Colibri\Utils\ExtendedObject;
use Colibri\Data\Models\DataModelException;
use Colibri\Data\NoSqlClient\ICommandResult;
use Colibri\Data\SqlClient\QueryInfo;

/**
 * Таблица, представление данных в хранилище
 * @author Vahan P. Grigoryan
 * @package Colibri\Data\Storages\Models
 */
class DataCollection extends BaseDataTable
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
     * @param DataAccessPoint $point
     * @param ?ICommandResult $result
     * @param string $returnAs
     * @param Storage|null $storage хранилище
     * @return void
     */
    public function __construct(
        DataAccessPoint $point,
        ?ICommandResult $result = null,
        string $returnAs = 'Colibri\\Data\\Models\\DataRow',
        ?Storage $storage = null
    ) {
        $this->_returnAsExtended = $returnAs;
        parent::__construct($point, $result);
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

    protected static function _replaceFields(string $value, Storage $storage): string
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
        ?array $query = null,
        ?array $filter = null,
        ?array $order = null
    ): ?static {
        return self::LoadByQuery($storage, $query, $filter, $order, $page, $pagesize);
    }

    public static function LoadByQuery(
        Storage|string $storage,
        ?array $query = null,
        ?array $filters = null,
        ?array $sort = null,
        int $page = -1,
        int $pagesize = 20
    ): ?static {
        if (is_string($storage)) {
            $storage = Storages::Create()->Load($storage);
        }

        // TODO добавить запрос на проверку удаленных записей

        [, $rowClass] = $storage->GetModelClasses();

        $result = $storage->accessPoint->ExecuteCommand('SelectDocuments', $storage->table, $query, $filters, [], [], $sort, $page, $pagesize);
        if (!$result->Error()) {
            return new static ($storage->accessPoint, $result, $rowClass, $storage);
        } else {
            app_debug($result);
            return null;
        }
    }

    protected static function DeleteByFilter(
        Storage|string $storage,
        array $filter
    ): bool {
        if (is_string($storage)) {
            $storage = Storages::Create()->Load($storage);
        }

        $params = (object)$storage?->{'params'};
        if($params?->{'softdeletes'} === true) {
            [, $filters, ] = $storage->accessPoint->ProcessFilters($storage, '', $filter, '', '');
            $updateData = $storage->accessPoint->ProcessMutationData(['datedeleted' => DateHelper::ToDbString()], 'update');
            $res = $storage->accessPoint->ExecuteCommand(
                'UpdateDocuments',
                $storage->table,
                $filters,
                $updateData
            );
            if (!$res->Error()) {
                return true;
            }
        } else {
            [, $filters, ] = $storage->accessPoint->ProcessFilters($storage, '', $filter, '', '');
            $res = $storage->accessPoint->ExecuteCommand('DeleteDocuments', $storage->table, $filters);
            if (!$res->error) {
                return true;
            }
        }

        app_debug(['Error', $res]);
        return false;
    }

    protected static function RestoreByFilter(
        Storage|string $storage,
        array $filter
    ): bool {
        if (is_string($storage)) {
            $storage = Storages::Create()->Load($storage);
        }

        $params = (object)$storage?->{'params'};
        if($params?->{'softdeletes'} === true) {
            [, $filters, ] = $storage->accessPoint->ProcessFilters($storage, '', $filter, '', '');
            $updateData = $storage->accessPoint->ProcessMutationData(['datedeleted' => null], 'update');
            $res = $storage->accessPoint->ExecuteCommand(
                'UpdateDocuments',
                $storage->table,
                $filters,
                $updateData
            );
            if (!$res->Error()) {
                return true;
            }
        }

        app_debug(['Error', $res]);
        return false;
    }

    protected static function UpdateByFilter(
        Storage|string $storage,
        array $filter,
        array $fields
    ): bool {
        if (is_string($storage)) {
            $storage = Storages::Create()->Load($storage);
        }
        $res = $storage->accessPoint->ExecuteCommand(
            'UpdateDocuments',
            $storage->table,
            $filter,
            $fields
        );
        if (!$res->Error()) {
            return true;
        }
        app_debug(['Error', $res]);
        return false;
    }

    /**
     * Сохраняет переданную строку в базу данных
     * @param DataRow|BaseDataRow $row строка для сохранения
     * @param string|null $idField поле для автоинкремента, если не найдется в таблице
     * @return ICommandResult|bool
     * @throws DataModelException
     */
    public function SaveRow(
        DataRow|BaseDataRow $row,
        ?string $idField = null,
        ?bool $convert = true
    ): ICommandResult|bool {

        $idf = $this->_storage->GetRealFieldName('id');
        $idc = $this->_storage->GetRealFieldName('datecreated');
        $idm = $this->_storage->GetRealFieldName('datemodified');
        $id = $row->id;

        // получаем сконвертированные данные
        $data = $this->_storage->accessPoint->ProcessMutationData($row, !!$id ? 'update' : 'insert');
        if (!$id) {
            $res = $this->_storage->accessPoint->ExecuteCommand(
                'InsertDocument',
                $this->_storage->table,
                (object)$data
            );
            if ($res->Error()) {
                app_debug('Error', $res);
                return $res;
            }
            $queryInfo = $res->QueryInfo();
            $row->$idf = $queryInfo->returned[0];
            $row->$idc = $data['datecreated'];
            $row->$idm = $data['datemodified'];
        } else {

            $res = $this->_storage->accessPoint->ExecuteCommand(
                'UpdateDocument',
                $this->_storage->table,
                (int)$id,
                (object)$data
            );
            if ($res->Error()) {
                app_debug('Error', $res);
                return $res;
            }

            $row->$idm = $data['datemodified'];

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

        $langModule = App::$moduleManager->{'lang'};

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
        $langModule = App::$moduleManager->{'lang'};

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
        $this->Load('select * from ' . $this->_storage->table . ' where false');
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

    protected static function _exportToFileJson(
        Storage|string $storage,
        string|File $file,
        array $fields,
        ?array $filter = null
    ): bool {
        if (is_string($storage)) {
            $storage = Storages::Create()->Load($storage);
        }

        if($storage->accessPoint->dbms !== DataAccessPoint::DBMSTypeRelational) {
            throw new DataAccessPointsException('This method works only for relational databases');
        }

        $fieldsConverted = [];
        foreach($fields as $field) {
            $fieldsConverted[] = self::_replaceFields($field, $storage);
        }

        $filters = [];
        foreach($filter as $field => $value) {
            $filters[] = self::_replaceFields($field, $storage) . '=\''.$value.'\'';
        }

        $result = $storage->accessPoint->Query('
            SELECT '.implode(',', $fieldsConverted).'
            FROM '.$storage->table.'
            WHERE ' . implode(' and  ', $filters) . '
            INTO OUTFILE \'' . ($file instanceof File ? $file->path : $file) .'\'
            FIELDS TERMINATED BY \',\' OPTIONALLY ENCLOSED BY \'"\'
            LINES TERMINATED BY \',\\n\'
        ', ['type' => DataAccessPoint::QueryTypeNonInfo]);
        if($result->error) {
            throw new DataModelException($result->error);
        }

        return true;
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

        if($storage->accessPoint->dbms !== DataAccessPoint::DBMSTypeRelational) {
            throw new DataAccessPointsException('This method works only for relational databases');
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
