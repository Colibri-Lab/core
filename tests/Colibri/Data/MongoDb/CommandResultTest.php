<?php

use PHPUnit\Framework\TestCase;
use Colibri\Data\MongoDb\CommandResult;
use Colibri\Data\NoSqlClient\ICommandResult;

class MongoDbCommandResultTest extends TestCase
{
    public function testImplementsICommandResult(): void
    {
        $response = (object)['responseHeader' => (object)['status' => 0]];
        $result = new CommandResult($response);
        $this->assertInstanceOf(ICommandResult::class, $result);
    }

    public function testConstructorCreatesInstance(): void
    {
        $response = (object)['responseHeader' => (object)['status' => 0], 'docs' => []];
        $result = new CommandResult($response);
        $this->assertInstanceOf(CommandResult::class, $result);
    }

    public function testErrorReturnsNullWhenNoError(): void
    {
        $response = (object)['responseHeader' => (object)['status' => 0]];
        $result = new CommandResult($response);
        $this->assertNull($result->Error());
    }

    public function testResultDataReturnsArray(): void
    {
        $response = (object)[
            'responseHeader' => (object)['status' => 0],
            'docs' => []
        ];
        $result = new CommandResult($response);
        $this->assertIsArray($result->ResultData());
    }
}
