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
use Colibri\Data\NoSqlClient\ICommandResult;
use Colibri\Data\SqlClient\QueryInfo;
use Colibri\Events\Event;
use Colibri\Events\EventDispatcher;
use Colibri\Events\EventsContainer;

/**
 * Table representing data in the storage.
 * @author Vahan P. Grigoryan
 * @package Colibri\Data\Storages\Models
 */
class DataTable extends BaseDataTable
{
    /**
     * Storage associated with the data table.
     * @var Storage
     */
    protected ?Storage $_storage = null;

    /**
     * The class name or closure to return for each row in the data table.
     * @var string|\Closure
     */
    protected string|\Closure $_returnAsExtended;

    /**
     * Indicates whether to perform a full selection of fields.
     * @var bool
     */
    protected static $fullSelection = false;

    /**
     * The parent object for lookup purposes.
     * @var mixed
     */
    protected mixed $_isLookupOf = null;

    /**
     * Sets the parent object for lookup purposes.
     * @param mixed $parentObject The parent object.
     * @return void
     */
    public function isLookUpOf(mixed $parentObject) {
        $this->_isLookupOf = $parentObject;
    }

    /**
     * Constructs a new DataTable instance.
     * @param DataAccessPoint $point The data access point.
     * @param IDataReader|null $reader The data reader.
     * @param string|\Closure $returnAs The class name or closure to return for each row.
     * @param Storage|null $storage The storage instance.
     * @return void
     */
    public function __construct(
        DataAccessPoint $point,
        ?IDataReader $reader = null,
        string $returnAs = 'Colibri\\Data\\Storages\\Models\\DataRow',
        ?Storage $storage = null
    ) {
        $this->_returnAsExtended = $returnAs;
        parent::__construct($point, $reader);
        $this->_storage = $storage;
    }

    /**
     * Returns an iterator.
     * @return DataTableIterator The iterator.
     */
    public function getIterator(): DataTableIterator
    {
        return new DataTableIterator($this);
    }

    /**
     * Returns the storage object.
     * @return Storage
     */
    public function Storage(): Storage
    {
        return $this->_storage;
    }

    /**
     * Creates a data row object.
     *
     * @param ExtendedObject $result The result object.
     * @return mixed The data row object.
     */
    protected function _createDataRowObject(mixed $result): mixed
    {
        $className = $this->_returnAsExtended;
        if (is_callable($className)) {
            $className = $className($this, $result);
        }

        $return = new $className($this, $result, $this->_storage);
        $return->isLookupOf($this->_isLookupOf);
        return $return;
    }

    /**
     * Replace field placeholders in the given value with their real field names from the storage.
     * @param string|null $value The value containing field placeholders.
     * @param Storage $storage The storage instance.
     * @return string|null The value with field placeholders replaced by real field names.
     */
    protected static function _replaceFields(?string $value, Storage $storage): ?string
    {
        if($value === null) {
            return $value;
        }
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

    /**
     * Loads data rows based on the given filter and parameters.
     *
     * @param Storage $storage The storage instance.
     * @param int $page The page number for pagination.
     * @param int $pagesize The number of items per page.
     * @param string|null $filter The filter condition.
     * @param string|null $order The order condition.
     * @param array $params Additional parameters for the query.
     * @param bool $calculateAffected Whether to calculate affected rows.
     * @return static|null The loaded data table or null if an error occurred.
     */
    protected static function _loadByFilter(
        Storage $storage,
        int $page = -1,
        int $pagesize = 20,
        ?string $filter = null,
        ?string $order = null,
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
            $filter[] = $storage->accessPoint->SoftDeleteCheck($storage->GetRealFieldName('datedeleted'), $storage->table);
        }
        $additionalParams['type'] = $calculateAffected ?
            DataAccessPoint::QueryTypeReader : DataAccessPoint::QueryTypeBigData;
        return self::LoadByQuery(
            $storage,
            'select '. ($selectFields ? $selectFields : '*') .' from ' . $storage->accessPoint->{'symbol'} . $storage->table . $storage->accessPoint->{'symbol'} . $joinTables .
                (!empty($filter) ? ' where ' . implode(' and ', $filter) : '') .
                ($groupBy ? ' group by ' . $groupBy : '') . ($order ? ' order by ' . $order : ''),
            $additionalParams
        );
    }

