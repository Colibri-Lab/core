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
 * Class representing a collection of data rows in a storage system.
 * @author Vahan P. Grigoryan
 * @package Colibri\Data\Storages\Models
 */
class DataCollection extends BaseDataTable
{
    /**
     * The storage associated with this data collection.
     * @var Storage
     */
    protected ?Storage $_storage = null;

    /**
     * The class name to return for each data row in the collection.
     * @var string
     */
    protected string $_returnAsExtended;

    /**
     * Indicates whether to perform a full selection of fields in the query.
     * @var bool
     */
    protected static $fullSelection = false;

    /**
     * The parent object for lookup relationships, if applicable.
     * @var mixed
     */
    protected mixed $_isLookupOf = null;

    /**
     * Sets the parent object for lookup relationships.
     *
     * @param mixed $parentObject The parent object to set.
     * @return void
     */
    public function isLookUpOf(mixed $parentObject): void {
        $this->_isLookupOf = $parentObject;
    }

    /**
     * Constructs a new DataCollection instance.
     * @param DataAccessPoint $point
     * @param ?ICommandResult $result
     * @param string $returnAs
     * @param Storage|null $storage
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
     * Returns the storage associated with this data collection.
     * @return DataTableIterator The iterator for the data collection.
     */
    public function getIterator(): DataTableIterator
    {
        return new DataTableIterator($this);
    }

    /**
     * Returns the storage associated with this data collection.
     * @return Storage
     */
    public function Storage(): Storage
    {
        return $this->_storage;
    }

    /**
     * Creates a new data row object based on the provided result.
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

        $return = new $className($this, $result, $this->_storage);
        $return->isLookUpOf($this->_isLookupOf);
        return $return;
    }

    /**
     * Replaces field placeholders in the given value with their corresponding real field names from the storage.
     * @param string $value The value containing field placeholders.
     * @param Storage $storage The storage to retrieve real field names from.
     * @return string The value with field placeholders replaced by real field names.
     */
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

    /**
     * Loads data from the storage based on the provided filter and other parameters.
     *
     * @param Storage $storage The storage to load data from.
     * @param int $page The page number for pagination (default: -1, no pagination).
     * @param int $pagesize The number of items per page for pagination (default: 20).
     * @param array|null $query The query parameters for filtering (default: null).
     * @param array|null $filter The filter parameters for filtering (default: null).
     * @param array|null $order The order parameters for sorting (default: null).
     * @return static|null Returns an instance of the DataCollection or null if an error occurs.
     */
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
        Storage $storage,
        ?array $query = null,
        ?array $filters = null,
        ?array $sort = null,
        int $page = -1,
        int $pagesize = 20
    ): ?static {
        [, $rowClass] = $storage->GetModelClasses();

        $result = $storage->accessPoint->ExecuteCommand('SelectDocuments', $storage->table, $query, $filters, [], [], $sort, $page, $pagesize);
        if (!$result->Error()) {
            return new static ($storage->accessPoint, $result, $rowClass, $storage);
        } else {
            app_debug($result);
            return null;
        }
    }

    /**
     * Deletes documents from the storage based on the provided filter.
     *
     * @param Storage $storage The storage to delete documents from.
     * @param array $filter The filter parameters for selecting documents to delete.
     * @return bool Returns true if the deletion was successful, false otherwise.
     */
    protected static function DeleteByFilter(
        Storage $storage,
        array $filter
    ): bool {
        
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

    /**
     * Restores documents in the storage based on the provided filter.
     *
     * @param Storage $storage The storage to restore documents in.
     * @param array $filter The filter parameters for selecting documents to restore.
     * @return bool Returns true if the restoration was successful, false otherwise.
     */
    protected static function RestoreByFilter(
        Storage $storage,
        array $filter
    ): bool {
        
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

    /**
     * Updates documents in the storage based on the provided filter and fields.
     *
     * @param Storage $storage The storage to update documents in.
     * @param array $filter The filter parameters for selecting documents to update.
     * @param array $fields The fields and their new values to update.
     * @return bool Returns true if the update was successful, false otherwise.
     */
    protected static function UpdateByFilter(
        Storage $storage,
        array $filter,
        array $fields
    ): bool {
        
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
     * Saves a data row to the storage, either by inserting a new row or updating an existing one.
     * @param DataRow|BaseDataRow $row The data row to save.
     * @param string|null $idField The auto-increment field, if not found in the table.
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

            $row->$idm = $data['$set']['datemodified'];

        }

        return true;
    }


    /**
     * Exports the data collection to a CSV file.
     * @param string $file The file to export to.
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
     * Exports the data collection to an XML file.
     * @param string $file The file to export to.
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
     * Exports the data collection to a JSON file.
     * @param string $file The file to export to.
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
     * Imports data from a CSV file.
     * @param string $file The source file.
     * @param int $firstrow The row number where the data starts.
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
     * Imports data from an XML file.
     * @param string $file The source file.
     * @param int $firstrow The row number where the data starts.
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

    /**
     * Exports the data to json file
     * @param Storage $storage
     * @param string|File $file
     * @param array $fields
     * @param array|null $filter
     * @return bool
     */
    protected static function _exportToFileJson(
        Storage $storage,
        string|File $file,
        array $fields,
        ?array $filter = null
    ): bool {
        
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

    /**
     * Imports from XML file to storage
     * @param Storage $storage
     * @param string|File $file
     * @param string $tag
     * @param array $fieldsMap
     * @param array $additionalFields
     * @return bool
     * @throws DataAccessPointsException
     * @throws DataModelException
     */
    protected static function _loadFromFileXML(
        Storage $storage,
        string|File $file,
        string $tag,
        array $fieldsMap,
        array $additionalFields = []
    ): bool {

        
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

    /**
     * Sets the full selection mode (with deleted items) for the data collection.
     * @param bool $value True to enable full selection, false to disable.
     * @return void
     */
    public static function SetFullSelect(bool $value)
    {
        static::$fullSelection = $value;
    }

}
