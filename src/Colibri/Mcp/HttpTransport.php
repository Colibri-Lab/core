<?php

namespace Colibri\Mcp;

use Colibri\Web\RawResponse;

/**
 * Stateless Streamable HTTP transport using JSON responses without SSE.
 * @class
 * @memberof Colibri\Mcp
 * @extends Object
 */
class HttpTransport
{
    /**
     * Protocol dispatcher.
     * @var Server
     * @private
     * @ignore
     */
    private Server $_server;
    /**
     * Explicit browser origins allowed to reach this endpoint.
     * @var array
     * @private
     * @ignore
     */
    private array $_origins;
    /**
     * Maximum incoming JSON request size.
     * @var int
     * @private
     * @ignore
     */
    private int $_maxBytes;

    /**
     * Creates a transport for any registered MCP server.
     * @param Server $server Protocol dispatcher.
     * @param array $allowedOrigins Exact allowed origins; origin-less clients remain supported.
     * @param int $maxRequestBytes Incoming request size limit.
     * @public
     */
    public function __construct(Server $server, array $allowedOrigins = [], int $maxRequestBytes = 1048576)
    {
        $this->_server = $server;
        $this->_origins = $allowedOrigins;
        $this->_maxBytes = $maxRequestBytes;
    }

    /**
     * Handles HTTP framing and dispatches a single MCP message.
     * @param string $method HTTP method.
     * @param array $headers Incoming header mapping.
     * @param string $body Original request body.
     * @return RawResponse Exact protocol HTTP response.
     * @public
     */
    public function Handle(string $method, array $headers, string $body): RawResponse
    {
        $normalized = [];
        foreach ($headers as $name => $value) {
            $normalized[strtolower($name)] = is_array($value) ? implode(', ', $value) : (string)$value;
        }
        $responseHeaders = ['Content-Type' => 'application/json; charset=utf-8', 'Cache-Control' => 'no-store', 'X-Content-Type-Options' => 'nosniff'];
        $origin = $normalized['origin'] ?? null;
        if ($origin !== null) {
            if (!in_array($origin, $this->_origins, true)) {
                return new RawResponse(403, $responseHeaders);
            }
            $responseHeaders['Access-Control-Allow-Origin'] = $origin;
            $responseHeaders['Vary'] = 'Origin';
            $responseHeaders['Access-Control-Allow-Headers'] = 'Content-Type, MCP-Protocol-Version, MCP-Session-Id, Accept';
            $responseHeaders['Access-Control-Allow-Methods'] = 'POST, GET, OPTIONS';
        }
        if (strtoupper($method) === 'OPTIONS') {
            return new RawResponse(204, $responseHeaders);
        }
        if (strtoupper($method) !== 'POST') {
            $responseHeaders['Allow'] = 'POST, OPTIONS';
            return new RawResponse(405, $responseHeaders);
        }
        $version = $normalized['mcp-protocol-version'] ?? '2025-03-26';
        if (!in_array($version, Server::Versions, true)) {
            return new RawResponse(400, $responseHeaders);
        }
        if (strtolower(trim(explode(';', $normalized['content-type'] ?? '')[0])) !== 'application/json') {
            return new RawResponse(415, $responseHeaders);
        }
        $accept = strtolower($normalized['accept'] ?? '');
        if (!str_contains($accept, 'application/json') || !str_contains($accept, 'text/event-stream')) {
            return new RawResponse(406, $responseHeaders);
        }
        if (strlen($body) > $this->_maxBytes) {
            return new RawResponse(413, $responseHeaders);
        }
        $result = $this->_server->Handle($body);
        if ($result === null) {
            return new RawResponse(202, $responseHeaders);
        }
        return new RawResponse(200, $responseHeaders, json_encode($result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }
}