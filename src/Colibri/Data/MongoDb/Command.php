<?php

/**
 * MongoDb
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\MongoDb
 */

namespace Colibri\Data\MongoDb;

use Colibri\Common\StringHelper;
use Colibri\Common\VariableHelper;
use Colibri\Data\NoSqlClient\Command as NoSqlCommand;
use Colibri\Data\NoSqlClient\ICommandResult;
use Colibri\Data\NoSqlClient\IConnection;
use Colibri\Data\NoSqlClient\QueryInfo;
use Colibri\Data\MongoDb\Exception as MongoDbException;
use Colibri\IO\Request\Encryption;
use Colibri\IO\Request\Request;
use Colibri\Utils\Logs\Logger;
use MongoDB\Builder\Expression\Variable;
use MongoDB\Collection;
use MongoDB\Database;

/**
 * Class for executing commands at the access point.
 *
 * This class extends SqlCommand and provides methods for preparing and executing queries.
 *
 * @inheritDoc
 *
 */
final class Command extends NoSqlCommand
{

    public function EscapeQuery(string $input):string {
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
     */
    public static function Execute(IConnection $connection, string $type, string $command, array $arguments): ICommandResult
    {
        $url = 'http://' . $connection->info->host . ':' . $connection->info->port . '/MongoDb' . $command;
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

    public function CollectionExists(string $collectionName): bool
    {
        /** @var Connection $connection */
        $connection = $this->_connection;
        
        $found = false;
        $collections = $connection->database->listCollectionNames();
        foreach($collections as $collection) {
            if($collection === $collectionName) {
                $found = true;
            }
        }
        
        return $found;
    }

    public function CreateCollection(string $collectionName): bool
    {
        /** @var Connection $connection */
        $connection = $this->_connection;
        $collection = $connection->database->createCollection($collectionName);
        return true;
    }

    public function MaxId(string $collectionName): int
    {
        $result = $this->SelectDocuments($collectionName, [], null, null, ['id'], ['id' => -1], 1, 1);
        $count = $result->QueryInfo()->count;
        if($count === 0) {
            return 0;
        }
        $rows = $result->ResultData();
        return $rows[0]->id;
    }

    public function InsertDocument(string $collectionName, object $document): CommandResult
    {
        $maxId = $this->MaxId($collectionName);
        $document->id = $maxId + 1;

        /** @var Database */
        $db = $this->_connection->database;
        /** @var Collection */
        $collection = $db->getCollection($collectionName);

        try {
            $result = $collection->insertOne($document);
            $return = new CommandResult((object)['responseHeader' => (object)['affected' => $result->getInsertedCount(), 'count' => $result->getInsertedCount()], 'response' => (object)[]]);
        } catch(\Throwable $e) {
            $return = new CommandResult((object)['error' => $e]);
        }
        $return->SetCollectionName($collectionName);
        $return->SetReturnedId($document->id);
        return $return;
    }

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

    public function UpdateDocument(string $collectionName, int $id, object $partOfDocument): CommandResult
    {
        /** @var Database */
        $db = $this->_connection->database;
        /** @var Collection */
        $collection = $db->getCollection($collectionName);

        try {
            $result = $collection->updateOne(['id' => $id], $partOfDocument);
            $return = new CommandResult((object)['responseHeader' => (object)['affected' => $result->getModifiedCount(), 'count' => $result->getModifiedCount()], 'response' => (object)[]]);
        } catch(\Throwable $e) {
            $return = new CommandResult((object)['error' => $e]);
        }
        $return->SetCollectionName($collectionName);
        $return->SetReturnedId($id);
        return $return;
        
    }

    public function UpdateDocuments(string $collectionName, array $filter, array $update): CommandResult
    {
        /** @var Database */
        $db = $this->_connection->database;
        /** @var Collection */
        $collection = $db->getCollection($collectionName);
        
        try {
            $result = $collection->updateMany($filter, $update);
            $return = new CommandResult((object)['responseHeader' => (object)['affected' => $result->getModifiedCount(), 'count' => $result->getModifiedCount()], 'response' => (object)[]]);
        } catch(\Throwable $e) {
            $return = new CommandResult((object)['error' => $e]);
        }
        $return->SetCollectionName($collectionName);
        return $return;
    }

    public function DeleteDocuments(string $collectionName, array $filter): CommandResult
    {
        
        /** @var Database */
        $db = $this->_connection->database;
        /** @var Collection */
        $collection = $db->getCollection($collectionName);

        try {
            $result = $collection->deleteMany($filter);
            $return = new CommandResult((object)['responseHeader' => (object)['affected' => $result->getDeletedCount(), 'count' => $result->getDeletedCount()], 'response' => (object)[]]);
        } catch(\Throwable $e) {
            $return = new CommandResult((object)['error' => $e]);
        }
        $return->SetCollectionName($collectionName);
        return $return;
    }

    /**
     * Summary of SelectDocuments
     * @param string $collectionName
     * @param array $select
     * @param mixed $filters - filters: array contains a filter fields, example 'fieldname' => 'fieldvalue' (if needed full), or 'fieldname' => 'regexp string, like /brbrbr/i'
     * @param mixed $faset
     * @param mixed $fields
     * @param mixed $sort
     * @param int $page
     * @param int $pagesize
     * @return \Colibri\Data\Solr\CommandResult
     */
    public function SelectDocuments(string $collectionName, ?array $select = null, ?array $filters = null, ?array $faset = null, ?array $fields = null, ?array $sort = null, int $page = -1, int $pagesize = 20): CommandResult
    {
        
        $options = [];
        if($sort) {
            $options['sort'] = $sort;
        }
        if($page >= 0) {
            $options['skip'] = ($page - 1) * $pagesize;
            $options['limit'] = $pagesize;
        }

        if($fields) {
            $options['projection'] = [];
            foreach($fields as $v) {
                $options['projection'][$v] = 1;
            }
        }

        if($select && !empty($select)) {
            $or = [];
            foreach($select as $key => $value) {
                if ($value === null) {
                    $value = 0;
                }
                if (is_array($value)) {
                    $or[] = [$key => ['$in' => $value]];
                } elseif (is_numeric($value) || is_bool($value)) {
                    $or[] = [$key => ['$eq' => $value]];
                } else {
                    $regexp = VariableHelper::ParseRegexp($value);
                    $or[] = [$key => ['$regex' => $regexp[0], '$options' => $regexp[1]]];
                }
            }
            if(!empty($filters)) {
                $filters = ['$and' => $filters];
                $filters['$or'] = $or;
            } else {
                $filters = ['$or' => $or];
            }

        }

        /** @var Database */
        $db = $this->_connection->database;
        /** @var Collection */
        $collection = $db->getCollection($collectionName);

        try {

            $rows = [];
            $cursor = $collection->find($filters ?? [], $options);
            foreach ($cursor as $document) {
                /** @var $document \MongoDB\Model\BSONDocument  */
                $docArray = (object)(array)$document;
                $rows[] = $docArray;
            }
            
            $affected = count($rows);
            if($page >= 0) {
                $affected = $collection->countDocuments($filters ?? []);
            }
    
            $return = new CommandResult((object)['responseHeader' => (object)[], 'response' => (object)['docs' => $rows, 'numFound' => $affected]]);
            $return->SetCollectionName($collectionName);
        } catch(\Throwable $e) {
            $return = new CommandResult((object)['error' => $e]);
            $return->SetCollectionName($collectionName);
        }
        return $return;
    }

    public function CreateCustomFields(string $collectionName)
    {
        // do nothing
    }

    public function GetFields(string $collectionName): ?CommandResult
    {
        return null;
    }

    public function AddField(string $collectionName, string $fieldName, string $fieldType, bool $required, bool $indexed, mixed $default = null): ?CommandResult
    {
        return null;
    }

    public function AddCopyField(string $collectionName, string $source, string $dest): ?CommandResult
    {
        return null;
    }

    public function ReplaceField(string $collectionName, string $fieldName, string $fieldType, bool $required, bool $indexed, mixed $default = null): ?CommandResult
    {
        return null;
    }

    public function Migrate(Logger $logger, string $storage, array $xstorage): void
    {
        if(!$this->CollectionExists($storage)) {
            $this->CreateCollection($storage);
        }
        
    }

}
