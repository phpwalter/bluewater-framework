# Bluewater Application Developer Guide

This guide is for developers building applications on top of Bluewater.

Application code belongs outside the framework package. Treat `vendor/bluewater/framework` as immutable. All application-specific code, configuration, services, endpoints, middleware, DTOs, extensions, and runtime data belong under the active application's directory.

## Requirements

- PHP 8.3 or newer
- Composer 2
- A supported web/runtime environment such as PHP-FPM behind Apache or Nginx

## Host layout

A Bluewater host can serve multiple isolated applications while sharing one physical Composer installation of the framework and common dependencies.

Recommended layout:

```text
host/
├── app/
│   ├── app_1/
│   │   ├── Bootstrap.php
│   │   ├── config/
│   │   ├── Endpoints/
│   │   ├── DTO/
│   │   ├── Middleware/
│   │   ├── Services/
│   │   ├── Extensions/
│   │   ├── cache/
│   │   └── logs/
│   ├── app_2/
│   └── app_3/
├── public/
│   ├── app_1/index.php
│   ├── app_2/index.php
│   └── app_3/index.php
├── vendor/
│   └── bluewater/framework/
└── composer.json
```

Each application has its own namespace, configuration, endpoints, cache, and logs. Applications do not share endpoints implicitly.

## Installing Bluewater in a host project

In the host project:

```bash
composer require bluewater/framework
```

For local framework development, a Composer path repository may be used instead.

The host's Composer installation owns Bluewater and shared third-party packages. Application-specific PHP classes do not need to be added to the host `composer.json` autoload section because Bluewater dynamically registers the active application's namespace at runtime.

## Application selection

The web server or PHP-FPM pool selects the application externally.

Typical environment variables:

```text
BLUEWATER_APP=app_1
BLUEWATER_ENV=production
BLUEWATER_APP_BASE=/var/www/bluewater-host/app
```

Use a separate FPM pool per application where practical. This gives each app isolated process-level configuration while all applications can still share the same physical `vendor/` tree.

The front controller should remain generic.

Example:

```php
<?php

declare(strict_types=1);

use Bluewater\Host;
use Bluewater\Runtime\FpmAdapter;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$appName = getenv('BLUEWATER_APP');
if (!is_string($appName) || $appName === '') {
    throw new RuntimeException('BLUEWATER_APP must be configured by the runtime.');
}

Host::fromEnvironment()
    ->application($appName)
    ->run(new FpmAdapter());
```

## Required application structure

At minimum, each app needs:

```text
app/app_1/
├── Bootstrap.php
├── config/
├── Endpoints/
├── cache/
└── logs/
```

Bluewater may create `cache/` and `logs/` automatically if they are missing and writable. Missing or invalid required application structure fails loudly at bootstrap.

## Application namespace

Each application has its own namespace.

Example:

```text
app_1
```

might use:

```text
Apps\App1
```

Bluewater dynamically maps that namespace to the active app directory at runtime. Adding a new application does not require `composer dump-autoload` for application classes.

A typical endpoint class resolves like:

```text
Apps\App1\Endpoints\Users
```

from:

```text
app/app_1/Endpoints/users.php
```

## Bootstrap lifecycle

Every application must define a `Bootstrap` class implementing `Bluewater\ApplicationBootstrap`.

Example:

```php
<?php

declare(strict_types=1);

namespace Apps\App1;

use Bluewater\Application;
use Bluewater\ApplicationBootstrap;

final class Bootstrap implements ApplicationBootstrap
{
    public function register(Application $app): void
    {
        // Register services, bindings, authentication providers,
        // serializers, extensions, database implementations, etc.
    }

    public function boot(Application $app): void
    {
        // Register global middleware and perform final initialization.
    }
}
```

Use `register()` for definitions and service wiring. Use `boot()` for initialization that depends on the configured application/container.

Do not put request-specific application logic in `Bootstrap`; that belongs in middleware or endpoints.

## Creating endpoints

