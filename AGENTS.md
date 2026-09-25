# AGENTS.md — microscrap/mpsse

**Always read `.okf/index.md` first** before changing this package. Open only the concepts needed for the task; prefer `status: stable` when present. When you learn a durable package fact, update `.okf/` and append `.okf/log.md`.

## Role

Bindings-only Composer package: pure-PHP MPSSE SPI/I2C/GPIO helpers (libmpsse-style) over **ext-ftdi** + `microscrap/ftdi` (`^0.9.0`). Global helpers + `MPSSE` static wrapper + package-owned `MPSSEContext` + enums. No ServiceProvider, no Chassis/Core/Fabricate wiring.

## Rules

* Helpers call `Microscrap\Bindings\MPSSE\MPSSE` static methods — there **is** an intermediate package wrapper (unlike `microscrap/ftdi`, which calls `Ftdi\FTDI` directly).
* Keep the wrap aligned with libmpsse / the existing `MPSSE` surface; do not invent APIs. Helpers stay thin over `MPSSE`.
* Context type is package-owned `MPSSEContext` — do not invent parallel DataObjects beyond this.
* Mode / pin / command / device tokens live in `src/Enums/*` as int- or string-backed enums with **FULLY UPPERCASE** cases.
* Prefer `is_null($var)` over `$var === null`.
* No class-level constants; no ServiceProvider / Chassis discovery in this package.
* Do not invent a CoverageDrift suite unless explicitly requested.
* Suggested peer only: `scrapyard-io/framework` — do not pull chip-driver / gpio-framework internals into this package.

## Quick OKF map

| Need | Concept |
|------|---------|
| Identity / scope | `.okf/orientation/package.md` |
| Docs site | `.okf/orientation/ecosystem-docs.md` |
| Call stack | `.okf/architecture/helpers-mpsse-ftdi.md` |
| Wrap rules | `.okf/conventions/one-to-one-libmpsse-wrap.md` |
| Enums | `.okf/conventions/enums-mpsse.md` |
| USB / ftdi_sio | `.okf/traps/usb-permissions-ftdi-sio.md` |
| Helper clash | `.okf/traps/function-exists-load-order.md` |
| Mode mismatch | `.okf/traps/mode-mismatch-workflows.md` |
