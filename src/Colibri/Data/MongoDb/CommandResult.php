<?php

/**
 * MongoDb
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\MongoDb
 */

namespace Colibri\Data\MongoDb;

use Colibri\Common\VariableHelper;
use Colibri\Data\NoSqlClient\ICommandResult;
use Colibri\Data\NoSqlClient\QueryInfo;

/**
 * Class for executing commands at the access point.
 *
 * This class extends SqlCommand and provides methods for preparing and executing queries.
 *
 * @inheritDoc
 * 
 * @class
 * @final
 * @implements ICommandResult
 *
 */
final class CommandResult implements ICommandResult
{
    /** @var object|null The response object from the command execution. */
    private ?object $_response = null;

    /** 
     * Constructor for the CommandResult class.
     * @param object $response The response object from the command execution.
     */
    public function __construct(object $response)
    {
        $this->_response = $response;
    }

    /**
     * Returns the error object from the command execution, if any.
     * @return object|null The error object, or null if there was no error.
     */
    public function Error(): ?object
    {
        if($this->_response?->error ?? false) {
            return json_decode($this->_response?->error);
        }
        return null;
    }

    /**
     * Returns information about the query execution.
     * @return object An object containing query execution information.
     */
    public function QueryInfo(): object
    {
        $affected = count($this->ResultData());
        $count = count($this->ResultData());
        if($this->_response?->response ?? null) {
            $affected = ($this->_response?->response?->numFound ?? 0);
        }
        return (object)[...(array)$this->_response->responseHeader, ...['affected' => $affected, 'count' => $count]];
    }

    /**
     * Converts an object or array to a standardized format.
     * @param mixed $object The object or array to convert.
     * @return mixed The converted object or array.
     */
    private function _convert($object)
    {
        if(is_array($object) && !VariableHelper::IsAssociativeArray($object)) {
            $ret = [];
            foreach($object as $v) {
                $ret[] = $this->_convert($v);
            }
            return $ret;
        } elseif (is_object($object) || is_array($object) && VariableHelper::IsAssociativeArray($object)) {
            $ret = [];
            foreach($object as $key => $value) {
                if(is_object($value) && method_exists($value, 'getArrayCopy')) {
                    $ret[$key] = $value->getArrayCopy();
                } elseif (is_object($value) && get_class($value) == 'MongoDB\BSON\ObjectId') {
                    $ret[$key] = (string)$value;
                } else {
                    $ret[$key] = $value;
                }
                $ret[$key] = $this->_convert($ret[$key]);
            }
            return (object)$ret;
        }
        return $object;
    }

    /**
     * Returns the result data from the command execution.
     * @return array The result data as an array.
     */
    public function ResultData(): array
    {
        $return = [];
        if($this->_response?->response ?? null) {
            $return = (array)($this->_response?->response?->docs ?? []);
        } elseif ($this->_response?->status ?? null) {
            $return = (array)($this->_response?->status ?? []);
        } elseif ($this->_response?->fieldTypes ?? null) {
            $return = (array)($this->_response?->fieldTypes ?? []);
        } elseif ($this->_response?->fields ?? null) {
            $return = (array)($this->_response?->fields ?? []);
        }
        $return = $this->_convert($return);
        return $return;
    }

    /**
     * Sets the name of the collection in the response header.
     * @param string $name The name of the collection.
     * @return void
     */
    public function SetCollectionName(string $name): void
    {
        if(! ($this->_response?->responseHeader ?? null)) {
            $this->_response->responseHeader = (object)[];
        }
        $this->_response->responseHeader->name = $name;
    }

    /**
     * Sets the returned ID(s) in the response header.
     * @param int|array $id The ID or IDs to set.
     * @return void
     */
    public function SetReturnedId(int|array $id): void
    {
        if(!$this->_response->responseHeader) {
            $this->_response->responseHeader = (object)[];
        }
        if(! ($this->_response?->responseHeader?->returned ?? false)) {
            $this->_response->responseHeader->returned = [];
        }
        if(!is_array($id)) {
            $id = [$id];
        }
        $this->_response->responseHeader->returned =
            [...$this->_response->responseHeader->returned, ...$id];
    }

    /**
     * Merges the current command result with another command result.
     * @param ICommandResult $result The command result to merge with.
     * @return void
     */
    public function MergeWith(ICommandResult $result): void
    {
        $queryInfo = $result->QueryInfo();
        $this->SetReturnedId($queryInfo->returned);
    }

}
