<!-- locale-guard:language-bar:start -->
<!-- locale-guard:language-bar:end -->

# Application Testing – Bluewater Framework

📄 **File:** `docs/testing/applications.md`
📅 **Status:** Active
🏷️ **Tags:** technical, testing, applications
🔖 **Version:** 8.0.0
📅 **Date:** 2026-09-03
🌍 **Scope:** Testing application endpoints and running the reference host
🤝 **Contributors:** Bluewater framework maintainers
👨‍💻 **Author:** Bluewater Framework Team

---

> ### 🪶 **Bluewater Principle**
> *Application tests exercise public Bluewater behavior without modifying the framework package.*

---

## 📌 Purpose

This guide explains application-owned tests and local execution of the bundled example.

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

## 📚 Related Documents

- [Testing](index.md)
- [Usage](../usage/index.md)
- [Deployment](../deployment/index.md)

---

This repository and its technical documentation are licensed under the [OSL-3.0 License](../../LICENSE).

---

*Last updated: 2026-09-03*
