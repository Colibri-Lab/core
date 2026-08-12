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
 * @final
 * @class
 * @extends NoSqlCommand
 *
 */
final class Command extends NoSqlCommand
{
    /**
     * Escapes special characters in a query string for MongoDB.
     *
     * @param string $input The input string to escape.
     * @return string The escaped string.
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
     * @suppress PHP0416
     */
    public static function Execute(IConnection $connection, string $type, string $command, array $arguments): ICommandResult
    {
        $url = 'http://' . $connection->{'info'}->host . ':' . $connection->{'info'}->port . '/MongoDb' . $command;
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
     * Checks if a collection exists in the database.
     *
     * @param string $collectionName The name of the collection to check.
     * @return bool True if the collection exists, false otherwise.
     * @public
     */
    public function CollectionExists(string $collectionName): bool
    {
        /** @var Connection $connection */
        $connection = $this->_connection;
        if(!$connection->Ping()) {
            $connection->Reopen();
        }

        $found = false;
        $collections = $connection->database->listCollectionNames();
        foreach($collections as $collection) {
            if($collection === $collectionName) {
                $found = true;
            }
        }

        return $found;
    }

    /**
     * Creates a new collection in the database.
     *
     * @param string $collectionName The name of the collection to create.
     * @return bool True if the collection was created successfully, false otherwise.
     * @public
     */
    public function CreateCollection(string $collectionName): bool
    {
        /** @var Connection $connection */
        $connection = $this->_connection;
        if(!$connection->Ping()) {
            $connection->Reopen();
        }
        
        $connection->database->createCollection($collectionName);

        return true;
    }

    /**
     * Gets the maximum ID value from a collection.
     *
     * @param string $collectionName The name of the collection.
     * @return int The maximum ID value in the collection.
     * @public
     */
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

    /**
     * Inserts a document into a collection.
     *
     * @param string $collectionName The name of the collection.
     * @param object $document The document to insert.
     * @return CommandResult The result of the insert operation.
     * @public
     * @suppress PHP0413
     */
    public function InsertDocument(string $collectionName, object $document): CommandResult
    {
        if(!$this->_connection->Ping()) {
            $this->_connection->Reopen();
        }
        
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

    /**
     * Inserts multiple documents into a collection.
     *
     * @param string $collectionName The name of the collection.
     * @param array $arrayOfDocuments An array of documents to insert.
     * @return CommandResult The result of the insert operation.
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
     * Updates a document in a collection.
     *
     * @param string $collectionName The name of the collection.
     * @param int $id The ID of the document to update.
     * @param object $partOfDocument The partial document with updated fields.
     * @return CommandResult The result of the update operation.
     * @public
     * @suppress PHP0413
     */
    public function UpdateDocument(string $collectionName, int $id, object $partOfDocument): CommandResult
    {
        if(!$this->_connection->Ping()) {
            $this->_connection->Reopen();
        }

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

    /**
     * Updates multiple documents in a collection based on a filter.
     *
     * @param string $collectionName The name of the collection.
     * @param array $filter The filter criteria for selecting documents to update.
     * @param array $update The update operations to apply to the selected documents.
     * @return CommandResult The result of the update operation.
     * @suppress PHP0413
     * @public
     */
    public function UpdateDocuments(string $collectionName, array $filter, array $update): CommandResult
    {
        if(!$this->_connection->Ping()) {
            $this->_connection->Reopen();
        }

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

    /**
     * Deletes documents from a collection based on a filter.
     *
     * @param string $collectionName The name of the collection.
     * @param array $filter The filter criteria for selecting documents to delete.
     * @return CommandResult The result of the delete operation.
     * @suppress PHP0413
     * @public
     */
    public function DeleteDocuments(string $collectionName, array $filter): CommandResult
    {   
        if(!$this->_connection->Ping()) {
            $this->_connection->Reopen();
        }

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
     * @param mixed $filters array contains a filter fields, example 'fieldname' => 'fieldvalue' (if needed full), or 'fieldname' => 'regexp string, like /brbrbr/i'
     * @param mixed $faset 
     * @param mixed $fields
     * @param mixed $sort
     * @param int $page
     * @param int $pagesize
     * @return \Colibri\Data\Solr\CommandResult
     * @suppress PHP0413
     * @public
     */
    public function SelectDocuments(string $collectionName, ?array $select = null, ?array $filters = null, ?array $faset = null, ?array $fields = null, ?array $sort = null, int $page = -1, int $pagesize = 20): CommandResult
    {
        if(!$this->_connection->Ping()) {
            $this->_connection->Reopen();
        }

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
                /** @var \MongoDB\Model\BSONDocument $document  */
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

    /**
     * DO NOTHING: Creates custom fields for a collection. This method is a placeholder and does not perform any operations.
     * @param string $collectionName The name of the collection for which to create custom fields.
     * @return void
     * @public
     * 
     */
    public function CreateCustomFields(string $collectionName)
    {
        // do nothing
    }

    /**
     * DO NOTHING: Gets the fields of a collection. This method is a placeholder and does not perform any operations.
     * @param string $collectionName The name of the collection for which to get fields.
     * @return CommandResult|null The result of the get fields operation, or null if not implemented.
     * @public
     */
    public function GetFields(string $collectionName): ?CommandResult
    {
        return null;
    }

    /**
     * DO NOTHING: Adds a field to a collection. This method is a placeholder and does not perform any operations.
     * @param string $collectionName The name of the collection to which to add the field.
     * @param string $fieldName The name of the field to add.
     * @param string $fieldType The type of the field to add.
     * @param bool $required Whether the field is required.
     * @param bool $indexed Whether the field is indexed.
     * @param mixed|null $default The default value for the field, if any.
     * @return CommandResult|null The result of the add field operation, or null if not implemented.
     * @public
     */
    public function AddField(string $collectionName, string $fieldName, string $fieldType, bool $required, bool $indexed, mixed $default = null): ?CommandResult
    {
        return null;
    }

    /**
     * DO NOTHING: Adds a copy of a field to a collection. This method is a placeholder and does not perform any operations.
     * @param string $collectionName The name of the collection to which to add the copy field.
     * @param string $source The name of the source field to copy.
     * @param string $dest The name of the destination field to create as a copy.
     * @return CommandResult|null The result of the add copy field operation, or null if not implemented.
     * @public
     */
    public function AddCopyField(string $collectionName, string $source, string $dest): ?CommandResult
    {
        return null;
    }

    /**
     * DO NOTHING: Replaces a field in a collection. This method is a placeholder and does not perform any operations.
     * @param string $collectionName The name of the collection in which to replace the field.
     * @param string $fieldName The name of the field to replace.
     * @param string $fieldType The type of the field to replace.
     * @param bool $required Whether the field is required.
     * @param bool $indexed Whether the field is indexed.
     * @param mixed|null $default The default value for the field, if any.
     * @return CommandResult|null The result of the replace field operation, or null if not implemented.
     * @public
     */
    public function ReplaceField(string $collectionName, string $fieldName, string $fieldType, bool $required, bool $indexed, mixed $default = null): ?CommandResult
    {
        return null;
    }

    /**
     * Migrates the database schema for a given storage and its extended storage configuration.
     * @param Logger $logger The logger instance to use for logging migration progress.
     * @param string $storage The name of the storage to migrate.
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

        if(!$this->IndexExists($storage, '$**', 'text')) {
            $this->CreateIndex($storage, '$**', 'text');    
        }
        

    }

    /**
     * Checks if an index exists on a collection for a given field and value.
     * @param string $collectionName The name of the collection to check.
     * @param string $fieldName The name of the field to check.
     * @param string $value The value of the index to check.
     * @return bool True if the index exists, false otherwise.
     * @public
     */
    public function IndexExists(string $collectionName, string $fieldName, string $value = '1') {
        
        $collection = $this->_connection->database->getCollection($collectionName);
        $indexes = $collection->listIndexes();
        foreach ($indexes as $index) {
            if((string)$index === $fieldName . '_' . $value) {
                return true;
            }
        }
        return false;
    }

    /**
     * Creates an index on a collection for a given field and value.
     * @param string $collectionName The name of the collection.
     * @param string $fieldName The name of the field to index.
     * @param string $value The value of the index.
     * @return void
     * @public
     */
    public function CreateIndex(string $collectionName, string $fieldName, string $value = '1') {
        $collection = $this->_connection->database->getCollection($collectionName);
        $collection->createIndex([$fieldName => $value]);
    }

}
