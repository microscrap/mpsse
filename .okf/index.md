---
okf_version: "0.2"
---

# microscrap/mpsse Knowledge Bundle

Package knowledge for `microscrap/mpsse` (bindings-only MPSSE SPI/I2C/GPIO helpers over `microscrap/ftdi` / **ext-ftdi**, v0.9.0).
Read this index first; open only the concepts needed for the task.

**Trust rule:** Prefer `status: stable`. Treat `deprecated` as historical only. New agent-written concepts stay `status: draft` until a human verifies them.
**Placement:** This bundle lives at the **package root** only — never under `src/`.
**Links:** Concept cross-links use paths relative to each file.
**Scope:** Document the bindings-only MPSSE helpers package. Do **not** invent ServiceProviders, chip drivers, gpio-framework internals, or Fabricate remaps here — those belong in peers (`scrapyard-io/framework`, tubes).
**Dist note:** `.okf/` and root `AGENTS.md` are `export-ignore` in `.gitattributes` so Composer dist packages do not ship this bundle.

# Orientation

* [Package (0.9)](orientation/package.md) - Composer identity, namespace, helpers over MPSSE / FTDI.
* [Ecosystem docs](orientation/ecosystem-docs.md) - Published overview and docs site entrypoint.

# Architecture

* [Helpers → MPSSE → FTDI](architecture/helpers-mpsse-ftdi.md) - Call stack: helpers → `MPSSE` static class → FTDI extension / `ftdi_*`.

# Conventions

* [1:1 libmpsse wrap](conventions/one-to-one-libmpsse-wrap.md) - Keep wrap aligned with libmpsse / existing MPSSE surface; helpers thin over `MPSSE`.
* [Enums for MPSSE](conventions/enums-mpsse.md) - Backed enums; FULLY UPPERCASE cases; no class constants.

# Traps

* [USB permissions / ftdi_sio](traps/usb-permissions-ftdi-sio.md) - Kernel driver and udev conflicts block opens.
* [`function_exists` load order](traps/function-exists-load-order.md) - Autoload order; first definition wins.
* [Mode mismatch workflows](traps/mode-mismatch-workflows.md) - SPI ops in I2C session etc. fail intentionally; follow start→transfer→stop.

# Log

* [Directory update log](log.md)