Bluewater uses file-based endpoint discovery. You do not maintain a route manifest.

Create:

```text
app/app_1/Endpoints/users.php
```

Example:

```php
<?php

declare(strict_types=1);

namespace Apps\App1\Endpoints;

use Bluewater\Endpoint\Endpoint;

final class Users extends Endpoint
{
    public function get(): array
    {
        return [
            ['id' => 1, 'name' => 'Ada'],
        ];
    }

    public function getById(int $id): array
    {
        return [
            'id' => $id,
            'name' => 'Ada',
        ];
    }
}
```

Bluewater derives:

```text
GET /users
GET /users/{id}
```

No route registration, generated manifest, or manual compile step is required.

## HTTP handler naming

Normal handlers use HTTP verb conventions:

```text
get
post
put
patch
delete
options
head
```

Examples:

```php
public function get(): array
public function post(CreateUserRequest $request): UserDto
public function deleteById(int $id): Response
```

Dynamic route parameters may be derived from `By...` handler names.

Example:

```php
public function getByAccountIdAndUserId(
    int $accountId,
    int $userId,
): array
```

can derive a route shape with corresponding dynamic parameters.

## Exceptional routes with `#[Path]`

Do not force complicated URLs into increasingly complicated method names.

Use `#[Path]` when convention is insufficient.

Example:

```php
use Bluewater\Routing\Path;

#[Path('/{id}/permissions')]
public function getPermissions(int $id): array
{
    return ['user_id' => $id, 'permissions' => []];
}
```

For `users.php`, this produces:

```text
GET /users/{id}/permissions
```

Bluewater validates that route placeholders match method parameters during discovery.

## Route conflicts

Route conflicts fail loudly.

For example, these are considered the same route shape:

```text
/users/{id}
/users/{name}
```

If both are registered for the same HTTP method, application startup/discovery fails instead of silently choosing one.

Static routes take precedence over dynamic routes.

## Automatic route cache

Bluewater automatically compiles discovered routes into the active application's cache directory.

Example:

```text
app/app_1/cache/routes.php
```

You do not edit this file.

When endpoint or inherited directory-middleware files change, Bluewater detects the change and rebuilds the route cache atomically.

Adding an endpoint file is sufficient for it to become available; no developer compile command is required.

## Request parameters and dependency injection

Endpoint arguments are resolved automatically.

Resolution includes:

1. route parameters;
2. query-string parameters;
3. Bluewater `Request` injection;
4. DTO hydration from the request body;
5. registered/autowireable services from the DI container;
6. default parameter values.

Example:

```php
public function getById(
    int $id,
    UserRepository $users,
): UserDto {
    return $users->find($id);
}
```

`$id` comes from the route and `UserRepository` comes from the container.

## Registering application services

Use `Bootstrap::register()`.

Example:

```php
public function register(Application $app): void
{
    $app->services()->bind(
        UserRepository::class,
        DatabaseUserRepository::class,
    );
}
```

Concrete services with resolvable constructors may be autowired without explicit registration.

Use explicit bindings when:

- injecting an interface;
- replacing a Bluewater/default implementation;
- creating services requiring configuration or factories;
- choosing between multiple implementations.

## DTOs and validation

Use typed DTOs for request data when practical.

Example:

```php
namespace Apps\App1\DTO;

use Bluewater\Validation\Email;
use Bluewater\Validation\MinLength;
use Bluewater\Validation\Required;

final readonly class CreateUserRequest
{
    public function __construct(
        #[Required, Email]
        public string $email,

        #[Required, MinLength(2)]
        public string $name,
    ) {}
}
```

Then use the DTO directly in the endpoint:

```php
public function post(
    CreateUserRequest $request,
    UserRepository $users,
): UserDto {
    return $users->create($request);
}
```

Bluewater hydrates and validates the DTO automatically.

Validation failures return HTTP 422 with field-level errors.

## Returning responses

Endpoint handlers may return:

