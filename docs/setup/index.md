<!-- locale-guard:language-bar:start -->
<!-- locale-guard:language-bar:end -->

# Setup – Bluewater Framework

📄 **File:** `docs/setup/index.md`
📅 **Status:** Active
🏷️ **Tags:** technical, setup, requirements
🔖 **Version:** 8.0.0
📅 **Date:** 2026-09-03
🌍 **Scope:** Requirements and installation
🤝 **Contributors:** Bluewater framework maintainers
👨‍💻 **Author:** Bluewater Framework Team

---

> ### 🪶 **Bluewater Principle**
> *A valid runtime and explicit host boundary come before application code.*

---

## 📌 Purpose

This guide defines the supported runtime and installs Bluewater into a host project.

## Requirements

- PHP 8.3 or newer
- Composer 2
- A supported web/runtime environment such as PHP-FPM behind Apache or Nginx

## Installing Bluewater in a host project

In the host project:

```bash
composer require bluewater/framework
```

For local framework development, a Composer path repository may be used instead.

The host's Composer installation owns Bluewater and shared third-party packages. Application-specific PHP classes do not need to be added to the host `composer.json` autoload section because Bluewater dynamically registers the active application's namespace at runtime.

## 📚 Related Documents

- [Host layout](host-layout.md)
- [Configuration](configuration.md)
- [Technical index](../README.md)

---

This repository and its technical documentation are licensed under the [OSL-3.0 License](../../LICENSE).

---

*Last updated: 2026-09-03*
