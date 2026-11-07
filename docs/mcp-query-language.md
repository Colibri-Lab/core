---
title: MCP Query Language
description: Bounded language for composing registered read-only MCP tools.
---

# MCP Query Language

**Status:** Implemented in Colibri Core. Servers opt in with `Server::EnableQueryLanguage()`; the Tasks server enables it during tool registration.

## Purpose

The MCP Query Language (MQL) composes a small number of existing MCP tools into one request. A program can pass one tool's result to another and return only the fields needed by the caller. MQL is independent of application models and does not issue database queries.

MQL reduces MCP round trips and can reduce returned data through projections. It does not remove other tools from `tools/list`, nor reduce the tokens needed for data explicitly returned, such as a complete discussion.

## Example

```text
let $matches = call projects_list(search: "Tasks", pageSize: 20);
let $project = call projects_get(id: $matches.items[0].id);
let $tasks = call tasks_list(projectId: $project.id, pageSize: 20);
return {project: {id: $project.id, title: $project.title}, tasks: $tasks.items};
```

Each call runs in order. Intermediate results remain server-side; only the final `return` value is sent to the MCP client.

To enable MQL on a server:

```php
$server->EnableQueryLanguage();
```

The method registers the `mcp_query` tool with one required string argument, `program`.

## Syntax

A program contains one or more `let` calls followed by exactly one `return`:

```text
let $variable = call tool_name(argument: expression, ...);
return expression;
```

Bare tool names must start with a letter or underscore and may then contain letters, digits, underscores, dots or hyphens. Argument names and object keys are identifiers. A tool call may have no arguments.

Expressions are JSON-like literals (`"text"`, numbers, `true`, `false`, `null`), arrays, objects, or references to an earlier tool result. References may select object properties and zero-based array indexes:

```text
$project.id
$projects.items[0].id
```

Calls are allowed only as the right-hand side of a `let` statement. MQL has no conditionals, loops, assignment after declaration, operators, function calls, imports or user-defined code. Variables cannot be redeclared; references to undeclared variables are rejected.

## Execution Rules

- MQL is opt-in. `Server::EnableQueryLanguage()` registers `mcp_query`; it does not change MCP protocol behavior for servers that do not enable it.
- A program may invoke at most eight tools. Each invoked tool must have the strict boolean annotation `readOnlyHint: true`. Missing or false annotations are rejected, and `mcp_query` cannot invoke itself.
- Every nested argument object is validated against that tool's original JSON Schema before its handler runs.
- Tools execute sequentially through their registered handlers. The interpreter does not discover methods, construct URLs, or bypass the tool registry.
- Only the final value is returned. Use object/array literals to project selected fields from intermediate results.

## Limits and Errors

The current Core limits are:

- Program source: 8,192 bytes maximum.
- Tool calls: 8 maximum.
- Expression nesting: 8 levels maximum.
- Entries in an object or array literal: 32 maximum.
- Serialized returned result: 262,144 bytes maximum.

The parser rejects malformed syntax, unsupported expressions, undeclared variables and excessive nesting. Execution rejects unknown tools, non-read-only tools, invalid nested arguments and references to missing result fields. Oversized results produce an explicit error; they are never silently truncated.

Errors do not expose stack traces, SQL or filesystem details. Invalid tool arguments are reported as model-visible query errors; unexpected handler failures are logged and returned as generic tool failures.

## Safety

MQL is a data-only interpreter, not a sandbox for generated source code. It never uses `eval`, `exec`, a shell, a template engine or dynamic method discovery. Only explicitly registered tools can run, and the query tool permits only those marked read-only. Mutations remain separate MCP tools with their own argument schemas and application checks.

The read-only annotation is an application-provided contract; it does not replace authorization or network controls. A query endpoint inherits the security policy of its MCP server. MQL itself provides no authentication or authorization.

## Colibri Integration

`Colibri\Mcp\Server` remains a generic protocol dispatcher. Modules opt in after registering their tools. The query language can compose any registered read-only tool whose argument schema and result shape are understood by the caller; it does not expose application models automatically.