    /**
     * Load data by string query and parameters.
     *
     * @param Storage $storage The storage instance.
     * @param string $query The SQL query string.
     * @param array $params The parameters for the query.
     * @return static|null The loaded data table or null if an error occurred.
     */
    public static function LoadByQuery(
        Storage $storage,
        string $query,
        array $params
    ): ?static {

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

    /**
     * Delete data rows based on the given filter.
     *
     * @param Storage $storage The storage instance.
     * @param string|null $filter The filter condition.
     * @return bool True if the deletion was successful, false otherwise.
     */
    protected static function DeleteByFilter(
        Storage $storage,
        ?string $filter = null
    ): bool {

        $filter = self::_replaceFields($filter, $storage);

        $params = (object)$storage?->{'params'};
        if(($params?->{'softdeletes'} ?? false) === true) {

            $allowedTypes = $storage->accessPoint->allowedTypes;
            $timestamp = $allowedTypes['timestamp'];
            $timestampGeneric = 'Colibri\\Data\\Storages\\Fields\\' . $timestamp['generic'];
            $now = new $timestampGeneric('now');
            $timestampType = 'string';
            if(method_exists($timestampGeneric, 'ParamTypeName')) {
                eval('$timestampType = ' . $timestampGeneric . '::ParamTypeName();');
            }

            $res = $storage->accessPoint->Update(
                $storage->table,
                [$storage->GetRealFieldName('datedeleted') => '[[datedeleted:'.$timestampType.']]'],
                !$filter ? '1=1' : $filter,
                ['datedeleted' => (string)$now]
            );
            if (!$res->error) {
                return true;
            }
        } else {
            // empty filter means truncate all
            $res = $storage->accessPoint->Delete($storage->table, !$filter ? '' : $filter,);
            if (!$res->error) {
                return true;
            }
        }


        App::$log->debug('Error: ' . $res->error . ', query: ' . $res->query);
        return false;
    }

    /**
     * Restore soft-deleted data rows based on the given filter.
     *
     * @param Storage $storage The storage instance.
     * @param string|null $filter The filter condition.
     * @return bool True if the restoration was successful, false otherwise.
     */
    protected static function RestoreByFilter(
        Storage $storage,
        string $filter
    ): bool {
        
        $filter = self::_replaceFields($filter, $storage);

        $params = (object)$storage?->{'params'};
        if($params?->{'softdeletes'} === true) {

            $allowedTypes = $storage->accessPoint->allowedTypes;
            $timestamp = $allowedTypes['timestamp'];
            $timestampGeneric = 'Colibri\\Data\\Storages\\Fields\\' . $timestamp['generic'];
            $timestampType = 'string';
            $nullValue = null;
            if(method_exists($timestampGeneric, 'ParamTypeName')) {
                eval('$timestampType = ' . $timestampGeneric . '::ParamTypeName();');
                eval('$nullValue = ' . $timestampGeneric . '::Null();');
            }
            $res = $storage->accessPoint->Update(
                $storage->table,
                [$storage->GetRealFieldName('datedeleted') => '[[datedeleted:'.$timestampType.']]'],
                $filter,
                ['datedeleted' => $nullValue]
            );
            if (!$res->error) {
                return true;
            }
        }


        App::$log->debug('Error: ' . $res->error . ', query: ' . $res->query);
        return false;
    }

    /**
     * Delete a specific data row.
     *
     * @param BaseDataRow $row The data row to delete.
     * @return QueryInfo|ICommandResult|bool The result of the deletion operation.
     */
    public function DeleteRow(BaseDataRow $row): QueryInfo|ICommandResult|bool
    {
        return self::DeleteByFilter($this->Storage(), '{id}=' . $row->id);
    }

    /**
     * Restore a specific soft-deleted data row.
     *
     * @param BaseDataRow $row The data row to restore.
     * @return QueryInfo|ICommandResult|bool The result of the restoration operation.
     */
    protected static function UpdateByFilter(
        Storage $storage,
        string $filter,
        array $fields
    ): bool {
        
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
     * Creates a new auto-increment value for the row
     * @param DataRow|BaseDataRow $row The row for which to create the auto-increment value
     * @return mixed The new auto-increment value, typically a timestamp or a unique identifier
     */
    protected function _createNewAutoIncrementValue(DataRow|BaseDataRow $row): mixed
    {
        return DateHelper::Mc();
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

        $isNewRow = !$id;

        if($isNewRow) {
            if(!$this->_storage->accessPoint->hasAutoincrement) {
                $row->id = $this->_createNewAutoIncrementValue($row);
            }
        }

        // получаем сконвертированные данные
        if(! ([$fieldValues, $params] = $row->DataToChange($isNewRow))) {
            return true;
        }

        $this->_storage->accessPoint->Begin();
        if ($isNewRow) {

            $res = $this->_storage->accessPoint->Insert(
                $this->_storage->table,
                $fieldValues,
                $idf,
                $params
            );

            if(!$this->_storage->accessPoint->hasAutoincrement) {
                // need to emulate
                $res->insertid = $params['id'];
            }

            if ($res->insertid == 0 || !!$res->error) {
                app_debug($res->error . ' query: ' . $res->query);
                $this->_storage->accessPoint->Rollback();
                return $res;
            }
            $row->$idf = $res->insertid;
            $row->$idc = $params[$idc];
            $row->$idm = $params[$idm];
        } else {
            $res = $this->_storage->accessPoint->Update(
                $this->_storage->table,
                $fieldValues,
                $idf . '=' . $id,
                $params
            );
            if ($res->error) {
                app_debug($res->error . ' query: ' . $res->query);
                $this->_storage->accessPoint->Rollback();
                return $res;
            }
            $row->$idm = $params[$idm];
        }

        $this->_storage->accessPoint->Commit();

        return true;
    }


    /**
     * Export data to a CSV file.
     *
     * @param string $file The file path to export the CSV to.
     * @return void
     */
    public function ExportCSV(string $file): void
    {
        if (File::Exists($file)) {
            File::Delete($file);
        }

        EventDispatcher::Instance()->Dispatch(new Event($this, EventsContainer::Progress), ['process' => 'ExportCSV', 'progress' => 0, 'data' => 'Starting']);
        
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

        EventDispatcher::Instance()->Dispatch(new Event($this, EventsContainer::Progress), ['process' => 'ExportCSV', 'progress' => 0, 'data' => 'Header exported']);

        $maxitems = $this->Count();
        foreach ($this->getIterator() as $index => $row) {
            $ar = (array) $row->Original();
            $r = [];
            foreach ($this->_storage->fields as $field) {
                $val = $ar[$this->_storage->GetRealFieldName($field->name)];
                if($field?->params['transformer']) {
                    $f = function($field, $value) { 
                        return $value; 
                    };
                    eval('$f = ' . $field?->params['transformer'] . ';');
                    $val = $f($field, $val);
                }
                $r[] = $val ? Encoding::Convert($val, Encoding::CP1251, Encoding::UTF8) : null;
            }
            fputcsv($stream->stream, $r, ';');
            EventDispatcher::Instance()->Dispatch(new Event($this, EventsContainer::Progress), ['process' => 'ExportCSV', 'progress' => ($index + 1) / $maxitems * 100, 'data' => 'Row exported']);
        }

        EventDispatcher::Instance()->Dispatch(new Event($this, EventsContainer::Progress), ['process' => 'ExportCSV', 'progress' => 100, 'data' => 'Completed']);

        $stream->close();

        EventDispatcher::Instance()->Dispatch(new Event($this, EventsContainer::Progress), ['process' => 'ExportCSV', 'progress' => 100, 'data' => 'File saved']);
    }

    /**
     * Export data to an XML file.
     *
     * @param string $file The file path to export the XML to.
     * @return void
     */
    public function ExportXML(string $file): void
    {
        if (File::Exists($file)) {
            File::Delete($file);
        }
        EventDispatcher::Instance()->Dispatch(new Event($this, EventsContainer::Progress), ['process' => 'ExportXML', 'progress' => 0, 'data' => 'Starting']);
        
        $langModule = App::$moduleManager->{'lang'};

        $stream = XmlNode::LoadNode('<table></table>', 'utf-8');
        $header = [];
        $header['datecreated'] = 'datecreated';
        $header['datemodified'] = 'datemodified';
        $header['datedeleted'] = 'datedeleted';
        foreach ($this->_storage->fields as $field) {
            if($langModule) {
                $header[$field->name] = $langModule->Translate($field->desc);
            } else {
                $header[$field->name] = $field->desc;
            }
        }
        $stream->Append(XmlNode::LoadNode(XmlHelper::Encode($header, 'row')));

        EventDispatcher::Instance()->Dispatch(new Event($this, EventsContainer::Progress), ['process' => 'ExportXML', 'progress' => 0, 'data' => 'Header exported']);

        $maxitems = $this->Count();
        foreach ($this->getIterator() as $index => $row) {
            $r = [];
            $r['datecreated'] = (string)$row->{'datecreated'};
            $r['datemodified'] = (string)$row->{'datemodified'};
            $r['datedeleted'] = (string)$row->{'datedeleted'};
            foreach ($this->_storage->fields as $field) {
                $fieldValue = $row->{$field->name};
                if($field?->params['transformer']) {
                    $f = function($field, $value) { 
                        return $value; 
                    };
                    eval('$f = ' . $field?->params['transformer'] . ';');
                    $fieldValue = $f($field, $fieldValue);
                }
                if($fieldValue instanceof \UnitEnum) {
                    $fieldValue = $fieldValue->{'value'};
                }
                if(is_object($row->{$field->name}) && method_exists($row->{$field->name}, 'ToString')) {
                    $r[$field->name] = preg_replace('/[^\x09\x0A\x0D\x20-\x{10FFFF}]/u', '', (string)$fieldValue->ToString());
                } else {
                    $r[$field->name] = preg_replace('/[^\x09\x0A\x0D\x20-\x{10FFFF}]/u', '', (string)$fieldValue);
                }

            }
            $stream->Append(XmlNode::LoadNode(XmlHelper::Encode($r, 'row')));
            EventDispatcher::Instance()->Dispatch(new Event($this, EventsContainer::Progress), ['process' => 'ExportXML', 'progress' => ($index + 1) / $maxitems * 100, 'data' => 'Row exported']);
        }

        EventDispatcher::Instance()->Dispatch(new Event($this, EventsContainer::Progress), ['process' => 'ExportXML', 'progress' => 100, 'data' => 'Completed']);

        $stream->Save($file);

        EventDispatcher::Instance()->Dispatch(new Event($this, EventsContainer::Progress), ['process' => 'ExportXML', 'progress' => 100, 'data' => 'File saved']);
    }

    /**
     * Export data to a JSON file.
     *
     * @param string $file The file path to export the JSON to.
     * @return void
     */
    public function ExportJson(string $file): void
    {

        if (File::Exists($file)) {
            File::Delete($file);
        }

        EventDispatcher::Instance()->Dispatch(new Event($this, EventsContainer::Progress), ['process' => 'ExportJson', 'progress' => 0, 'data' => 'Starting']);

        File::Create($file, true);
        File::Append($file, '[' . "\n");

        $maxitems = $this->Count();
        foreach ($this as $index => $row) {
            File::Append($file, $row->ToJSON() . ", \n");
            EventDispatcher::Instance()->Dispatch(new Event($this, EventsContainer::Progress), ['process' => 'ExportJson', 'progress' => ($index + 1) / $maxitems * 100, 'data' => 'Row exported']);
        }
        
        EventDispatcher::Instance()->Dispatch(new Event($this, EventsContainer::Progress), ['process' => 'ExportJson', 'progress' => 100, 'data' => 'Completed']);
        
        File::Append($file, ']');
        
        EventDispatcher::Instance()->Dispatch(new Event($this, EventsContainer::Progress), ['process' => 'ExportJson', 'progress' => 100, 'data' => 'File saved']);

    }

    /**
     * Export data to an SQL file.
     *
     * @param string $file The file path to export the SQL to.
     * @param array $exceptFields Fields to exclude from the export.
     * @param string $primaryKeyName The name of the primary key field.
     * @param array $typeExchange An array for type conversion.
     * @return void
     */
    public function ExportSQL(string $file, array $exceptFields = [], string $primaryKeyName = 'id', array $typeExchange = []): void
    {
        
        $fields = [];
        $createTable = ['DROP TABLE IF EXISTS ' . $this->Storage()->name . ';', 'CREATE TABLE IF NOT EXISTS ' . $this->Storage()->name . ' ('];
        foreach($this->Storage()->fields as $field) {
            if(in_array($field->name, $exceptFields)) {
                continue;
            }
            $fields[] = $field->name;
            $type = isset($typeExchange[$field->type]) ? $typeExchange[$field->type] : strtoupper($field->type);
            $createTable[] = '    ' . $field->name . ' ' . $type . ($field->length ? '('.$field->length.')' : '') . ',';
        }
        $createTable[] = '    datecreated DATETIME,';
        $createTable[] = '    datemodified DATETIME,';
        $createTable[] = '    PRIMARY KEY ('.$primaryKeyName.')';
        $createTable[] = ');' . "\n";
        File::Append($file, implode("\n", $createTable));

        foreach($this as $result) {
            /** @var DataRow $result  */
            $data = $result->DataToChange(true);
            File::Append($file, 'INSERT INTO '.$this->Storage()->name.'(datecreated, datemodified, '.implode(', ', $fields).') VALUES (');
            $values = ['\'' . (string)$result->datecreated . '\'', ($result->modified === null ? 'null' : '\'' . (string)$result->modified . '\'')];
            foreach($fields as $field) {
                if(in_array($field, $exceptFields)) {
                    continue;
                }
                $realField = $this->Storage()->GetRealFieldName($field);
                if($data[1][$realField] === null || $data[1][$realField] === 'null') {
                    $values[] = 'null';
                } elseif (strstr($data[0][$realField], ':string') !== false) {
                    $values[] = '\'' . (string)$data[1][$realField] . '\'';
                } else {
                    $values[] = (string)$data[1][$realField];
                }
            }
            File::Append($file, implode(',', $values));
            File::Append($file, ');' . "\n");
        }
        File::Append($file, "\n" . "\n");
        
    }

    /**
     * Import data from a CSV file.
     * @param string $file The source CSV file.
     * @param int $firstrow The row number where the data starts.
     * @return bool True if the import was successful, false otherwise.
     */
    public function ImportCSV(string $file, int $firstrow = 1, ?Logger $logger = null): bool
    {
        $stream = File::Open($file);
        $maxBytes = $stream->length;

        EventDispatcher::Instance()->Dispatch(new Event($this, EventsContainer::Progress), ['process' => 'ImportCSV', 'progress' => 0, 'data' => 'Starting']);

        $pos_before = ftell($stream->streamp);
        $header = fgetcsv($stream->stream, 0, ';');
        $pos_after = ftell($stream->stream);

        $readedLength = $pos_after - $pos_before;        

        $this->Load('select * from ' . $this->_storage->name . ' where false');
        $hasErrors = false;

        $pos_before = ftell($stream->streamp);
        while ($row = fgetcsv($stream->stream, 0, ';')) {
            if ($firstrow-- > 1) {
                continue;
            }

            $pos_after = ftell($stream->stream);
            $readedLength = $pos_after - $pos_before;        

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

            EventDispatcher::Instance()->Dispatch(new Event($this, EventsContainer::Progress), ['process' => 'ImportCSV', 'progress' => $readedLength / $maxBytes * 100, 'data' => 'Row imported']);

            $pos_before = ftell($stream->streamp);

        }

        EventDispatcher::Instance()->Dispatch(new Event($this, EventsContainer::Progress), ['process' => 'ImportCSV', 'progress' => 100, 'data' => 'Completed']);

        return $hasErrors;
    }

    /**
     * Import data from an XML file.
     * @param string $file The source XML file.
     * @param int $firstrow The row number where the data starts.
     * @return bool True if the import was successful, false otherwise.
     */
    public function ImportXML(string $file, int $firstrow = 1, ?Logger $logger = null): bool
    {
        $xml = XmlNode::Load($file, true);
        EventDispatcher::Instance()->Dispatch(new Event($this, EventsContainer::Progress), ['process' => 'ImportXML', 'progress' => 0, 'data' => 'Starting']);

        $rows = $xml->Query('//row');
        $this->Load('select * from ' . $this->_storage->table . ' where false');
        $hasErrors = false;
        $maxitems = $rows->Count();
        foreach ($rows as $index => $row) {
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

            EventDispatcher::Instance()->Dispatch(new Event($this, EventsContainer::Progress), ['process' => 'ImportXML', 'progress' => ($index + 1) / $maxitems * 100, 'data' => 'Row imported']);

        }

        EventDispatcher::Instance()->Dispatch(new Event($this, EventsContainer::Progress), ['process' => 'ImportXML', 'progress' => 100, 'data' => 'Completed']);

        return $hasErrors;
    }

    /**
     * Export data to a JSON file using a direct SQL query.
     *
     * @param Storage $storage The storage instance.
     * @param string|File $file The file path or File object to export the JSON to.
     * @param array $fields The fields to include in the export.
     * @param array|null $filter Optional filter conditions for the export.
     * @return bool True if the export was successful, false otherwise.
     * @throws DataModelException If there is an error during the export process.
     */
    protected static function _exportToFileJson(
        Storage $storage,
        string|File $file,
        array $fields,
        ?array $filter = null
    ): bool {
        
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

    /**
     * Load data from an XML file into the storage.
     *
     * @param Storage $storage The storage instance.
     * @param string|File $file The XML file path or File object to load data from.
     * @param string $tag The XML tag that identifies each row of data.
     * @param array $fieldsMap A mapping of storage fields to XML fields.
     * @param array $additionalFields Additional fields to set during the load operation.
     * @return bool True if the load was successful, false otherwise.
     * @throws DataModelException If there is an error during the load process.
     */
    protected static function _loadFromFileXML(
        Storage $storage,
        string|File $file,
        string $tag,
        array $fieldsMap,
        array $additionalFields = []
    ): bool {

        
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

    /**
     * Sets the full selection mode for data retrieval.
     *
     * @param bool $value True to enable full selection, false to disable.
     * @return void
     */
    public static function SetFullSelect(bool $value)
    {
        static::$fullSelection = $value;
    }

}
