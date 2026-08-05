<?php

/**
 * PgSql
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\Solr
 */

namespace Colibri\Data\Solr;

use Colibri\Common\StringHelper;
use Colibri\Common\VariableHelper;
use Colibri\Data\NoSqlClient\Command as NoSqlCommand;
use Colibri\Data\NoSqlClient\ICommandResult;
use Colibri\Data\NoSqlClient\IConnection;
use Colibri\Data\NoSqlClient\QueryInfo;
use Colibri\Data\Solr\Exception as SolrException;
use Colibri\IO\Request\Encryption;
use Colibri\IO\Request\Request;
use Colibri\Utils\Logs\Logger;

/**
 * Class for executing commands at the access point.
 *
 * This class extends SqlCommand and provides methods for preparing and executing queries.
 *
 * @inheritDoc
 *
 * @class
 * @final
 * @extends NoSqlCommand
 *
 */
final class Command extends NoSqlCommand
{
    /**
     * Escapes special characters in a Solr query string.
     *
     * @param string $input The input query string to escape.
     * @return string The escaped query string.
     * @public
     */
    public function EscapeQuery(string $input): string
    {
        $specialChars = ['\\', '+', '-', '&&', '||', '!', '(', ')', '{', '}', '[', ']', '^', '"', '~', '*', '?', ':', '/'];
        foreach ($specialChars as $char) {
            $input = str_replace($char, '\\' . $char, $input);
        }
        return $input;
    }

    /**
     * Executes the command and returns a data results if exists.
     *
     * @param string $command command name
     * @param mixed[] $arguments command arguments
     * @return ICommandResult The command result.
     * @public
     * @static
     */
    public static function Execute(IConnection $connection, string $type, string $command, array $arguments): ICommandResult
    {
        $url = 'http://' . $connection->info->host . ':' . $connection->info->port . '/solr' . $command;
        if($type === 'get') {
            $url = StringHelper::AddToQueryString($url, [
                'wt' => 'json',
                ...$arguments
            ]);
        }
        $request = new Request($url, $type, $type === 'get' ? Encryption::UrlEncoded : Encryption::JsonEncoded);
        $response = $request->Execute($type != 'get' ? $arguments : []);
        if($response->status !== 200) {
            return new CommandResult((object)['error' => $response->data]);
        } else {
            $result = json_decode($response->data);
            return new CommandResult($result);
        }

    }

    /**
     * Executes the command and returns a data results if exists.
     *
     * @param string $collectionName The name of the collection to check.
     * @return bool True if the collection exists, false otherwise.
     * @public
     */
    public function CollectionExists(string $collectionName): bool
    {
        $result = Command::Execute($this->_connection, 'get', '/admin/cores', ['wt' => 'json']);
        $collections = $result->ResultData();
        return isset($collections[$collectionName]);
    }

    /**
     * Executes the command and returns a data results if exists.
     *
     * @param string $collectionName The name of the collection to create.
     * @return bool True if the collection was created successfully, false otherwise.
     * @public
     */
    public function CreateCollection(string $collectionName): bool
    {
        shell_exec('sudo mkdir -p /var/solr/data/' . $collectionName);
        shell_exec('sudo cp -r ' . $this->_connection->info->path . 'configsets/_default/* /var/solr/data/' . $collectionName);
        shell_exec('sudo chown -R solr:solr /var/solr/data/' . $collectionName);
        shell_exec('sudo chmod -R 755 /var/solr/data/' . $collectionName);
        $result = Command::Execute($this->_connection, 'get', '/admin/cores', [
            'wt' => 'json',
            'action' => 'CREATE',
            'name' => $collectionName,
            'instanceDir' => $collectionName
        ]);


        return true;
    }

    /**
     * Executes the command and returns a data results if exists.
     *
     * @param string $collectionName The name of the collection to check.
     * @return int The maximum ID in the collection.
     * @public
     */
    public function MaxId(string $collectionName): int
    {
        $result = Command::Execute(
            $this->_connection,
            'get',
            '/' . $collectionName . '/select',
            ['wt' => 'json', 'q' => '*:*', 'sort' => 'id desc', 'rows' => 1, 'fl' => 'id']
        );

        $count = $result->QueryInfo()->count;
        if($count === 0) {
            return 0;
        }
        $rows = $result->ResultData();
        return $rows[0]->id;
    }

