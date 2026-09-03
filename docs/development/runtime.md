<!-- locale-guard:language-bar:start -->
<!-- locale-guard:language-bar:end -->

# Runtime Development – Bluewater Framework

📄 **File:** `docs/development/runtime.md`
📅 **Status:** Active
🏷️ **Tags:** technical, development, runtime
🔖 **Version:** 8.0.0
📅 **Date:** 2026-09-03
🌍 **Scope:** Implementing and maintaining runtime adapters
🤝 **Contributors:** Bluewater framework maintainers
👨‍💻 **Author:** Bluewater Framework Team

---

> ### 🪶 **Bluewater Principle**
> *The kernel delegates transport I/O through a narrow adapter contract.*

---

## 📌 Purpose

This guide defines the runtime-neutral boundary and requirements for additional adapters.

## Runtime architecture

The Bluewater application kernel must remain runtime-neutral.

FPM is an adapter, not a foundational assumption.

Runtime-specific responsibilities belong under `Bluewater\Runtime`, such as:

- creating a Bluewater `Request` from runtime input;
- emitting a Bluewater `Response`;
- adapting lifecycle semantics where necessary.

Do not place FPM globals or Apache-specific logic inside routing, DI, validation, serialization, or application services.

## 📚 Related Documents

- [Deployment](../deployment/index.md)
- [Core architecture](architecture.md)
- [Framework testing](../testing/framework.md)

---

This repository and its technical documentation are licensed under the [OSL-3.0 License](../../LICENSE).

---

*Last updated: 2026-09-03*
