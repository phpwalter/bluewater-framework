# Bluewater 8

Bluewater is a lightweight, convention-first PHP 8.3+ API framework designed for small-to-medium APIs that need to scale to hundreds or thousands of endpoints without making developers maintain route manifests.

## Design goals

- **Drop in an endpoint file and it exists.** No route manifest or compile command.
- **Production speed.** Reflection and discovery happen only when endpoint/config files change; normal requests use app-local compiled PHP caches.
- **Shared core.** One physical Composer-managed Bluewater installation can serve multiple isolated applications.
- **Application code stays outside `vendor/`.** Apps extend Bluewater through public APIs, DI bindings, middleware, and explicit extensions.
- **Simple Bluewater API, PSR interoperability underneath.** PSR-11 container, PSR-3 logging, and explicit PSR-7/PSR-15 bridges.
- **Runtime neutral kernel.** FPM is the first runtime adapter, not an architectural assumption.

## Host layout

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

Each application should normally run in a separate PHP-FPM pool. The web server/FPM configuration selects the app through `BLUEWATER_APP`; the front controller remains identical.

## Endpoint convention

A single file can contain multiple HTTP handlers:

```php
namespace Apps\App1\Endpoints;

use Bluewater\Endpoint\Endpoint;

final class Users extends Endpoint
{
    public function get(): array
    {
        return [];
    }

    public function getById(int $id): array
    {
        return ['id' => $id];
    }
}
```

Saved as:

```text
app/app_1/Endpoints/users.php
```

Bluewater derives:

```text
GET /users
GET /users/{id}
```

No route registration or manifest regeneration is required.

### Advanced paths

Use `#[Bluewater\Routing\Path]` only when convention is insufficient. Convention creates the normal route; attributes refine exceptional cases.

## Route caching

Bluewater fingerprints endpoint files and inherited `_middleware.php` files. If the fingerprint matches the app-local `cache/routes.php`, the compiled route table is loaded directly. If files are added, changed, or removed, Bluewater rebuilds the cache atomically.

Route conflicts fail loudly during discovery. Dynamic parameter names do not hide conflicts: `/users/{id}` and `/users/{name}` are considered the same route shape.

## Middleware

Bluewater supports four effective scopes:

1. global application middleware registered in `Bootstrap::boot()`;
2. directory middleware using `Endpoints/.../_middleware.php`;
3. endpoint-class middleware using repeatable `#[UseMiddleware]`;
4. endpoint-method middleware using repeatable `#[UseMiddleware]`.

The resolved route cache stores the final middleware chain.

## Dependency injection

The Bluewater container implements PSR-11 and supports:

- explicitly registered instances;
- interface-to-class bindings;
- callable factories;
- zero-config constructor autowiring for concrete services.

Endpoint parameters are resolved by convention:

- route parameters first;
- query parameters second;
- Bluewater `Request` injection;
- application DTO hydration from JSON request bodies;
- registered/autowireable services from the container.

## Validation

DTO validation is first-class. The initial attributes include `Required`, `Email`, and `MinLength`. Validation errors return HTTP 422 with field-level errors.

```php
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

## Content negotiation

Endpoint methods may return Bluewater `Response`, arrays, DTOs, scalars, or collections. `Accept` controls serialization.

Built in:

- `application/json`
- `application/xml`
- `text/csv`
- text media types

Additional media types can be registered with `SerializerRegistry`.

## Authentication

Authentication is middleware-driven rather than built into endpoint/controller logic. Initial providers include:

- API keys;
- HS256 JWT;
- OAuth bearer tokens through an application-provided token introspector.

See `examples/host/app/app_1` for registration and protected endpoint examples.

## Database

Bluewater core intentionally does not include an ORM. It exposes a small `Database` contract and PDO implementation supporting prepared queries and transactions. Applications may replace it or install an ORM integration as an external extension.

## OpenAPI

`OpenApiGenerator` derives OpenAPI 3.1 metadata from discovered routes, typed parameters, DTOs, return types, and optional `#[Summary]` metadata. The example app exposes this at `GET /openapi`.

There is no separately maintained OpenAPI route manifest.

## Configuration

Bluewater ships protected INI/PHP defaults under `config/`:

```text
Bluewater.ini.php
BW.db.ini.php
BW.logging.ini.php
...
```

An app owns overrides under:

```text
app/app_1/config/
```

using corresponding `App.*` files. Core loads first, app overrides second. Missing app files simply inherit defaults.

`BW_VER` is locked and cannot be overridden. Override types must match the core value type. Legacy placeholders such as `{APP_ROOT}`, `{CACHE_ROOT}`, `{BLUEWATER}`, `{SITE_ROOT}`, and `{DS}` are resolved without making framework internals depend on global PHP constants.

The merged result is cached atomically as app-local PHP in `cache/config.php` for OPcache-friendly loading.

## Application bootstrap

Every application must define `<AppNamespace>\Bootstrap` implementing `ApplicationBootstrap`.

```php
final class Bootstrap implements ApplicationBootstrap
{
    public function register(Application $app): void
    {
        // service bindings and explicit extensions
    }

    public function boot(Application $app): void
    {
        // global middleware and final app initialization
    }
}
```

Bluewater intentionally keeps the lifecycle to these two application hooks. Request-specific behavior belongs in middleware.

## Extensions

Extensions implement `Bluewater\Extension\Extension` and are registered explicitly:

```php
$app->extensions()->add(MyExtension::class);
```

There is no Composer auto-discovery or hidden package boot behavior.

## FPM front controller

```php
use Bluewater\Host;
use Bluewater\Runtime\FpmAdapter;

require '/path/to/host/vendor/autoload.php';

Host::fromEnvironment()
    ->application((string) getenv('BLUEWATER_APP'))
    ->run(new FpmAdapter());
```

The example includes an Apache `.htaccess` configuration. In production, application identity and environment should normally be configured at the virtual-host/FPM-pool boundary.

## Example

`examples/host/app/app_1` demonstrates:

- dynamic application namespace loading;
- required bootstrap;
- app-level config overrides;
- SQLite/PDO integration;
- file-based health and user endpoints;
- DTO validation;
- global, directory, class, and method middleware;
- API key, JWT, and OAuth authentication;
- explicit application extension;
- generated OpenAPI.

## Development

```bash
composer install
composer check
```

CI runs the suite on PHP 8.3 and PHP 8.4.

## Public API boundary

Application code should depend only on documented `Bluewater\...` public namespaces. Future implementation-only code will live under `Bluewater\Internal\...` and is not part of the semantic-versioning compatibility contract.
