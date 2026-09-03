<!-- locale-guard:language-bar:start -->
<!-- locale-guard:language-bar:end -->

# Public API Reference – Bluewater Framework

📄 **File:** `docs/references/public-api.md`
📅 **Status:** Active
🏷️ **Tags:** technical, references, api
🔖 **Version:** 8.0.0
📅 **Date:** 2026-09-03
🌍 **Scope:** Supported namespaces and principal extension contracts
🤝 **Contributors:** Bluewater framework maintainers
👨‍💻 **Author:** Bluewater Framework Team

---

> ### 🪶 **Bluewater Principle**
> *Applications depend on documented public contracts, not implementation details.*

---

## 📌 Purpose

This reference identifies the current public subsystem surface of Bluewater v8.

## Public subsystems

| Namespace | Principal types |
|---|---|
| `Bluewater` | `Host`, `Application`, `ApplicationBootstrap`, `ApplicationDefinition` |
| `Bluewater\Auth` | Authentication providers, middleware, manager, and `Identity` |
| `Bluewater\Config` | `Config`, `ConfigFactory`, and `IniConfigParser` |
| `Bluewater\Container` | PSR-11 `Container` and resolution exceptions |
| `Bluewater\Database` | `Database` and `PdoDatabase` |
| `Bluewater\Endpoint` | `Endpoint` and `EndpointDispatcher` |
| `Bluewater\Extension` | `Extension` and `ExtensionManager` |
| `Bluewater\Http` | Immutable `Request`, `Response`, and `PsrBridge` |
| `Bluewater\Middleware` | `Middleware`, `Pipeline`, adapters, attributes, and logging |
| `Bluewater\OpenApi` | `OpenApiGenerator` and `Summary` |
| `Bluewater\Routing` | `Router`, `Route`, `Path`, and `RouteNotFound` |
| `Bluewater\Runtime` | `RuntimeAdapter` and `FpmAdapter` |
| `Bluewater\Serialization` | `SerializerRegistry` |
| `Bluewater\Validation` | Validator, attributes, and `ValidationException` |

Future types under `Bluewater\Internal\...` are not part of the semantic-versioning compatibility contract.

## 📚 Related Documents

- [Core architecture](../development/architecture.md)
- [Usage](../usage/index.md)
- [Technical references](index.md)

---

This repository and its technical documentation are licensed under the [OSL-3.0 License](../../LICENSE).

---

*Last updated: 2026-09-03*
