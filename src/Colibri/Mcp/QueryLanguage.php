<?php

namespace Colibri\Mcp;

use InvalidArgumentException;
use JsonException;

/**
 * Parses bounded MCP tool-composition programs into a data-only syntax tree.
 * @class
 * @memberof Colibri\Mcp
 * @extends Object
 */
class QueryLanguage
{
    /**
     * Maximum source length accepted by the parser.
     * @const int
    * @public
     */
    public const MaxProgramLength = 8192;

    /**
     * Maximum number of tool calls accepted in one program.
     * @const int
    * @public
     */
    public const MaxCalls = 8;

    /**
     * Maximum nested object or array depth.
     * @const int
    * @public
     */
    public const MaxNestingDepth = 8;

    /**
     * Maximum number of entries in one object or array literal.
     * @const int
    * @public
     */
    public const MaxCollectionItems = 32;

    /**
     * Tokenized program source.
     * @var array
     * @private
     * @ignore
     */
    private array $_tokens = [];

    /**
     * Current token position.
     * @var int
     * @private
     * @ignore
     */
    private int $_position = 0;

    /**
     * Variables declared earlier in the current program.
     * @var array
     * @private
     * @ignore
     */
    private array $_variables = [];

    /**
     * Parses a bounded program containing read-only tool call statements and a return value.
     * @param string $source Program source.
     * @return array Parsed statements and call count.
     * @public
     */
    public function Parse(string $source): array
    {
        if ($source === '' || strlen($source) > self::MaxProgramLength) {
            throw new InvalidArgumentException('MCP query program is empty or too long');
        }
        $this->_tokens = $this->_tokenize($source);
        $this->_position = 0;
        $this->_variables = [];
        $statements = [];
        $callCount = 0;
        $hasReturn = false;
        while ($this->_current()['type'] !== 'eof') {
            if ($this->_isIdentifier('let')) {
                if ($hasReturn || $callCount >= self::MaxCalls) {
                    throw new InvalidArgumentException('MCP query program exceeds its call limit or has statements after return');
                }
                $statements[] = $this->_parseCallStatement();
                $callCount++;
                continue;
            }
            if ($this->_isIdentifier('return')) {
                if ($hasReturn) {
                    throw new InvalidArgumentException('MCP query program can contain only one return statement');
                }
                $this->_consume();
                $value = $this->_parseExpression(0);
                $this->_expectValue(';');
                $statements[] = ['type' => 'return', 'value' => $value];
                $hasReturn = true;
                if ($this->_current()['type'] !== 'eof') {
                    throw new InvalidArgumentException('No statements may follow return');
                }
                continue;
            }
            throw new InvalidArgumentException('Expected let or return in MCP query program');
        }
        if (!$hasReturn) {
            throw new InvalidArgumentException('MCP query program must end with return');
        }
        return ['statements' => $statements, 'callCount' => $callCount];
    }

