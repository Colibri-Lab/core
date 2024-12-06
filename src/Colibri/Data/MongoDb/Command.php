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
        $result = $this->SelectDocuments($collectionName, [], null, null, ['id'], 'id desc', 1, 1);
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

        $result = $collection->insertOne($document);
        if($result->getInsertedCount() > 0) {
            $return = new CommandResult((object)['responseHeader' => (object)[], 'response' => (object)[]]);
            $return->SetReturnedId($document->id);
        } else {
            $return = new CommandResult((object)['responseHeader' => (object)[], 'error' => (object)[]]);
        }
        $return->SetCollectionName($collectionName);
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

    public function UpdateDocument(string $collectionName, object $partOfDocument): CommandResult
    {
        /** @var Database */
        $db = $this->_connection->database;
        /** @var Collection */
        $collection = $db->getCollection($collectionName);

        $updateOperator = ['$set' => []];
        foreach($partOfDocument as $key => $value) {
            if(is_array($value)) {
                foreach($value as $k => $v) {
                    $updateOperator['$' . $k][$key] = $v;
                }
            }
        }

        $result = $collection->updateOne(['id' => $partOfDocument->id], $updateOperator);
        if($result->getModifiedCount() > 0) {
            $return = new CommandResult((object)['responseHeader' => (object)[], 'response' => (object)[]]);
            $return->SetCollectionName($collectionName);
            $return->SetReturnedId($partOfDocument->id);
            return $return;
        } else {
            $return = new CommandResult((object)['error' => []]);
            $return->SetCollectionName($collectionName);
            $return->SetReturnedId($partOfDocument->id);
            return $return;
        }
    }

    public function UpdateDocuments(string $collectionName, array $arrayOfPartOfDocuments): CommandResult
    {
        $results = [];
        foreach($arrayOfPartOfDocuments as $document) {
            $results[] = $this->UpdateDocument($collectionName, $document);
        }

        $return = new CommandResult((object)['responseHeader' => (object)[], 'response' => (object)[]]);
        $return->SetCollectionName($collectionName);
        foreach($results as $result) {
            $return->MergeWith($result);
        }
        return $return;
    }

    public function DeleteDocuments(string $collectionName, array $deleteQuery = []): CommandResult
    {
        
        /** @var Database */
        $db = $this->_connection->database;
        /** @var Collection */
        $collection = $db->getCollection($collectionName);

        $deleteParams = [];
        foreach($deleteQuery as $key => $value) {
            $deleteParams[$key] =  $value;
        }

        $result = $collection->deleteMany($deleteParams);

        $params = ['delete' => $deleteParams];
        $return = Command::Execute($this->_connection, 'post', '/' . $collectionName . '/update?wt=json&commit=true', $params);
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
    public function SelectDocuments(string $collectionName, array $select, ?array $filters = null, ?array $faset = null, ?array $fields = null, ?string $sort = null, int $page = -1, int $pagesize = 20): CommandResult
    {
        
        $options = [];
        if($sort) {
            $parts = explode(' ', $sort);
            $options['sort'] = [$parts[0] => ($parts[1] ?? 'asc') === 'asc' ? 1 : -1];
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

        $filter = [];
        if($filters) {
            foreach($filters as $key => $value) {
                $type = $value[0];
                $operation = $value[1];
                array_splice($value, 0, 2);

                if($type === 'number') {
                    $value = array_map(fn($v) => (float)$v, $value);
                }
                if(count($value) === 1) {
                    $operation = 'eq';
                    $value = $value[0];
                }
                $filter[$key] = ['$' . $operation => is_array($value) ? implode(',', $value) : $value];

            }
        }
        
        if(!empty($select)) {
            $or = [];
            foreach($select as $key => $value) {
                if(is_array($value)) {
                    $or[] = [$key => ['$in' => $value]];
                } elseif (VariableHelper::IsValidRegExp($value)) {
                    $regexp = VariableHelper::ParseRegexp($value);
                    $or[] = [$key => ['$regex' => $regexp[0], '$options' => $regexp[1]]];
                } else {
                    $or[] = [$key => ['$eq' => $value]];
                }
            }
            if(!empty($filter)) {
                $filter = ['$and' => $filter];
                $filter['$or'] = $or;
            } else {
                $filter = ['$or' => $or];
            }

        }

        /** @var Database */
        $db = $this->_connection->database;
        /** @var Collection */
        $collection = $db->getCollection($collectionName);

        
        $rows = [];
        $cursor = $collection->find($filter, $options);
        foreach ($cursor as $document) {
            /** @var $document \MongoDB\Model\BSONDocument  */
            $docArray = (object)(array)$document;
            $rows[] = $docArray;
        }
        
        $affected = count($rows);
        if($page >= 0) {
            $affected = $collection->countDocuments($filter);
        }

        $return = new CommandResult((object)['responseHeader' => (object)[], 'response' => (object)['docs' => $rows, 'numFound' => $affected]]);
        $return->SetCollectionName($collectionName);
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

}
