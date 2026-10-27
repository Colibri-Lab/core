<?php

namespace Colibri\Mcp;

use Colibri\App;
use Colibri\Web\PayloadCopy;
use Colibri\Web\RawResponse;
use Colibri\Web\RequestCollection;

/**
 * Reusable module endpoint for the existing Colibri HTTP server.
 * @class
 * @memberof Colibri\Mcp
 * @extends \Colibri\Web\Controller
 */
abstract class Controller extends \Colibri\Web\Controller
{
    /**
     * Builds the module's protocol server and explicitly registered tools.
     * @return Server Module-specific MCP server.
     * @protected
     */
    abstract protected function createServer(): Server;

    /**
     * Returns exact origins allowed for browser-based clients.
     * @return array Allowed origins; no Origin header is required for native agents.
     * @protected
     */
    protected function allowedOrigins(): array
    {
        return [];
    }

    /**
     * Determines whether the module currently exposes its endpoint.
     * @return bool Whether MCP is enabled.
     * @protected
     */
    protected function isEnabled(): bool
    {
        return true;
    }

    /**
     * Handles MCP on the normal module controller pipeline without RPC wrapping.
     * @param RequestCollection $get GET parameters.
     * @param RequestCollection $post POST parameters.
     * @param PayloadCopy|null $payload Framework payload copy, unused to preserve raw JSON.
     * @return RawResponse Protocol HTTP response.
     * @public
     */
    public function Index(RequestCollection $get, RequestCollection $post, ?PayloadCopy $payload = null): RawResponse
    {
        if (!$this->isEnabled()) {
            return new RawResponse(404, ['Cache-Control' => 'no-store']);
        }
        $headers = [];
        foreach (['Origin', 'Content-Type', 'Accept', 'MCP-Protocol-Version'] as $name) {
            $value = App::$request->headers->{$name};
            if ($value !== null) {
                $headers[$name] = $value;
            }
        }
        return (new HttpTransport($this->createServer(), $this->allowedOrigins()))->Handle(
            (string)App::$request->server->{'request_method'},
            $headers,
            App::$request->GetBody()
        );
    }
}