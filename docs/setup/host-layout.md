<!-- locale-guard:language-bar:start -->
<!-- locale-guard:language-bar:end -->

# Host Layout – Bluewater Framework

📄 **File:** `docs/setup/host-layout.md`
📅 **Status:** Active
🏷️ **Tags:** technical, setup, filesystem, namespace
🔖 **Version:** 8.0.0
📅 **Date:** 2026-09-03
🌍 **Scope:** Shared framework installation and isolated application directories
🤝 **Contributors:** Bluewater framework maintainers
👨‍💻 **Author:** Bluewater Framework Team

---

> ### 🪶 **Bluewater Principle**
> *Shared packages never imply shared application state.*

---

## 📌 Purpose

This guide defines the recommended filesystem layout and namespace boundary for one or more applications.

## Host layout

A Bluewater host can serve multiple isolated applications while sharing one physical Composer installation of the framework and common dependencies.

Recommended layout:

```text
host/
├── app/
│   ├── app_1/
│   │   ├── Bootstrap.php
│   │   ├── config/
│   │   ├── Endpoints/
│   │   ├── DTO/
│   │   ├── Middleware/
│   │   ├── Services/
│   │   ├── Extensions/
│   │   ├── cache/
│   │   └── logs/
│   ├── app_2/
│   └── app_3/
├── public/
│   ├── app_1/index.php
│   ├── app_2/index.php
│   └── app_3/index.php
├── vendor/
│   └── bluewater/framework/
└── composer.json
```

Each application has its own namespace, configuration, endpoints, cache, and logs. Applications do not share endpoints implicitly.

## Required application structure

At minimum, each app needs:

```text
app/app_1/
├── Bootstrap.php
├── config/
├── Endpoints/
├── cache/
└── logs/
```

Bluewater may create `cache/` and `logs/` automatically if they are missing and writable. Missing or invalid required application structure fails loudly at bootstrap.

## Application namespace

Each application has its own namespace.

Example:

```text
app_1
```

might use:

```text
Apps\App1
```

Bluewater dynamically maps that namespace to the active app directory at runtime. Adding a new application does not require `composer dump-autoload` for application classes.

A typical endpoint class resolves like:

```text
Apps\App1\Endpoints\Users
```

from:

```text
app/app_1/Endpoints/users.php
```

## 📚 Related Documents

- [Setup](index.md)
- [Configuration](configuration.md)
- [Deployment](../deployment/index.md)

---

This repository and its technical documentation are licensed under the [OSL-3.0 License](../../LICENSE).

---

*Last updated: 2026-09-03*
