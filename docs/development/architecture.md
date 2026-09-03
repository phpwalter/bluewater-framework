<!-- locale-guard:language-bar:start -->
<!-- locale-guard:language-bar:end -->

# Core Architecture Development – Bluewater Framework

📄 **File:** `docs/development/architecture.md`
📅 **Status:** Active
🏷️ **Tags:** technical, development, architecture, public-api
🔖 **Version:** 8.0.0
📅 **Date:** 2026-09-03
🌍 **Scope:** Namespace rules, extension boundaries, and subsystem changes
🤝 **Contributors:** Bluewater framework maintainers
👨‍💻 **Author:** Bluewater Framework Team

---

> ### 🪶 **Bluewater Principle**
> *Public contracts remain small, explicit, and testable.*

---

## 📌 Purpose

This guide defines where new framework behavior belongs and how public compatibility is protected.

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

## 📚 Related Documents

- [Core development](index.md)
- [Public API reference](../references/public-api.md)
- [Contribution workflow](contributing.md)

---

This repository and its technical documentation are licensed under the [OSL-3.0 License](../../LICENSE).

---

*Last updated: 2026-09-03*
