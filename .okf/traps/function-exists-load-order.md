---
type: Trap
title: "`function_exists` load order"
description: "Helpers skip definition when the name exists; first autoloaded definition wins."
resource: src/Helpers/mpsse.php
tags: [trap, autoload, helpers, mpsse]
generated: { by: "okf-documentation-generator/cursor", at: "2026-08-10T21:28:00Z" }
status: draft
sources:
  - id: readme
    resource: README.md
    title: README note on function_exists guards
  - id: helpers
    resource: src/Helpers/mpsse.php
    title: function_exists guard pattern
  - id: composer
    resource: composer.json
    title: Autoload files entry
---

# Symptom

An `mpsse_*` call behaves unlike this package’s wrap — or a redefinition / “missing helper” confusion appears when multiple packages define the same global name.

# Cause

Every helper is defined only when the name is free:[^helpers][^readme]

```php
if (! function_exists('mpsse_open')) {
    function mpsse_open(...): ?MPSSEContext
    {
        // ...
    }
}
```

Under the guard, **whichever package’s autoload files run first keeps the definition**. Composer `autoload.files` order depends on require graph / install order.[^composer]

# Mitigation

- Treat this package as the **canonical** `mpsse_*` helper source when present.[^readme]
- Avoid defining overlapping global `mpsse_*` helpers in application code or peer packages.
- Prefer calling `Microscrap\Bindings\MPSSE\MPSSE::*` directly when you must avoid global-name collisions entirely (same underlying API; fuller surface than the eight helpers).[^helpers]

# Related

* [Helpers → MPSSE → FTDI](../architecture/helpers-mpsse-ftdi.md)
* [1:1 libmpsse wrap](../conventions/one-to-one-libmpsse-wrap.md)

[^readme]: README note on function_exists guards
[^helpers]: function_exists guard pattern
[^composer]: Autoload files entry