    /**
     * Tokenizes source without evaluating or compiling executable code.
     * @param string $source Program source.
     * @return array Tokens.
     * @private
     * @ignore
     */
    private function _tokenize(string $source): array
    {
        $tokens = [];
        $length = strlen($source);
        $offset = 0;
        while ($offset < $length) {
            $character = $source[$offset];
            if (ctype_space($character)) {
                $offset++;
                continue;
            }
            if ($character === '"') {
                $start = $offset++;
                $closed = false;
                while ($offset < $length) {
                    if ($source[$offset] === '\\') {
                        $offset += 2;
                        continue;
                    }
                    if ($source[$offset] === '"') {
                        $offset++;
                        $closed = true;
                        break;
                    }
                    $offset++;
                }
                if (!$closed) {
                    throw new InvalidArgumentException('Unterminated string literal');
                }
                try {
                    $value = json_decode(substr($source, $start, $offset - $start), true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException $exception) {
                    throw new InvalidArgumentException('Invalid string literal', 0, $exception);
                }
                if (!is_string($value)) {
                    throw new InvalidArgumentException('Invalid string literal');
                }
                $tokens[] = ['type' => 'literal', 'value' => $value];
                continue;
            }
            if ($character === '$') {
                $offset++;
                $start = $offset;
                if ($offset >= $length || !$this->_isIdentifierStart($source[$offset])) {
                    throw new InvalidArgumentException('Invalid variable reference');
                }
                $offset++;
                while ($offset < $length && $this->_isIdentifierPart($source[$offset])) {
                    $offset++;
                }
                $tokens[] = ['type' => 'variable', 'value' => substr($source, $start, $offset - $start)];
                continue;
            }
            if (ctype_digit($character) || ($character === '-' && isset($source[$offset + 1]) && ctype_digit($source[$offset + 1]))) {
                if (preg_match('/\G-?(?:0|[1-9][0-9]*)(?:\.[0-9]+)?(?:[eE][+-]?[0-9]+)?/', $source, $matches, 0, $offset) !== 1) {
                    throw new InvalidArgumentException('Invalid number literal');
                }
                $raw = $matches[0];
                try {
                    $value = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException $exception) {
                    throw new InvalidArgumentException('Invalid number literal', 0, $exception);
                }
                $tokens[] = ['type' => 'literal', 'value' => $value];
                $offset += strlen($raw);
                continue;
            }
            if ($this->_isIdentifierStart($character)) {
                $start = $offset++;
                while ($offset < $length && preg_match('/[A-Za-z0-9_.-]/', $source[$offset]) === 1) {
                    $offset++;
                }
                $value = substr($source, $start, $offset - $start);
                if ($value === 'true' || $value === 'false' || $value === 'null') {
                    if ($value === 'true') {
                        $literal = true;
                    } elseif ($value === 'false') {
                        $literal = false;
                    } else {
                        $literal = null;
                    }
                    $tokens[] = ['type' => 'literal', 'value' => $literal];
                } else {
                    $tokens[] = ['type' => 'identifier', 'value' => $value];
                }
                continue;
            }
            if (str_contains('(){}[],:;=.', $character)) {
                $tokens[] = ['type' => $character, 'value' => $character];
                $offset++;
                continue;
            }
            throw new InvalidArgumentException('Unsupported character in MCP query program');
        }
        $tokens[] = ['type' => 'eof', 'value' => null];
        return $tokens;
    }

    /**
     * Parses one variable assignment containing a named tool call.
     * @return array Call statement.
     * @private
     * @ignore
     */
    private function _parseCallStatement(): array
    {
        $this->_consume();
        $variable = $this->_expectType('variable')['value'];
        if (isset($this->_variables[$variable])) {
            throw new InvalidArgumentException('MCP query variables cannot be reassigned');
        }
        $this->_expectValue('=');
        if (!$this->_isIdentifier('call')) {
            throw new InvalidArgumentException('A let statement must call a registered MCP tool');
        }
        $this->_consume();
        $toolName = $this->_expectType('identifier')['value'];
        if (preg_match('/^[A-Za-z0-9_.-]{1,128}$/D', $toolName) !== 1) {
            throw new InvalidArgumentException('Invalid MCP tool name');
        }
        $this->_expectValue('(');
        $arguments = [];
        if (!$this->_isValue(')')) {
            do {
                $name = $this->_expectType('identifier')['value'];
                if (!$this->_isPlainIdentifier($name) || array_key_exists($name, $arguments)) {
                    throw new InvalidArgumentException('Invalid or duplicate MCP tool argument name');
                }
                $this->_expectValue(':');
                $arguments[$name] = $this->_parseExpression(0);
                if (!$this->_isValue(',')) {
                    break;
                }
                $this->_consume();
            } while (!$this->_isValue(')'));
        }
        $this->_expectValue(')');
        $this->_expectValue(';');
        $this->_variables[$variable] = true;
        return ['type' => 'call', 'target' => $variable, 'name' => $toolName, 'arguments' => $arguments];
    }

    /**
     * Parses a literal, variable reference, array or object expression.
     * @param int $depth Current nesting depth.
     * @return array Expression node.
     * @private
     * @ignore
     */
    private function _parseExpression(int $depth): array
    {
        if ($depth > self::MaxNestingDepth) {
            throw new InvalidArgumentException('MCP query expression is nested too deeply');
        }
        $token = $this->_current();
        if ($token['type'] === 'literal') {
            $this->_consume();
            return ['type' => 'literal', 'value' => $token['value']];
        }
        if ($token['type'] === 'variable') {
            $this->_consume();
            if (!isset($this->_variables[$token['value']])) {
                throw new InvalidArgumentException('MCP query references an undeclared variable');
            }
            $path = [];
            while ($this->_isValue('.') || $this->_isValue('[')) {
                if ($this->_isValue('.')) {
                    $this->_consume();
                    $property = $this->_expectType('identifier')['value'];
                    if (!$this->_isPlainIdentifier($property)) {
                        throw new InvalidArgumentException('Invalid property in MCP query reference');
                    }
                    $path[] = $property;
                    if (count($path) > self::MaxNestingDepth) {
                        throw new InvalidArgumentException('MCP query reference is nested too deeply');
                    }
                    continue;
                }
                $this->_consume();
                $index = $this->_expectType('literal')['value'];
                if (!is_int($index) || $index < 0) {
                    throw new InvalidArgumentException('MCP query array indexes must be non-negative integers');
                }
                $this->_expectValue(']');
                $path[] = $index;
                if (count($path) > self::MaxNestingDepth) {
                    throw new InvalidArgumentException('MCP query reference is nested too deeply');
                }
            }
            return ['type' => 'reference', 'name' => $token['value'], 'path' => $path];
        }
        if ($this->_isValue('{')) {
            return $this->_parseObject($depth + 1);
        }
        if ($this->_isValue('[')) {
            return $this->_parseArray($depth + 1);
        }
        throw new InvalidArgumentException('Expected a literal, variable reference, array or object');
    }

    /**
     * Parses an object literal with unique identifier keys.
     * @param int $depth Current nesting depth.
     * @return array Object expression node.
     * @private
     * @ignore
     */
    private function _parseObject(int $depth): array
    {
        $this->_consume();
        $properties = [];
        if (!$this->_isValue('}')) {
            do {
                $name = $this->_expectType('identifier')['value'];
                if (!$this->_isPlainIdentifier($name) || array_key_exists($name, $properties)) {
                    throw new InvalidArgumentException('Invalid or duplicate MCP query object key');
                }
                $this->_expectValue(':');
                $properties[$name] = $this->_parseExpression($depth);
                if (count($properties) > self::MaxCollectionItems) {
                    throw new InvalidArgumentException('MCP query object contains too many properties');
                }
                if (!$this->_isValue(',')) {
                    break;
                }
                $this->_consume();
            } while (!$this->_isValue('}'));
        }
        $this->_expectValue('}');
        return ['type' => 'object', 'properties' => $properties];
    }

    /**
     * Parses an array literal.
     * @param int $depth Current nesting depth.
     * @return array Array expression node.
     * @private
     * @ignore
     */
    private function _parseArray(int $depth): array
    {
        $this->_consume();
        $items = [];
        if (!$this->_isValue(']')) {
            do {
                $items[] = $this->_parseExpression($depth);
                if (count($items) > self::MaxCollectionItems) {
                    throw new InvalidArgumentException('MCP query array contains too many items');
                }
                if (!$this->_isValue(',')) {
                    break;
                }
                $this->_consume();
            } while (!$this->_isValue(']'));
        }
        $this->_expectValue(']');
        return ['type' => 'array', 'items' => $items];
    }

    /**
     * Checks whether a value is a plain field, property or object key identifier.
     * @param string $value Identifier value.
     * @return bool Whether the identifier is plain.
     * @private
     * @ignore
     */
    private function _isPlainIdentifier(string $value): bool
    {
        return preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $value) === 1;
    }

