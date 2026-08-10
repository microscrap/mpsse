---
type: Architecture
title: "Helpers → MPSSE → FTDI"
description: "Global helpers call Microscrap\\Bindings\\MPSSE\\MPSSE; that wrapper uses Ftdi\\FTDI / ftdi_* — intermediate package class exists (unlike microscrap/ftdi)."
resource: src/Helpers/mpsse.php
tags: [architecture, bindings, mpsse, helpers, ftdi]
generated: { by: "okf-documentation-generator/cursor", at: "2026-08-10T21:28:00Z" }
status: draft
sources:
  - id: helpers
    resource: src/Helpers/mpsse.php
    title: Helper file with function_exists guards
  - id: mpsse
    resource: src/MPSSE.php
    title: MPSSE static wrapper over FTDI
  - id: context
    resource: src/MPSSEContext.php
    title: Package-owned MPSSEContext
  - id: composer
    resource: composer.json
    title: Autoload files list for helpers
  - id: readme
    resource: README.md
    title: Helpers delegate to MPSSE; MPSSE uses FTDI
  - id: agents
    resource: AGENTS.md
    title: Agent wrap rules
---

# Call stack

Unlike `microscrap/ftdi` (helpers → `Ftdi\FTDI` with **no** package wrapper), this package **does** have an intermediate static class:[^readme][^helpers][^mpsse]

```
app / tests
    │
    └─ mpsse_open(...) / mpsse_pin_high(...)   # global helpers (thin)
            └─► Microscrap\Bindings\MPSSE\MPSSE::*
                    └─► Ftdi\FTDI / ftdi_* helpers (ext-ftdi / libftdi1)
```

Rules:[^agents][^readme]

1. Helpers call `MPSSE` static methods only (not `Ftdi\FTDI` directly).
2. Protocol / session logic lives on `MPSSE` — keep helpers thin.
3. Context type is package-owned `MPSSEContext` (may hold `?FTDIContext`); do not invent parallel DataObjects beyond this.[^context]
4. Do not remove the `MPSSE` wrapper to “match ftdi” unless Angel explicitly asks.

# Helper inventory (0.7.0)

Eight globals in `src/Helpers/mpsse.php`:[^helpers]

| Helper | Delegates to |
|--------|----------------|
| `mpsse_open(...)` | `MPSSE::open(...)` (returns `null` when not open) |
| `mpsse_close($ctx)` | `MPSSE::close(...)` |
| `mpsse_check_ftdi_device($device)` | Matches `FtdiProductId` case **name** |
| `mpsse_configure_pin_direction(...)` | `MPSSE::configurePinDirection(...)` |
| `mpsse_pin_high` / `mpsse_pin_low` | `MPSSE::pinHigh` / `pinLow` |
| `mpsse_pin_state` / `mpsse_read_pins` | `MPSSE::pinState` / `readPins` |

The full SPI/I2C session API (`start`, `write`, `read`, `transfer`, ACK helpers, …) is on `MPSSE` only — not duplicated as globals.[^readme][^mpsse]

# Autoload

Composer `autoload.files` registers a single helper module:[^composer]

- `src/Helpers/mpsse.php`

Each function is wrapped in `if (! function_exists(...))` so a prior definition wins.[^helpers]

# Objects and errors

- Session state is `Microscrap\Bindings\MPSSE\MPSSEContext` (libmpsse-style fields; embeds `?Ftdi\FTDIContext`).[^context]
- Prefer `is_null($ctx)` / `is_null($ctx->ftdi)` style checks over `=== null` in agent-authored code.[^agents]
- Use `MPSSE::errorString($ctx)` after failed opens; helpers may populate optional `&$error`.[^helpers][^readme]
- No ServiceProvider-thrown framework exceptions from this package.[^readme]

# Related

* [1:1 libmpsse wrap](../conventions/one-to-one-libmpsse-wrap.md)
* [Enums for MPSSE](../conventions/enums-mpsse.md)
* [`function_exists` load order](../traps/function-exists-load-order.md)
* [Mode mismatch workflows](../traps/mode-mismatch-workflows.md)

[^helpers]: Helper file with function_exists guards
[^mpsse]: MPSSE static wrapper over FTDI
[^context]: Package-owned MPSSEContext
[^composer]: Autoload files list for helpers
[^readme]: Helpers delegate to MPSSE; MPSSE uses FTDI
[^agents]: Agent wrap rules