    /**
     * Executes the command and returns a data results if exists.
     *
     * @param string $collectionName The name of the collection to insert the document into.
     * @param object $document The document to insert.
     */
    public function InsertDocument(string $collectionName, object $document): CommandResult
    {
        $maxId = $this->MaxId($collectionName);
        $document->id = StringHelper::Expand((string)($maxId + 1), 20, '0');
        $return = Command::Execute($this->_connection, 'post', '/' . $collectionName . '/update?wt=json&overwrite=true&commit=true', [$document]);
        $return->SetReturnedId($document->id);
        $return->SetCollectionName($collectionName);
        return $return;
    }

    /**
     * Executes the command and returns a data results if exists.
     *
     * @param string $collectionName The name of the collection to insert the documents into.
     * @param object[] $arrayOfDocuments The array of documents to insert.
     * @return CommandResult The command result.
     * @public
     */
    public function InsertDocuments(string $collectionName, array $arrayOfDocuments): CommandResult
    {
        $results = [];
        foreach($arrayOfDocuments as $document) {
            $results[] = $this->InsertDocument($collectionName, $document);
        }

        $return = new CommandResult((object)['responseHeader' => (object)[], 'response' => (object)[]]);
        $return->SetCollectionName($collectionName);
        foreach($results as $result) {
            $return->MergeWith($result);
        }
        return $return;
    }

    /**
     * Executes the command and returns a data results if exists.
     *
     * @param string $collectionName The name of the collection to update the document in.
     * @param int $id The ID of the document to update.
     * @param object $partOfDocument The part of the document to update.
     * @return CommandResult The command result.
     * @public
     */
    public function UpdateDocument(string $collectionName, int $id, object $partOfDocument): CommandResult
    {
        $partOfDocument->id = StringHelper::Expand((string)$id, 20, '0');
        $return = Command::Execute($this->_connection, 'post', '/' . $collectionName . '/update?wt=json&overwrite=true&commit=true', [$partOfDocument]);
        $return->SetCollectionName($collectionName);
        $return->SetReturnedId($id);
        return $return;
    }

    /**
     * Executes the command and returns a data results if exists.
     *
     * @param string $collectionName The name of the collection to update the documents in.
     * @param array $filter The filter criteria to select the documents to update.
     * @param array $update The update data to apply to the selected documents.
     * @return CommandResult The command result.
     * @public
     */
    public function UpdateDocuments(string $collectionName, array $filter, array $update): CommandResult
    {
        $resultsOfIds = $this->SelectDocuments($collectionName, ['*' => '*'], $filter, null, []);
        if($resultsOfIds->Error()) {
            return $resultsOfIds;
        }

        $updateData = [];
        $ids = $resultsOfIds->ResultData();
        foreach($ids as $idData) {
            $updateData[] = [...(array)$idData, ...$update];
        }

        $return = Command::Execute($this->_connection, 'post', '/' . $collectionName . '/update?wt=json&commit=true', $updateData);
        $return->SetCollectionName($collectionName);
        return $return;
    }

    /**
     * Executes the command and returns a data results if exists.
     *
     * @param string $collectionName The name of the collection to delete the documents from.
     * @param array $deleteQuery The delete query criteria.
     * @return CommandResult The command result.
     * @public
     */
    public function DeleteDocuments(string $collectionName, array $deleteQuery = ['*' => '*']): CommandResult
    {
        $deleteParams = [];
        foreach($deleteQuery as $key => $value) {
            $deleteParams[] = $key . ':' . $value;
        }
        $params = ['delete' => ['query' => implode(' and ', $deleteParams)]];
        $return = Command::Execute($this->_connection, 'post', '/' . $collectionName . '/update?wt=json&commit=true', $params);
        $return->SetCollectionName($collectionName);
        return $return;
    }