    /**
     * Checks whether a character can start an identifier.
     * @param string $character Character to check.
     * @return bool Whether the character can start an identifier.
     * @private
     * @ignore
     */
    private function _isIdentifierStart(string $character): bool
    {
        return preg_match('/^[A-Za-z_]$/D', $character) === 1;
    }

    /**
     * Checks whether a character can continue a variable identifier.
     * @param string $character Character to check.
     * @return bool Whether the character can continue an identifier.
     * @private
     * @ignore
     */
    private function _isIdentifierPart(string $character): bool
    {
        return preg_match('/^[A-Za-z0-9_]$/D', $character) === 1;
    }

    /**
     * Checks whether the current token is a specific identifier.
     * @param string $value Identifier value.
     * @return bool Whether the current token matches.
     * @private
     * @ignore
     */
    private function _isIdentifier(string $value): bool
    {
        return $this->_current()['type'] === 'identifier' && $this->_current()['value'] === $value;
    }

    /**
     * Checks whether the current token has a specific value.
     * @param string $value Token value.
     * @return bool Whether the current token matches.
     * @private
     * @ignore
     */
    private function _isValue(string $value): bool
    {
        return $this->_current()['value'] === $value;
    }

    /**
     * Returns the current token.
     * @return array Current token.
     * @private
     * @ignore
     */
    private function _current(): array
    {
        return $this->_tokens[$this->_position] ?? ['type' => 'eof', 'value' => null];
    }

    /**
     * Consumes and returns the current token.
     * @return array Consumed token.
     * @private
     * @ignore
     */
    private function _consume(): array
    {
        return $this->_tokens[$this->_position++] ?? ['type' => 'eof', 'value' => null];
    }

    /**
     * Consumes a token with the expected type.
     * @param string $type Expected token type.
     * @return array Consumed token.
     * @private
     * @ignore
     */
    private function _expectType(string $type): array
    {
        if ($this->_current()['type'] !== $type) {
            throw new InvalidArgumentException('Unexpected token in MCP query program');
        }
        return $this->_consume();
    }

    /**
     * Consumes a token with the expected value.
     * @param string $value Expected token value.
     * @return array Consumed token.
     * @private
     * @ignore
     */
    private function _expectValue(string $value): array
    {
        if (!$this->_isValue($value)) {
            throw new InvalidArgumentException('Unexpected token in MCP query program');
        }
        return $this->_consume();
    }
}