- `Bluewater\Http\Response`;
- arrays;
- DTOs/objects;
- scalars;
- collections.

Bluewater converts normal return values through the serializer registry.

Example:

```php
public function get(): array
{
    return ['status' => 'ok'];
}
```

For explicit responses:

```php
use Bluewater\Http\Response;

return Response::json(['created' => true], 201);
```

## Content negotiation

Bluewater uses request headers, especially `Accept`, to select a response serializer.

Built-in formats include:

```text
application/json
application/xml
text/csv
text/*
```

Application-specific serializers can be registered through Bluewater's serializer extension surface.

JSON should generally remain the default API format.

## Middleware

Bluewater supports four effective middleware scopes.

### Global middleware

Register in `Bootstrap::boot()`.

Example:

```php
public function boot(Application $app): void
{
    $app->middleware()->add(RequestLoggingMiddleware::class);
}
```

Use for behavior that applies to the entire application.

Examples:

- request logging;
- CORS;
- tracing;
- global security headers.

### Directory middleware

Create:

```text
Endpoints/admin/_middleware.php
```

returning middleware class names:

```php
<?php

return [
    AdminAuthenticationMiddleware::class,
];
```

Endpoints below that directory inherit the middleware.

### Endpoint-class middleware

Use repeatable `#[UseMiddleware]` on the endpoint class.

```php
use Bluewater\Middleware\UseMiddleware;

#[UseMiddleware(AppHeaderMiddleware::class)]
final class Users extends Endpoint
{
}
```

### Endpoint-method middleware

Apply middleware to one HTTP handler:

```php
#[UseMiddleware(DeleteAuthorizationMiddleware::class)]
public function deleteById(int $id): Response
{
    // ...
}
```

## Authentication

Authentication is middleware-driven. Endpoint business logic should not manually parse JWTs or API keys unless there is a very specific reason.

Bluewater provides initial support for:

- API keys;
- HS256 JWTs;
- OAuth bearer tokens using an application-provided introspector.

Register providers in `Bootstrap::register()` and attach the appropriate middleware globally, by directory, class, or method.

This keeps authentication policy separate from endpoint business logic.

## Configuration

Bluewater framework defaults are installed under:

```text
vendor/bluewater/framework/config/
```

Do not modify them.

Application overrides belong under:

```text
app/app_1/config/
```

Examples:

```text
App.ini.php
App.db.ini.php
App.logging.ini.php
```

Bluewater loads defaults first and app overrides second.

You do not need to copy every core config file into the application. Create only the application overrides you need.

Example:

```ini
<?php
exit;
?>
[database]
DRIVER = sqlite
DATABASE = "{APP_ROOT}/data/app.sqlite"
```

The protected PHP header is part of Bluewater's configuration-file convention.

## Configuration references

Configuration values may reference other approved values.

Examples:

```ini
LOG_PATH = "{APP_ROOT}/logs"
CACHE_FILE = "{CACHE_ROOT}/example.php"
```

Supported legacy runtime placeholders include:

```text
{APP_ROOT}
{CACHE_ROOT}
{BLUEWATER}
{SITE_ROOT}
{DS}
```

Unknown references and circular references fail loudly during bootstrap.

## Immutable framework identity

Applications may not override locked framework identity such as:

```text
BW_VER
```

If an app attempts to change a locked setting, Bluewater fails bootstrap rather than silently ignoring it.

## Config cache

Effective merged configuration is compiled into:

```text
app/app_1/cache/config.php
```

Do not edit this file.

Bluewater regenerates it when source configuration changes.

## Database access

Bluewater core intentionally does not include an ORM.

It provides a small database contract and PDO-based implementation for:

- prepared statements;
- queries;
- transactions.

Applications may bind their own database abstraction or integrate an ORM through an explicit package/extension.

Typical application structure:

```text
Services/
├── UserRepository.php
└── DatabaseUserRepository.php
```

Application business logic should depend on repository/service interfaces rather than directly coupling every endpoint to PDO.

