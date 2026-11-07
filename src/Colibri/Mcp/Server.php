<?php

namespace Colibri\Mcp;

use Closure;
use InvalidArgumentException;
use Opis\JsonSchema\Validator;
use RuntimeException;
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
     * Maximum serialized result returned by one MCP query program.
     * @const int
     * @public
     */
    public const QueryMaxResultBytes = 262144;

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
     * Registers the bounded query tool for composing registered read-only tools.
     * @return static This server.
     * @public
     */
    public function EnableQueryLanguage(): static
    {
        $this->AddTool(
            'mcp_query',
            'Compose up to eight registered read-only MCP tools in one program and return only the selected result.',
            [
                'type' => 'object',
                'properties' => ['program' => ['type' => 'string', 'minLength' => 1, 'maxLength' => QueryLanguage::MaxProgramLength]],
                'required' => ['program'],
                'additionalProperties' => false
            ],
            fn(array $arguments): mixed => $this->_runQuery($arguments['program']),
            ['readOnlyHint' => true, 'destructiveHint' => false, 'idempotentHint' => true, 'openWorldHint' => false]
        );
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
     * Executes a parsed program using only registered read-only tool handlers.
     * @param string $source Program source.
     * @return mixed Selected result value.
     * @private
     * @ignore
     */
    private function _runQuery(string $source): mixed
    {
        $program = (new QueryLanguage())->Parse($source);
        $variables = [];
        foreach ($program['statements'] as $statement) {
            if ($statement['type'] === 'return') {
                $result = $this->_evaluateQueryValue($statement['value'], $variables);
                $encoded = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                if (strlen($encoded) > self::QueryMaxResultBytes) {
                    throw new InvalidArgumentException('MCP query result is too large; return fewer fields or split the request');
                }
                return $result;
            }
            $toolName = $statement['name'];
            if ($toolName === 'mcp_query' || !isset($this->_tools[$toolName])) {
                throw new InvalidArgumentException('MCP query references an unknown tool');
            }
            $tool = $this->_tools[$toolName];
            $annotations = $tool['definition']['annotations'] ?? null;
            if (!$annotations instanceof stdClass || ($annotations->readOnlyHint ?? false) !== true) {
                throw new InvalidArgumentException('MCP query can call only tools marked read-only');
            }
            $arguments = [];
            foreach ($statement['arguments'] as $name => $expression) {
                $arguments[$name] = $this->_evaluateQueryValue($expression, $variables);
            }
            $argumentObject = json_decode(json_encode((object)$arguments, JSON_THROW_ON_ERROR));
            $schema = json_decode(json_encode($tool['definition']['inputSchema'], JSON_THROW_ON_ERROR));
            if (!(new Validator())->validate($argumentObject, $schema)->isValid()) {
                throw new InvalidArgumentException('MCP query arguments do not match the called tool schema');
            }
            try {
                $variables[$statement['target']] = ($tool['handler'])($arguments);
            } catch (InvalidArgumentException $exception) {
                throw $exception;
            } catch (Throwable $exception) {
                error_log('MCP query tool failed: ' . $toolName . ': ' . $exception->getMessage());
                throw new RuntimeException('MCP query tool failed', 0, $exception);
            }
        }
        throw new InvalidArgumentException('MCP query program did not return a value');
    }

    /**
     * Evaluates one data-only expression node against previous tool results.
     * @param array $expression Parsed expression node.
     * @param array $variables Program variables.
     * @return mixed Evaluated value.
     * @private
     * @ignore
     */
    private function _evaluateQueryValue(array $expression, array $variables): mixed
    {
        if ($expression['type'] === 'literal') {
            return $expression['value'];
        }
        if ($expression['type'] === 'array') {
            $items = [];
            foreach ($expression['items'] as $item) {
                $items[] = $this->_evaluateQueryValue($item, $variables);
            }
            return $items;
        }
        if ($expression['type'] === 'object') {
            $object = new stdClass();
            foreach ($expression['properties'] as $name => $value) {
                $object->{$name} = $this->_evaluateQueryValue($value, $variables);
            }
            return $object;
        }
        if ($expression['type'] === 'reference') {
            $value = $variables[$expression['name']];
            foreach ($expression['path'] as $segment) {
                if (is_array($value) && array_key_exists($segment, $value)) {
                    $value = $value[$segment];
                } elseif ($value instanceof stdClass && is_string($segment) && property_exists($value, $segment)) {
                    $value = $value->{$segment};
                } else {
                    throw new InvalidArgumentException('MCP query references a missing result field');
                }
            }
            return $value;
        }
        throw new InvalidArgumentException('Unsupported MCP query expression');
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