<!-- locale-guard:language-bar:start -->
<!-- locale-guard:language-bar:end -->

# Application Usage – Bluewater Framework

📄 **File:** `docs/usage/index.md`
📅 **Status:** Active
🏷️ **Tags:** technical, usage, applications
🔖 **Version:** 8.0.0
📅 **Date:** 2026-09-03
🌍 **Scope:** Application bootstrap and normal development workflow
🤝 **Contributors:** Bluewater framework maintainers
👨‍💻 **Author:** Bluewater Framework Team

---

> ### 🪶 **Bluewater Principle**
> *Application behavior uses public contracts and remains outside vendor code.*

---

## 📌 Purpose

This guide establishes the application lifecycle and working rules used throughout the task-specific usage guides.

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

## Task guides

Use the focused documents in this directory for routing, dependency injection, validation, responses, middleware, authentication, database access, extensions, OpenAPI, and logging.

## 📚 Related Documents

- [Routing](routing.md)
- [Dependency injection](dependency-injection.md)
- [Technical index](../README.md)

---

This repository and its technical documentation are licensed under the [OSL-3.0 License](../../LICENSE).

---

*Last updated: 2026-09-03*
