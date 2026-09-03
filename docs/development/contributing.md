<!-- locale-guard:language-bar:start -->
<!-- locale-guard:language-bar:end -->

# Contribution Workflow – Bluewater Framework

📄 **File:** `docs/development/contributing.md`
📅 **Status:** Active
🏷️ **Tags:** technical, development, contributing, ci
🔖 **Version:** 8.0.0
📅 **Date:** 2026-09-03
🌍 **Scope:** Branching, validation, review, and compatibility expectations
🤝 **Contributors:** Bluewater framework maintainers
👨‍💻 **Author:** Bluewater Framework Team

---

> ### 🪶 **Bluewater Principle**
> *A change is complete only when implementation, tests, examples, and documentation agree.*

---

## 📌 Purpose

This guide defines the required workflow for contributing to Bluewater core.

## Contribution workflow

Recommended workflow:

```text
main
 ↓
feature/fix branch
 ↓
edit src/
 ↓
edit config/ when defaults change
 ↓
add unit tests
 ↓
update app_1 integration coverage when needed
 ↓
run targeted tests
 ↓
composer check
 ↓
push
 ↓
pull request
 ↓
PHP 8.3 + PHP 8.4 CI
 ↓
review
 ↓
merge
```

Do not merge a framework change merely because it is syntactically valid. Core changes must preserve Bluewater's primary goals: small surface area, predictable behavior, automatic file-based endpoint discovery, high request-path efficiency, explicit extension points, and straightforward debugging.

## 📚 Related Documents

- [Core development](index.md)
- [Framework testing](../testing/framework.md)
- [Technical index](../README.md)

---

This repository and its technical documentation are licensed under the [OSL-3.0 License](../../LICENSE).

---

*Last updated: 2026-09-03*
