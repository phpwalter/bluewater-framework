<!-- locale-guard:language-bar:start -->
<!-- locale-guard:language-bar:end -->

# Responses and Content Negotiation – Bluewater Framework

📄 **File:** `docs/usage/responses.md`
📅 **Status:** Active
🏷️ **Tags:** technical, usage, responses, serialization
🔖 **Version:** 8.0.0
📅 **Date:** 2026-09-03
🌍 **Scope:** Response values, media types, and custom serializers
🤝 **Contributors:** Bluewater framework maintainers
👨‍💻 **Author:** Bluewater Framework Team

---

> ### 🪶 **Bluewater Principle**
> *Representation selection is explicit and keeps transport output immutable.*

---

## 📌 Purpose

This guide explains supported endpoint return values and negotiated response serialization.

## Returning responses

Endpoint handlers may return:

- `Bluewater\Http\Response`;
- arrays;
- DTOs/objects;
- scalars;
- collections.

Bluewater converts normal return values through the serializer registry.

Example:

```php
public function get(): array
{
    return ['status' => 'ok'];
}
```

For explicit responses:

```php
use Bluewater\Http\Response;

return Response::json(['created' => true], 201);
```

## Content negotiation

Bluewater uses request headers, especially `Accept`, to select a response serializer.

Built-in formats include:

```text
application/json
application/xml
text/csv
text/*
```

Application-specific serializers can be registered through Bluewater's serializer extension surface.

JSON should generally remain the default API format.

## 📚 Related Documents

- [Routing](routing.md)
- [OpenAPI](openapi.md)
- [Application testing](../testing/applications.md)

---

This repository and its technical documentation are licensed under the [OSL-3.0 License](../../LICENSE).

---

*Last updated: 2026-09-03*
