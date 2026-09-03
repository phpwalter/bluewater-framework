<!-- locale-guard:language-bar:start -->
<!-- locale-guard:language-bar:end -->

# Framework Testing – Bluewater Framework

📄 **File:** `docs/testing/framework.md`
📅 **Status:** Active
🏷️ **Tags:** technical, testing, phpunit, phpstan, phpcs
🔖 **Version:** 8.0.0
📅 **Date:** 2026-09-03
🌍 **Scope:** Unit, integration, static, external-host, and pull-request validation
🤝 **Contributors:** Bluewater framework maintainers
👨‍💻 **Author:** Bluewater Framework Team

---

> ### 🪶 **Bluewater Principle**
> *Core changes pass every quality gate before review.*

---

## 📌 Purpose

This guide defines the complete validation contract for Bluewater framework changes.

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

## 📚 Related Documents

- [Testing](index.md)
- [Contribution workflow](../development/contributing.md)
- [Core development](../development/index.md)

---

This repository and its technical documentation are licensed under the [OSL-3.0 License](../../LICENSE).

---

*Last updated: 2026-09-03*
