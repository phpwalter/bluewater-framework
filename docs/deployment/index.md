<!-- locale-guard:language-bar:start -->
<!-- locale-guard:language-bar:end -->

# PHP-FPM Deployment – Bluewater Framework

📄 **File:** `docs/deployment/index.md`
📅 **Status:** Active
🏷️ **Tags:** technical, deployment, fpm, operations
🔖 **Version:** 8.0.0
📅 **Date:** 2026-09-03
🌍 **Scope:** Production application selection and multi-application isolation
🤝 **Contributors:** Bluewater framework maintainers
👨‍💻 **Author:** Bluewater Framework Team

---

> ### 🪶 **Bluewater Principle**
> *Application identity comes from trusted process configuration, never request input.*

---

## 📌 Purpose

This guide defines the production deployment boundary implemented by Bluewater’s FPM runtime adapter.

## Production deployment guidance

For a multi-application host, prefer one PHP-FPM pool per application.

Example conceptual deployment:

```text
app1.example.com
    → app_1 FPM pool
    → BLUEWATER_APP=app_1

app2.example.com
    → app_2 FPM pool
    → BLUEWATER_APP=app_2
```

Both can use the same physical:

```text
host/vendor/bluewater/framework
```

but maintain independent:

```text
config
cache
logs
endpoints
services
```

Application identity should be supplied by trusted web-server/FPM configuration, not by arbitrary request input.

## Operator-owned controls

Bluewater does not provision web servers, TLS, containers, clusters, secrets, databases, backups, or observability platforms. Operators must configure those controls and ensure each application’s cache and log directories have the least privileges required.

## 📚 Related Documents

- [Host layout](../setup/host-layout.md)
- [Application configuration](../setup/configuration.md)
- [Runtime development](../development/runtime.md)

---

This repository and its technical documentation are licensed under the [OSL-3.0 License](../../LICENSE).

---

*Last updated: 2026-09-03*
