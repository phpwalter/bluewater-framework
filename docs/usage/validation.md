<!-- locale-guard:language-bar:start -->
<!-- locale-guard:language-bar:end -->

# DTOs and Validation – Bluewater Framework

📄 **File:** `docs/usage/validation.md`
📅 **Status:** Active
🏷️ **Tags:** technical, usage, dto, validation
🔖 **Version:** 8.0.0
📅 **Date:** 2026-09-03
🌍 **Scope:** Request DTO hydration and attribute validation
🤝 **Contributors:** Bluewater framework maintainers
👨‍💻 **Author:** Bluewater Framework Team

---

> ### 🪶 **Bluewater Principle**
> *Malformed input is rejected before application services execute.*

---

## 📌 Purpose

This guide explains DTO construction, built-in validation attributes, and HTTP 422 responses.

## DTOs and validation

Use typed DTOs for request data when practical.

Example:

```php
namespace Apps\App1\DTO;

use Bluewater\Validation\Email;
use Bluewater\Validation\MinLength;
use Bluewater\Validation\Required;

final readonly class CreateUserRequest
{
    public function __construct(
        #[Required, Email]
        public string $email,

        #[Required, MinLength(2)]
        public string $name,
    ) {}
}
```

Then use the DTO directly in the endpoint:

```php
public function post(
    CreateUserRequest $request,
    UserRepository $users,
): UserDto {
    return $users->create($request);
}
```

Bluewater hydrates and validates the DTO automatically.

Validation failures return HTTP 422 with field-level errors.

## 📚 Related Documents

- [Dependency injection](dependency-injection.md)
- [Responses](responses.md)
- [Application testing](../testing/applications.md)

---

This repository and its technical documentation are licensed under the [OSL-3.0 License](../../LICENSE).

---

*Last updated: 2026-09-03*
