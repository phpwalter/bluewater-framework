<!-- locale-guard:language-bar:start -->
<!-- locale-guard:language-bar:end -->

# Bluewater Core Developer Guide – Bluewater Framework

📄 **File:** `docs/CORE_DEVELOPERS.md`  
📅 **Status:** Active  
🏷️ **Tags:** technical, framework-core, contributing  
🔖 **Version:** 8.0.0  
📅 **Date:** 2026-09-03  
🌍 **Scope:** Maintaining, extending, testing, and releasing Bluewater 8  
🤝 **Contributors:** Bluewater framework maintainers  
👨‍💻 **Author:** Bluewater Framework Team

---

> ### 🪶 **Bluewater Principle**
> *Core changes preserve a small public surface and prove behavior through executable tests.*

---

## 📌 Purpose

This guide defines repository boundaries, design rules, test expectations, compatibility requirements, and contribution workflow for Bluewater framework maintainers.

This guide is for developers who maintain or extend the Bluewater framework itself.

Core development happens in the `bluewater-framework` repository. Application code must not be added to framework internals, and application developers must not modify Bluewater after it is installed under `vendor/bluewater/framework`.

## Development requirements

- PHP 8.3 or newer
- Composer 2
- Git
- PDO SQLite for integration tests
- SimpleXML for XML serialization tests

Clone the repository and install dependencies:

```bash
git clone https://github.com/phpwalter/bluewater-framework.git
cd bluewater-framework
composer install
```

During development, work on a feature branch rather than directly on `main`:

```bash
git switch main
git pull
git switch -c feature/my-change
```

Until the initial Bluewater 8 implementation is merged, use the implementation branch:

```bash
git switch build/bluewater-v8
composer install
```

## Repository layout

```text
bluewater-framework/
├── src/                 # Framework source
├── config/              # Bluewater default configuration
├── tests/               # Unit and integration tests
├── examples/
│   └── host/
│       ├── app/
│       │   └── app_1/   # Reference application
│       └── public/
│           └── app_1/   # Reference front controller
├── composer.json
├── phpunit.xml.dist
└── README.md
```

The main ownership rule is simple:

```text
src/ + config/ + tests/ = Bluewater framework
examples/host/app/app_1 = application-level reference implementation
```

Do not implement framework behavior inside `app_1`. `app_1` exists to prove that the framework behaves correctly from an application developer's point of view.

## Namespace and source conventions

Bluewater framework classes use the `Bluewater\` namespace and PSR-4 mapping from `src/`.

Example:

```text
src/Routing/Router.php
```

maps to:

```php
namespace Bluewater\Routing;

final class Router
{
}
```

Current public subsystems include:

```text
Bluewater\Auth
Bluewater\Config
Bluewater\Container
Bluewater\Database
Bluewater\Endpoint
Bluewater\Extension
Bluewater\Http
Bluewater\Logging
Bluewater\Middleware
Bluewater\OpenApi
Bluewater\Routing
Bluewater\Runtime
Bluewater\Serialization
Bluewater\Validation
```

Implementation-only code should increasingly live under:

```text
Bluewater\Internal\...
```

Anything under `Bluewater\Internal` is not part of the semantic-versioning compatibility contract.

## Public API design rules

Bluewater should remain easy to understand from application code.

Prefer extension mechanisms in this order:

1. interfaces;
2. composition and dependency injection;
3. explicit extensions;
4. inheritance only where intentionally designed.

Core implementation classes should normally be `final`. If an application needs to replace a behavior, expose an interface or service binding rather than requiring subclassing of framework internals.

Application developers should not need to understand PSR internals for normal Bluewater development. Bluewater-native APIs should remain the primary surface while PSR interoperability is provided underneath.

Current interoperability includes:

- PSR-11 container;
- PSR-3 logging;
- explicit PSR-7 bridge;
- explicit PSR-15 middleware adapter.

## Adding framework code

Add framework features under the appropriate `src/` subsystem.

For example, a new validation attribute might require:

```text
src/Validation/MaxLength.php
src/Validation/Validator.php
```

and corresponding tests:

```text
tests/Validation/ValidatorTest.php
```

A feature that affects the application-facing development model should also be demonstrated or exercised through `examples/host/app/app_1`.

Examples include changes to:

- routing;
- endpoint invocation;
- configuration;
- middleware;
- dependency injection;
- authentication;
- serialization;
- database integration;
- OpenAPI generation;
- runtime adapters.

## Configuration development

Bluewater-owned defaults live under `config/`.

Examples:

```text
config/Bluewater.ini.php
config/BW.db.ini.php
config/BW.logging.ini.php
config/BW.session.php
```

Application overrides belong in the application, not in the framework package:

```text
app/app_1/config/App.ini.php
app/app_1/config/App.db.ini.php
app/app_1/config/App.logging.ini.php
```

Configuration behavior follows these rules:

- Bluewater defaults load first;
- application config overrides permitted values;
- `BW_VER` is locked;
- override types must match Bluewater's defined type;
- unresolved references fail bootstrap;
- circular references fail bootstrap;
- effective config is compiled into the active application's cache directory;
- Bluewater never writes generated data into its own package directory.

Legacy placeholders such as `{APP_ROOT}`, `{CACHE_ROOT}`, `{BLUEWATER}`, `{SITE_ROOT}`, and `{DS}` are supported as compatibility vocabulary without making framework internals depend on global PHP constants.

When adding new framework configuration, define a safe default in the corresponding `BW.*` file and add tests covering both the default and application override behavior.

## Routing development

Routing is a primary Bluewater differentiator and should be treated as high-risk framework code.

The developer-facing promise is:

```text
create endpoint file
        ↓
