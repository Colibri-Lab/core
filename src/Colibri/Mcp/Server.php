<?php

namespace Colibri\Mcp;

use Closure;
use InvalidArgumentException;
use Opis\JsonSchema\Validator;
use stdClass;
use Throwable;

/**
 * Transport-independent MCP tools server with explicit registration.
 * @class
 * @memberof Colibri\Mcp
 * @extends Object
 */
class Server
{
    /**
     * Supported Streamable HTTP protocol revisions, newest first.
     * @const array
     */
    public const Versions = ['2025-11-25', '2025-06-18', '2025-03-26'];

    /**
     * Registered tool descriptions and handlers.
     * @var array
     * @private
     * @ignore
     */
    private array $_tools = [];

    /**
     * Server implementation identity.
     * @var array
     * @private
     * @ignore
     */
    private array $_info;

    /**
     * Instructions exposed to connected clients.
     * @var string
     * @private
     * @ignore
     */
    private string $_instructions;

    /**
     * Creates an explicitly configured protocol server.
     * @param string $name Server implementation name.
     * @param string $version Implementation version.
     * @param string $instructions Optional client instructions.
     * @public
     */
    public function __construct(string $name, string $version = '1.0.0', string $instructions = '')
    {
        $this->_info = ['name' => $name, 'version' => $version];
        $this->_instructions = $instructions;
    }

    /**
     * Registers a named tool without coupling protocol code to application models.
     * @param string $name Unique MCP tool name.
     * @param string $description Tool purpose and usage.
     * @param array $schema JSON Schema for an arguments object.
     * @param callable $handler Receives validated arguments as an associative array.
     * @param array $annotations MCP tool annotations.
     * @return static This server.
     * @public
     */
    public function AddTool(string $name, string $description, array $schema, callable $handler, array $annotations = []): static
    {
        if (!preg_match('/^[A-Za-z0-9_.-]{1,128}$/D', $name) || isset($this->_tools[$name])) {
            throw new InvalidArgumentException('Invalid or duplicate MCP tool name');
        }
        if (($schema['type'] ?? null) !== 'object') {
            throw new InvalidArgumentException('MCP tool input schema must describe an object');
        }
        $definition = ['name' => $name, 'description' => $description, 'inputSchema' => $schema];
        if (isset($definition['inputSchema']['properties']) && $definition['inputSchema']['properties'] === []) {
            $definition['inputSchema']['properties'] = new stdClass();
        }
        if ($annotations !== []) {
            $definition['annotations'] = (object)$annotations;
        }
        $this->_tools[$name] = ['definition' => $definition, 'handler' => Closure::fromCallable($handler)];
        return $this;
    }

    /**
     * Dispatches one JSON-RPC request; accepted notifications have no response.
     * @param string $body UTF-8 encoded JSON-RPC envelope.
     * @return array|null JSON-RPC response, or null for a notification.
     * @public
     */
    public function Handle(string $body): ?array
    {
        try {
            $message = json_decode($body, false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->_error(null, -32700, 'Parse error');
        }
        if (!$message instanceof stdClass || ($message->jsonrpc ?? null) !== '2.0') {
            return $this->_error(null, -32600, 'Invalid Request');
        }
        $hasId = property_exists($message, 'id');
        $id = $message->id ?? null;
        if ($hasId && !is_string($id) && !is_int($id)) {
            return $this->_error(null, -32600, 'Invalid request identifier');
        }
        if (!is_string($message->method ?? null) || (isset($message->params) && !$message->params instanceof stdClass)) {
            return $this->_error($id, -32600, 'Invalid Request');
        }
        if (!$hasId) {
            return null;
        }
        $params = $message->params ?? new stdClass();
        if ($message->method === 'initialize') {
            if (!is_string($params->protocolVersion ?? null) || !($params->capabilities ?? null) instanceof stdClass
                || !is_object($params->clientInfo ?? null) || !is_string($params->clientInfo->name ?? null)
                || !is_string($params->clientInfo->version ?? null)) {
                return $this->_error($id, -32602, 'Invalid initialization parameters');
            }
            $version = in_array($params->protocolVersion, self::Versions, true) ? $params->protocolVersion : self::Versions[0];
            return $this->_result($id, [
                'protocolVersion' => $version,
                'capabilities' => ['tools' => ['listChanged' => false]],
                'serverInfo' => $this->_info,
                'instructions' => $this->_instructions
            ]);
        }
        if ($message->method === 'ping') {
            return $this->_result($id, new stdClass());
        }
        if ($message->method === 'tools/list') {
            if (isset($params->cursor)) {
                return $this->_error($id, -32602, 'This tool list does not use pagination cursors');
            }
            return $this->_result($id, ['tools' => array_values(array_map(static fn(array $tool): array => $tool['definition'], $this->_tools))]);
        }
        if ($message->method === 'tools/call') {
            if (!is_string($params->name ?? null) || !isset($this->_tools[$params->name])) {
                return $this->_error($id, -32602, 'Unknown tool');
            }
            $arguments = $params->arguments ?? new stdClass();
            if (!$arguments instanceof stdClass) {
                return $this->_error($id, -32602, 'Tool arguments must be an object');
            }
            $tool = $this->_tools[$params->name];
            try {
                $schema = json_decode(json_encode($tool['definition']['inputSchema'], JSON_THROW_ON_ERROR));
                if (!(new Validator())->validate($arguments, $schema)->isValid()) {
                    return $this->_result($id, $this->_toolError('Arguments do not match the tool input schema'));
                }
                $data = ($tool['handler'])(get_object_vars($arguments));
                $structured = is_array($data) || is_object($data) ? $data : ['value' => $data];
                if (is_array($structured) && array_is_list($structured)) {
                    $structured = ['items' => $structured];
                }
                return $this->_result($id, [
                    'content' => [['type' => 'text', 'text' => json_encode($structured, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]],
                    'structuredContent' => (object)$structured,
                    'isError' => false
                ]);
            } catch (InvalidArgumentException $exception) {
                return $this->_result($id, $this->_toolError($exception->getMessage()));
            } catch (Throwable $exception) {
                error_log('MCP tool failed: ' . $params->name . ': ' . $exception->getMessage());
                return $this->_result($id, $this->_toolError('Tool execution failed'));
            }
        }
        return $this->_error($id, -32601, 'Method not found');
    }

    /**
     * Creates a successful JSON-RPC envelope.
     * @param string|int $id Request identifier.
     * @param mixed $result Protocol result.
     * @return array Response envelope.
     * @private
     * @ignore
     */
    private function _result(string|int $id, mixed $result): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
    }

    /**
     * Creates a protocol error without leaking implementation details.
     * @param string|int|null $id Request identifier.
     * @param int $code JSON-RPC error code.
     * @param string $message Error description.
     * @return array Error envelope.
     * @private
     * @ignore
     */
    private function _error(string|int|null $id, int $code, string $message): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]];
    }

    /**
     * Creates a model-visible tool failure, distinct from protocol errors.
     * @param string $message Safe error description.
     * @return array Tool result.
     * @private
     * @ignore
     */
    private function _toolError(string $message): array
    {
        return ['content' => [['type' => 'text', 'text' => $message]], 'isError' => true];
    }
}