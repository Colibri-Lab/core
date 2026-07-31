# Colibri Core

`colibri/core` is the foundational library for the Colibri platform. It provides application infrastructure: application bootstrap and configuration, collections, HTTP request and response handling, data and filesystem access, events, logging, queues, XML, and utility classes.

## Requirements

- PHP 8.0 or later;
- Composer;
- PHP extensions required by the enabled components, such as `json`, `mbstring`, `openssl`, and `bcmath`;
- integration-specific drivers, such as database, MongoDB, GD, or FTP extensions.

## Installation

Install the package through Composer:

```bash
composer require colibri/core
```

Composer registers the `Colibri\` namespace for `src/Colibri/` and loads global functions from `src/Colibri/Utils/functions.php`.

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Colibri\Common\UUIDHelper;

$id = UUIDHelper::v4();
```

## Structure

| Directory | Purpose |
| --- | --- |
| `src/Colibri/Collections` | Typed collections, lists, and iterators. |
| `src/Colibri/Common` | Utilities for strings, validation, tokens, UUIDs, dates, and more. |
| `src/Colibri/Data` | Data-access abstractions, SQL and NoSQL drivers, storage, and trees. |
| `src/Colibri/Encryption` | Encryption and cryptographic utilities. |
| `src/Colibri/Events` | Event dispatcher and event models. |
| `src/Colibri/Graphics` | Geometry types and graphics support. |
| `src/Colibri/IO` | Filesystem, FTP, and incoming-data handling. |
| `src/Colibri/Modules` | Application module management. |
| `src/Colibri/Queue` and `src/Colibri/Threading` | Queues, processes, and background tasks. |
| `src/Colibri/Utils` | Configuration, caching, logging, performance, and debugging. |
| `src/Colibri/Web` | HTTP, routing, sessions, controllers, views, and templates. |
| `src/Colibri/Xml` | XML/XSD reading, building, and serialization. |
| `tests` | Core PHPUnit tests. |

## Application Initialization

`Colibri\App` is the main application object. It is a singleton that initializes configuration, HTTP objects, the event dispatcher, router, logging, and other subsystems.

```php
<?php

use Colibri\App;

$app = App::Instance();
$app->Initialize();
```

Before initialization, the application must have an accessible configuration and a correctly resolved project root. The exact configuration depends on the enabled modules and runtime environment.

## Examples

### Collection

```php
use Colibri\Collections\Collection;

$settings = new Collection(['Theme' => 'dark']);
$settings->Add('language', 'ru');

echo $settings->theme; // dark
echo $settings['language']; // ru
```

### Time-Limited Token

```php
use Colibri\Common\TokenHelper;

$token = TokenHelper::Generate('application-secret', 300);
$isValid = TokenHelper::Validate($token, 'application-secret');
```

### UUID

```php
use Colibri\Common\UUIDHelper;

$uuid = UUIDHelper::v4();
```

## Tests

For development, install dependencies including `require-dev` and run the suite:

```bash
composer install
composer test
```

The tests use PHPUnit. In addition to component unit tests, the suite validates the syntax of every PHP file in `src` and confirms that every declared type can be autoloaded.

## License

Proprietary. Use and distribution are governed by the package owner's terms.
