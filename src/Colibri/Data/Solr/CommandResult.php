<?php

/**
 * PgSql
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
 * This class extends SqlCommand and provides methods for preparing and executing queries.
 * 
 * @inheritDoc
 *
 */
final class CommandResult implements ICommandResult
{

    private ?object $_response = null;

    public function __construct(object $response)
    {
        $this->_response = $response;
    }

    public function Error(): ?object
    {
        if($this->_response?->error ?? false) {
            return json_decode($this->_response?->error);
        }
        return null;
    }

    public function QueryInfo(): object
    {
        $affected = count($this->ResultData());
        $count = count($this->ResultData());
        if($this->_response?->response ?? null) {
            $affected = $this->_response?->response->numFound;
        }
        return (object)[...(array)$this->_response->responseHeader, ...['affected' => $affected, 'count' => $count]];
    }

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

    public function SetCollectionName(string $name): void
    {
        if(! ($this->_response?->responseHeader ?? null) ) {
            $this->_response->responseHeader = (object)[];
        }
        $this->_response->responseHeader->name = $name;
    }

    public function SetReturnedId(int|array $id): void
    {
        if(!$this->_response->responseHeader) {
            $this->_response->responseHeader = (object)[];
        }
        if(! ($this->_response?->responseHeader?->returned ?? false) ) {
            $this->_response->responseHeader->returned = [];
        }
        if(!is_array($id)) {
            $id = [$id];
        }
        $this->_response->responseHeader->returned =
            [...$this->_response->responseHeader->returned, ...$id];
    }

    public function MergeWith(ICommandResult $result): void
    {
        $queryInfo = $result->QueryInfo();
        $this->SetReturnedId($queryInfo->returned);
    }

}

?>