<?php

/**
 * Solr
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\Solr
 */

namespace Colibri\Data\Solr;

use Colibri\Data\NoSqlClient\ICommandResult;
use Colibri\Data\NoSqlClient\QueryInfo;

/**
 * Class for executing commands at the access point.
 *
 * This class represents the result of a command executed against a Solr server.
 *
 * @inheritDoc
 * 
 * @final
 * @class
 * @implements ICommandResult
 *
 */
final class CommandResult implements ICommandResult
{
    /**
     * The response object from the Solr server.
     * @var object|null
     * @private
     */
    private ?object $_response = null;

    /**
     * Constructor.
     *
     * @param object $response The response object from the Solr server.
     * @public
     * @constructor
     */
    public function __construct(object $response)
    {
        $this->_response = $response;
    }

    /**
     * Returns the error object from the command execution, if any.
     *
     * @return object|null The error object, or null if there was no error.
     * @public
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
     *
     * @return object An object containing query execution information.
     * @public
     */
    public function QueryInfo(): object
    {
        $affected = count($this->ResultData());
        $count = count($this->ResultData());
        if($this->_response?->response ?? null) {
            $affected = $this->_response?->response->numFound;
        }
        return (object)[...(array)$this->_response->responseHeader, ...['affected' => $affected, 'count' => $count]];
    }

    /**
     * Returns the result data from the command execution.
     *
     * @return array The result data as an array.
     * @public
     */
    public function ResultData(): array
    {
        if($this->_response?->response ?? null) {
            return (array)$this->_response?->response?->docs ?? [];
        } elseif ($this->_response?->status ?? null) {
            return (array)$this->_response?->status ?? [];
        } elseif ($this->_response?->fieldTypes ?? null) {
            return (array)$this->_response?->fieldTypes ?? [];
        } elseif ($this->_response?->fields ?? null) {
            return (array)$this->_response?->fields ?? [];
        }

        return [];
    }

    /**
     * Sets the collection name for the command result.
     *
     * @param string $name The collection name.
     * @public
     */
    public function SetCollectionName(string $name): void
    {
        if(! ($this->_response?->responseHeader ?? null)) {
            $this->_response->responseHeader = (object)[];
        }
        $this->_response->responseHeader->name = $name;
    }

    /**
     * Sets the returned ID for the command result.
     *
     * @param int|array $id The returned ID or an array of returned IDs.
     * @public
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
     *
     * @param ICommandResult $result The command result to merge with.
     * @public
     */
    public function MergeWith(ICommandResult $result): void
    {
        $queryInfo = $result->QueryInfo();
        $this->SetReturnedId($queryInfo->returned);
    }

}
