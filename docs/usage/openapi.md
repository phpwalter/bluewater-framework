<!-- locale-guard:language-bar:start -->
<!-- locale-guard:language-bar:end -->

# OpenAPI – Bluewater Framework

📄 **File:** `docs/usage/openapi.md`
📅 **Status:** Active
🏷️ **Tags:** technical, usage, openapi
🔖 **Version:** 8.0.0
📅 **Date:** 2026-09-03
🌍 **Scope:** Generating OpenAPI 3.1 from routes and reflected types
🤝 **Contributors:** Bluewater framework maintainers
👨‍💻 **Author:** Bluewater Framework Team

---

> ### 🪶 **Bluewater Principle**
> *Executable routes remain the source for generated API descriptions.*

---

## 📌 Purpose

This guide explains OpenAPI generator registration, output, metadata, and current limitations.

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

## 📚 Related Documents

- [Routing](routing.md)
- [Validation](validation.md)
- [Responses](responses.md)

---

This repository and its technical documentation are licensed under the [OSL-3.0 License](../../LICENSE).

---

*Last updated: 2026-09-03*
