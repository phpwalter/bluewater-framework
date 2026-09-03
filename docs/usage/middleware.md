<!-- locale-guard:language-bar:start -->
<!-- locale-guard:language-bar:end -->

# Application Middleware – Bluewater Framework

📄 **File:** `docs/usage/middleware.md`
📅 **Status:** Active
🏷️ **Tags:** technical, usage, middleware
🔖 **Version:** 8.0.0
📅 **Date:** 2026-09-03
🌍 **Scope:** Global, directory, class, and method middleware
🤝 **Contributors:** Bluewater framework maintainers
👨‍💻 **Author:** Bluewater Framework Team

---

> ### 🪶 **Bluewater Principle**
> *Request policy executes in a visible and deterministic order.*

---

## 📌 Purpose

This guide explains middleware creation, registration, scope, ordering, and PSR-15 integration.

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

## 📚 Related Documents

- [Authentication](authentication.md)
- [Routing](routing.md)
- [Usage](index.md)

---

This repository and its technical documentation are licensed under the [OSL-3.0 License](../../LICENSE).

---

*Last updated: 2026-09-03*