    /**
     * Select the documents from solr database
     * @param string $collectionName
     * @param array $select array of fields to select
     * @param mixed $filters - filters: array contains a filter fields, example 'fieldname' => 'fieldvalue' (if needed full), or 'fieldname' => 'regexp string, like /brbrbr/i'
     * @param mixed $faset - faset: array contains a faset fields, example 'fieldname' => 'fieldvalue' (if needed full), or 'fieldname' => 'regexp string, like /brbrbr/i'
     * @param mixed $fields - fields: array contains a fields to return, example ['fieldname1', 'fieldname2', 'fieldname3']
     * @param mixed $sort - sort: array contains a fields to sort, example ['fieldname1' => 'asc', 'fieldname2' => 'desc']
     * @param int $page - page number for pagination, default is -1 (no pagination)
     * @param int $pagesize - number of results per page, default is 20
     * @return \Colibri\Data\Solr\CommandResult
     */
    public function SelectDocuments(
        string $collectionName,
        array $select,
        ?array $filters = null,
        ?array $faset = null,
        ?array $fields = null,
        ?array $sort = null,
        int $page = -1,
        int $pagesize = 20
    ): CommandResult {

        $params = ['wt' => 'json'];
        $q = [];
        foreach($select as $key => $value) {
            if(is_array($value)) {
                $vv = [];
                foreach($value as $v) {
                    $vv[] = $key . ':' . $v;
                }
                $q[] = '(' . implode(' or ', $vv) . ')';
            } else {
                $q[] = $key.':'.$value;
            }
        }
        $params['q'] = implode(' or ', $q);
        $params['fq'] = VariableHelper::ToString($filters, ' and ', ':', false);

        if(!empty($faset)) {
            $params['faset'] = true;
            foreach($faset as $key => $value) {
                $params['faset.' . $key] = $value;
            }
        }
        if(!empty($fields)) {
            $params['fl'] = implode(',', $fields);
        }

        if($sort) {
            $params['sort'] = array_keys($sort)[0] . ' ' . array_values($sort)[0];
        }

        if($page >= 0) {
            $params['start'] = ($page - 1) * $pagesize;
            $params['rows'] = $pagesize;
        }
        $params['q.op'] = 'OR';

        $return = Command::Execute($this->_connection, 'get', '/' . $collectionName . '/select', $params);
        $return->SetCollectionName($collectionName);
        return $return;
    }

    /**
     * Create custom fields to the solr collection, if they do not exist
     * @param string $collectionName The name of the collection to create custom fields in.
     * @return void
     * @public
     */
    public function CreateCustomFields(string $collectionName)
    {
        $types = [
            'bool' => [
                "name" => "bool",
                "class" => "solr.BoolField"
            ],
            'int' => [
                "name" => "int",
                "class" => "solr.IntPointField",
                "docValues" => true
            ],
            'bigint' => [
                'name' => "bigint",
                'class' => "solr.LongPointField",
                "docValues" => true
            ],
            'float' => [
                "name" => "float",
                "class" => "solr.FloatPointField",
                "docValues" => true
            ],
            'date' => [
                "name" => "date",
                "class" => "solr.TrieDateField",
                "docValues" => true,
                "indexed" => true,
                "stored" => true
            ],
            'datetime' => [
                "name" => "datetime",
                "class" => "solr.DatePointField",
                "docValues" => true,
                "indexed" => true,
                "stored" => true
            ],
            'varchar' => [
                "name" => "varchar",
                "class" => "solr.StrField",
                "sortMissingLast" => true,
                "docValues" => true
            ],
            'longtext' => [
                "name" => "longtext",
                "class" => "solr.TextField",
                "analyzer" => [
                    "tokenizer"=> [ "class" => "solr.StandardTokenizerFactory"],
                    "filters" => [ [ "class" => "solr.LowerCaseFilterFactory" ] ]
                ]
            ]
        ];

        $result = Command::Execute($this->_connection, 'get', '/' . $collectionName . '/schema/fieldtypes', []);
        $fieldTypes = $result->ResultData();

        foreach($types as $type => $data) {
            if(VariableHelper::FindInArray($fieldTypes, 'name', $type) === null) {
                Command::Execute($this->_connection, 'post', '/' . $collectionName . '/schema', ['add-field-type' => $data]);
            }
        }

        $this->AddField($collectionName, 'datecreated', 'datetime', false, true, null);
        $this->AddField($collectionName, 'datemodified', 'datetime', false, true, null);
        $this->AddField($collectionName, 'datedeleted', 'datetime', false, true, null);


    }

    /**
     * Gets the fields from collection
     * @param string $collectionName
     * @return CommandResult
     * @public
     */
    public function GetFields(string $collectionName): CommandResult
    {
        $return = Command::Execute($this->_connection, 'get', '/' . $collectionName . '/schema/fields', []);
        $return->SetCollectionName($collectionName);
        return $return;
    }

