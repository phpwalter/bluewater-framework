<!-- locale-guard:language-bar:start -->
<!-- locale-guard:language-bar:end -->

# Application Logging – Bluewater Framework

📄 **File:** `docs/usage/logging.md`
📅 **Status:** Active
🏷️ **Tags:** technical, usage, logging, psr-3
🔖 **Version:** 8.0.0
📅 **Date:** 2026-09-03
🌍 **Scope:** PSR-3 logger selection and request logging
🤝 **Contributors:** Bluewater framework maintainers
👨‍💻 **Author:** Bluewater Framework Team

---

> ### 🪶 **Bluewater Principle**
> *Operational records never include credentials or uncontrolled sensitive context.*

---

## 📌 Purpose

This guide explains the default logger, feature control, and request-logging middleware.

## Logging

Use the logger supplied through Bluewater's PSR-3-compatible logging layer instead of `echo`, `print_r`, or ad hoc files.

Application logs belong under the app's runtime directory:

```text
app/app_1/logs/
```

Do not write application logs into `vendor/bluewater/framework`.

## 📚 Related Documents

- [Middleware](middleware.md)
- [Application configuration](../setup/configuration.md)
- [Deployment](../deployment/index.md)

---

This repository and its technical documentation are licensed under the [OSL-3.0 License](../../LICENSE).

---

*Last updated: 2026-09-03*