## Extensions

Reusable application capabilities may be packaged as Composer packages and registered explicitly.

A Bluewater extension implements:

```text
Bluewater\Extension\Extension
```

Extensions can register facilities such as:

- services;
- middleware;
- serializers;
- validators;
- authentication providers;
- OpenAPI components;
- database drivers.

Extension registration is explicit. Bluewater does not use hidden Composer package auto-discovery.

Normal reusable extensions should not silently add application endpoints.

## OpenAPI

Bluewater generates OpenAPI 3.1 metadata from discovered routes and application metadata.

Sources include:

- endpoint paths;
- HTTP methods;
- typed parameters;
- DTO definitions;
- return types;
- optional OpenAPI metadata attributes such as summaries.

The example application exposes generated metadata at:

```text
GET /openapi
```

Do not maintain a separate route manifest solely for OpenAPI.

## Logging

Use the logger supplied through Bluewater's PSR-3-compatible logging layer instead of `echo`, `print_r`, or ad hoc files.

Application logs belong under the app's runtime directory:

```text
app/app_1/logs/
```

Do not write application logs into `vendor/bluewater/framework`.

## Feature cost and disabling facilities

Bluewater is designed so optional facilities do not need to impose startup work when disabled.

For example, an app that does not use OpenAPI or database integration should not initialize those facilities unnecessarily.

Keep application bootstrap registrations intentional and minimal.

## Testing application code

Application tests should cover business behavior independently of the web server whenever possible.

Because the Bluewater kernel uses Bluewater `Request` and `Response` objects, application endpoints can be exercised without running Apache or FPM.

At minimum, test:

- successful endpoint behavior;
- invalid DTO input and HTTP 422 responses;
- authentication success/failure;
- middleware behavior;
- database/service bindings;
- custom route behavior;
- important configuration overrides.

For a full host integration test, boot the application through `Bluewater\Host` and issue Bluewater requests directly.

## Running the example application

The framework repository includes:

```text
examples/host/app/app_1
```

Use it as a reference implementation for application structure and supported patterns.

It demonstrates:

- dynamic namespace loading;
- `Bootstrap.php`;
- configuration overrides;
- SQLite/PDO;
- file-based endpoints;
- request DTOs;
- validation;
- all middleware scopes;
- API-key authentication;
- JWT authentication;
- OAuth introspection;
- application extensions;
- OpenAPI generation.

## Production deployment guidance

For a multi-application host, prefer one PHP-FPM pool per application.

Example conceptual deployment:

```text
app1.example.com
    → app_1 FPM pool
    → BLUEWATER_APP=app_1

app2.example.com
    → app_2 FPM pool
    → BLUEWATER_APP=app_2
```

Both can use the same physical:

```text
host/vendor/bluewater/framework
```

but maintain independent:

```text
config
cache
logs
endpoints
services
```

Application identity should be supplied by trusted web-server/FPM configuration, not by arbitrary request input.

## Application development workflow

A normal application change should be straightforward:

```text
create/edit endpoint, DTO, service, middleware or config
        ↓
run application tests
        ↓
exercise relevant route locally
        ↓
commit
        ↓
deploy
```

You do not regenerate a route manifest after adding an endpoint.

You do not add the application's namespace to Composer whenever you create a new class.

You do not modify Bluewater under `vendor/`.

You do not subclass framework internals to replace core services unless the API explicitly designates an inheritance point.

## Rules of thumb

Keep these boundaries clear:

```text
vendor/bluewater/framework
    = immutable framework package

app/app_1
    = application-owned code

app/app_1/cache
    = generated application runtime metadata

app/app_1/logs
    = application runtime logs
```

Prefer explicit DI bindings and extensions over framework hacks. Prefer middleware for request policy. Prefer DTOs for structured input. Prefer service/repository interfaces for business behavior. Let file convention define ordinary routes and use attributes only when they add meaningful metadata or handle exceptional routing needs.