no route manifest
        ↓
route becomes available automatically
```

The runtime implementation may compile and cache routing metadata internally, but application developers never maintain that cache or manifest.

Route changes should test at minimum:

- static routes;
- dynamic routes;
- static-route precedence;
- route-shape conflicts;
- handler-name conventions;
- `#[Path]` overrides;
- route parameter/signature validation;
- endpoint file additions;
- endpoint file modifications;
- endpoint file deletions;
- cache invalidation;
- directory middleware inheritance;
- class middleware;
- method middleware.

Normal convention examples:

```php
public function get(): array
```

maps to:

```text
GET /users
```

for `Endpoints/users.php`.

```php
public function getById(int $id): array
```

maps to:

```text
GET /users/{id}
```

Exceptional routes should use `#[Path]` rather than expanding the naming convention indefinitely.

## Dependency injection development

Bluewater uses a hybrid DI model:

- explicitly registered services;
- interface-to-class bindings;
- callable factories;
- constructor autowiring for concrete classes where possible.

Do not introduce hidden service discovery. Replacement implementations must be registered explicitly.

When adding a replaceable framework facility, prefer:

```php
interface Cache
{
    public function get(string $key): mixed;
}
```

with a default implementation:

```php
final class FileCache implements Cache
{
}
```

An application can then replace it explicitly through the container.

## Runtime architecture

The Bluewater application kernel must remain runtime-neutral.

FPM is an adapter, not a foundational assumption.

Runtime-specific responsibilities belong under `Bluewater\Runtime`, such as:

- creating a Bluewater `Request` from runtime input;
- emitting a Bluewater `Response`;
- adapting lifecycle semantics where necessary.

Do not place FPM globals or Apache-specific logic inside routing, DI, validation, serialization, or application services.

## Unit tests

Tests should mirror the framework subsystem where practical.

Examples:

```text
src/Config/ConfigFactory.php
    → tests/Config/ConfigFactoryTest.php

src/Routing/Router.php
    → tests/Routing/RouterTest.php

src/Container/Container.php
    → tests/Container/ContainerTest.php
```

Run the full PHPUnit suite with:

```bash
vendor/bin/phpunit
```

Run one test file while developing:

```bash
vendor/bin/phpunit tests/Routing/RouterTest.php
```

Run a single test by name:

```bash
vendor/bin/phpunit --filter testMethodsDeriveFileBasedRoutesWithoutManifest
```

## Integration testing with `app_1`

`examples/host/app/app_1` is the framework's reference application and integration fixture.

It currently exercises:

- dynamic application namespace loading;
- required `Bootstrap` lifecycle;
- application config overrides;
- SQLite/PDO integration;
- health and user endpoints;
- DTO validation;
- global middleware;
- directory middleware;
- class middleware;
- method middleware;
- API-key authentication;
- JWT authentication;
- OAuth introspection;
- explicit application extensions;
- generated OpenAPI.

A framework change that affects normal application behavior should usually add or update an integration test using this application.

The principle is:

> Unit tests prove the subsystem. `app_1` proves the framework.

## Mandatory validation before a pull request

Run:

```bash
composer check
```

The project also supports:

```bash
composer validate --strict
vendor/bin/phpunit
```

`composer check` is the minimum local acceptance gate before pushing a framework change.

GitHub Actions currently validates Bluewater on:

```text
PHP 8.3
PHP 8.4
```

CI performs Composer validation, dependency installation, syntax checks, and the PHPUnit suite.

## External application testing during framework development

For deeper compatibility testing, use another local Composer project with a path repository.

Example workspace:

```text
development/
├── bluewater-framework/
└── customer-api-host/
```

In the external host's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../bluewater-framework",
            "options": {
                "symlink": true
            }
        }
    ],
    "require": {
        "bluewater/framework": "@dev"
    }
}
```

Then run:

```bash
composer update bluewater/framework
```

The application will use the local framework checkout through Composer, allowing real application testing without copying Bluewater source into the application.

## Contribution workflow

Recommended workflow:

```text
main
 ↓
feature/fix branch
 ↓
edit src/
 ↓
edit config/ when defaults change
 ↓
add unit tests
 ↓
update app_1 integration coverage when needed
 ↓
run targeted tests
 ↓
composer check
 ↓
push
 ↓
pull request
 ↓
PHP 8.3 + PHP 8.4 CI
 ↓
review
 ↓
merge
```

Do not merge a framework change merely because it is syntactically valid. Core changes must preserve Bluewater's primary goals: small surface area, predictable behavior, automatic file-based endpoint discovery, high request-path efficiency, explicit extension points, and straightforward debugging.

## 📚 Related Documents

- [Framework overview](../README.md)
- [Application developer guide](APP_DEVELOPERS.md)
- [License](../LICENSE)

---

This repository and its technical documentation are licensed under the [OSL-3.0 License](../LICENSE).

---

*Last updated: 2026-09-03*
