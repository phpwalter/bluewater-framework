<!-- locale-guard:language-bar:start -->
<!-- locale-guard:language-bar:end -->

# Application Authentication – Bluewater Framework

📄 **File:** `docs/usage/authentication.md`
📅 **Status:** Active
🏷️ **Tags:** technical, usage, authentication, security
🔖 **Version:** 8.0.0
📅 **Date:** 2026-09-03
🌍 **Scope:** API-key, JWT, and OAuth bearer provider configuration
🤝 **Contributors:** Bluewater framework maintainers
👨‍💻 **Author:** Bluewater Framework Team

---

> ### 🪶 **Bluewater Principle**
> *Authentication selects one provider and fails closed without granting authorization.*

---

## 📌 Purpose

This guide explains provider registration, protected endpoints, identity access, and security boundaries.

## Authentication

Authentication is middleware-driven. Endpoint business logic should not manually parse JWTs or API keys unless there is a very specific reason.

Bluewater provides initial support for:

- API keys;
- HS256 JWTs;
- OAuth bearer tokens using an application-provided introspector.

Register providers in `Bootstrap::register()` and attach the appropriate middleware globally, by directory, class, or method.

This keeps authentication policy separate from endpoint business logic.

## 📚 Related Documents

- [Middleware](middleware.md)
- [Application configuration](../setup/configuration.md)
- [Application testing](../testing/applications.md)

---

This repository and its technical documentation are licensed under the [OSL-3.0 License](../../LICENSE).

---

*Last updated: 2026-09-03*
