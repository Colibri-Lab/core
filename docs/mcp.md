# MCP In Colibri

`Colibri\Mcp\Server` is a protocol dispatcher, not a network daemon. Modules use the existing Colibri HTTP server and their normal route configuration. No MCP SDK or extra listener is required.

## Supported Profile

- JSON-RPC 2.0 and MCP revisions `2025-11-25`, `2025-06-18`, `2025-03-26`.
- `initialize`, `ping`, `tools/list`, `tools/call`, and accepted notifications.
- Stateless Streamable HTTP with JSON responses. Notifications return HTTP 202 with an empty body.
- GET and DELETE return 405: SSE streams and stateful MCP sessions are not implemented.
- No resources, prompts, sampling, subscriptions, server-to-client requests or legacy HTTP+SSE transport are advertised.
- Arguments are validated using the existing Opis JSON Schema dependency. Application handlers are registered explicitly, never discovered as arbitrary public methods.
- Native clients need no Origin header. Browser clients must use an exact configured allowed origin; an empty allowlist denies browser origins.
- Authentication is an application policy, not provided implicitly by this profile. Do not expose a deliberately anonymous module to untrusted networks.

## Register Tools

```php
$server = new \Colibri\Mcp\Server('my-module', '1.0.0');
$server->AddTool(
    'items_get',
    'Get an item by ID',
    [
        'type' => 'object',
        'properties' => ['id' => ['type' => 'integer', 'minimum' => 1]],
        'required' => ['id'],
        'additionalProperties' => false
    ],
    fn(array $arguments): array => $service->Get($arguments['id']),
    ['readOnlyHint' => true]
);
```

Handlers return serializable application data. The dispatcher supplies both `structuredContent` and JSON text content. An `InvalidArgumentException` becomes a model-visible tool error; other exceptions are logged without exposing internal details. No callback is executed for a notification.

## Connect A Module

Use the standard controller generator, then extend `Colibri\Mcp\Controller`. Implement protected `createServer(): Server`, returning the registered server. Override `allowedOrigins(): array` and `isEnabled(): bool` when needed. The inherited public `Index()` handles the HTTP request.

Register a module route such as `'/mcp': '/modules/<module>/mcp/index.json'` using the usual installer and module configuration includes. Do not call another controller from a tool: share application services and generated model APIs instead.

The framework's `RawResponse` integration returns unmodified bytes through both ReactPHP and the PHP web SAPI. It deliberately bypasses RPC response envelopes, encryption and localization, preserving tool data such as literal language placeholders and source code.

## Client Requests

POST JSON with `Content-Type: application/json` and `Accept: application/json, text/event-stream`. After initialization, include the negotiated `MCP-Protocol-Version` header. Unsupported explicit protocol headers return HTTP 400. Request bodies larger than 1 MiB return 413; this does not truncate tool output or discussion history.

The server does not create an MCP session ID and does not rely on browser authentication cookies. Modules are responsible for authorization when required, returned data visibility, write attribution and deployment-level rate limits.