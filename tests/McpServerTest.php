<?php

namespace Colibri\Tests;

use Colibri\Mcp\Server;
use Colibri\Mcp\HttpTransport;
use PHPUnit\Framework\TestCase;

/**
 * Verifies reusable MCP dispatch independently of module and HTTP code.
 * @class
 * @memberof Colibri\Tests
 * @extends TestCase
 */
class McpServerTest extends TestCase
{
    /**
     * Negotiates a supported revision and exposes registered tools.
     * @return void
     * @public
     */
    public function testInitializeAndCall(): void
    {
        $server = new Server('test');
        $server->AddTool('echo', 'Returns text', ['type' => 'object', 'properties' => ['text' => ['type' => 'string']], 'required' => ['text'], 'additionalProperties' => false], static fn(array $args): array => ['text' => $args['text']]);
        $initialized = $server->Handle('{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-11-25","capabilities":{},"clientInfo":{"name":"test","version":"1"}}}');
        self::assertSame('2025-11-25', $initialized['result']['protocolVersion']);
        self::assertSame('echo', $server->Handle('{"jsonrpc":"2.0","id":2,"method":"tools/list"}')['result']['tools'][0]['name']);
        $reply = $server->Handle('{"jsonrpc":"2.0","id":"call","method":"tools/call","params":{"name":"echo","arguments":{"text":"hello"}}}');
        self::assertSame('hello', $reply['result']['structuredContent']->text);
        self::assertFalse($reply['result']['isError']);
        self::assertTrue($server->Handle('{"jsonrpc":"2.0","id":3,"method":"tools/call","params":{"name":"echo","arguments":{"text":3}}}')['result']['isError']);
    }

    /**
     * Handles malformed input, unknown operations, notifications and empty results.
     * @return void
     * @public
     */
    public function testErrorsAndNotifications(): void
    {
        $server = new Server('test');
        self::assertSame(-32700, $server->Handle('{')['error']['code']);
        self::assertSame(-32600, $server->Handle('[]')['error']['code']);
        self::assertSame(-32601, $server->Handle('{"jsonrpc":"2.0","id":1,"method":"unknown"}')['error']['code']);
        self::assertNull($server->Handle('{"jsonrpc":"2.0","method":"notifications/initialized"}'));
        self::assertSame('{}', json_encode($server->Handle('{"jsonrpc":"2.0","id":0,"method":"ping"}')['result']));
    }

    /**
     * Enforces stateless HTTP framing, origin checks and empty notification replies.
     * @return void
     * @public
     */
    public function testHttpTransport(): void
    {
        $transport = new HttpTransport(new Server('test'), ['https://allowed.test'], 1024);
        $headers = ['Content-Type' => 'application/json', 'Accept' => 'application/json, text/event-stream'];
        $ping = '{"jsonrpc":"2.0","id":1,"method":"ping"}';
        $reply = $transport->Handle('POST', $headers, $ping);
        self::assertSame(200, $reply->status);
        self::assertSame('{"jsonrpc":"2.0","id":1,"result":{}}', $reply->body);
        $notification = $transport->Handle('POST', $headers, '{"jsonrpc":"2.0","method":"notifications/initialized"}');
        self::assertSame(202, $notification->status);
        self::assertSame('', $notification->body);
        self::assertSame(405, $transport->Handle('GET', [], '')->status);
        self::assertSame(405, $transport->Handle('DELETE', [], '')->status);
        self::assertSame(403, $transport->Handle('POST', $headers + ['Origin' => 'https://forbidden.test'], $ping)->status);
        self::assertSame(204, $transport->Handle('OPTIONS', ['Origin' => 'https://allowed.test'], '')->status);
        self::assertSame(403, $transport->Handle('OPTIONS', ['Origin' => 'null'], '')->status);
        self::assertSame(400, $transport->Handle('POST', $headers + ['MCP-Protocol-Version' => 'invalid'], $ping)->status);
        self::assertSame(415, $transport->Handle('POST', ['Content-Type' => 'text/plain', 'Accept' => $headers['Accept']], $ping)->status);
        self::assertSame(406, $transport->Handle('POST', ['Content-Type' => 'application/json'], $ping)->status);
        self::assertSame(413, $transport->Handle('POST', $headers, str_repeat('x', 1025))->status);
    }

    /**
     * Never invokes a tool from a notification or accepts arguments outside its schema.
     * @return void
     * @public
     */
    public function testStrictToolDispatch(): void
    {
        $called = 0;
        $server = new Server('test');
        $server->AddTool('write', 'Writes', ['type' => 'object', 'additionalProperties' => false], static function (array $args) use (&$called): array {
            $called++;
            return ['ok' => true];
        });
        self::assertNull($server->Handle('{"jsonrpc":"2.0","method":"tools/call","params":{"name":"write"}}'));
        self::assertSame(0, $called);
        self::assertTrue($server->Handle('{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"write","arguments":{"unexpected":1}}}')['result']['isError']);
        self::assertSame(0, $called);
        self::assertFalse($server->Handle('{"jsonrpc":"2.0","id":2,"method":"tools/call","params":{"name":"write"}}')['result']['isError']);
        self::assertSame(1, $called);
    }
}