    /**
     * Add the field to collection
     * @param string $collectionName
     * @param string $fieldName
     * @param string $fieldType
     * @param bool $required
     * @param bool $indexed
     * @param mixed $default
     * @return CommandResult
     * @public
     */
    public function AddField(string $collectionName, string $fieldName, string $fieldType, bool $required, bool $indexed, mixed $default = null): CommandResult
    {
        $addFieldParams = [
            "name" => $fieldName,
            "type" => $fieldType,
            "stored" => true,
            "indexed" => $indexed,
            "required" => $required,
            "docValues" => $fieldType != 'longtext' ? $indexed : false,
            "termVectors" => $indexed,
            "termPositions" => $indexed,
            "termOffsets" => $indexed
        ];
        if($default) {
            $addFieldParams["default"] = $default;
        }
        $return = Command::Execute($this->_connection, 'post', '/' . $collectionName . '/schema', ['add-field' => $addFieldParams]);
        $return->SetCollectionName($collectionName);
        return $return;
    }

    /**
     * Copy the field from one to another in collection
     * @param string $collectionName
     * @param string $source
     * @param string $dest
     * @return CommandResult
     * @public
     */
    public function AddCopyField(string $collectionName, string $source, string $dest): CommandResult
    {
        $return = Command::Execute($this->_connection, 'post', '/' . $collectionName . '/schema', ['add-copy-field' => [
            "source" => $source,
            "dest" => $dest
        ]]);
        $return->SetCollectionName($collectionName);
        return $return;
    }

    /**
     * Replace the field in collection
     * @param string $collectionName
     * @param string $fieldName
     * @param string $fieldType
     * @param bool $required
     * @param bool $indexed
     * @param mixed $default
     * @return CommandResult
     * @public
     */
    public function ReplaceField(string $collectionName, string $fieldName, string $fieldType, bool $required, bool $indexed, mixed $default = null): CommandResult
    {
        $replaceFieldParams = [
            "name" => $fieldName,
            "type" => $fieldType,
            "stored" => true,
            "indexed" => $indexed,
            "required" => $required,
            "docValues" => $indexed,
            "termVectors" => $indexed,
            "termPositions" => $indexed,
            "termOffsets" => $indexed
        ];
        if($default) {
            $replaceFieldParams["default"] = $default;
        }
        $return = Command::Execute($this->_connection, 'post', '/' . $collectionName . '/schema', ['replace-field' => $replaceFieldParams]);
        $return->SetCollectionName($collectionName);
        return $return;
    }

    /**
     * Migrate the storage to the new structure
     * @param Logger $logger The logger instance.
     * @param string $storage The storage name.
     * @param array $xstorage The extended storage configuration.
     * @return void
     * @public
     */
    public function Migrate(Logger $logger, string $storage, array $xstorage): void
    {

        $storage = isset($xstorage['name']) ? $xstorage['name'] : $storage;

        if(!$this->CollectionExists($storage)) {
            $this->CreateCollection($storage);
        }

        $this->CreateCustomFields($storage);

        // надо создать поля
        $ofields = $this->GetFields($storage);
        if(!$ofields) {
            return;
        }

        $xfields = $xstorage['fields'] ?? [];
        foreach ($xfields as $fieldName => $xfield) {

            $fname = $fieldName;
            $fparams = $xfield['params'] ?? [];

            $fieldFound = VariableHelper::FindInArray($ofields->ResultData(), 'name', $fname);
            if (!$fieldFound) {
                $logger->error($storage . ': ' . $fname . ': Field destination not found: creating');
                $this->AddField(
                    $storage,
                    $fname,
                    $xfield['type'],
                    $fparams['required'] ?? false,
                    $xfield['indexed'] ?? true,
                    $xfield['default'] ?? null
                );
            } else {
                $required = $fparams['required'] ?? false;
                $default = $xfield['default'] ?? null;

                $orType = $fieldFound->type != $xfield['type'];
                $orDefault = ($fieldFound?->default ?? null) != $default;
                $orRequired = ($fieldFound?->required ?? false) != $required;

                if ($orType || $orDefault || $orRequired) {
                    $logger->error($storage . ': ' . $fname . ': Field destination changed: updating');
                    // проверить на соответствие
                    $this->ReplaceField(
                        $storage,
                        $fname,
                        $xfield['type'],
                        $required,
                        $xfield['indexed'] ?? true,
                        $default
                    );
                }
            }

        }
    }

}
