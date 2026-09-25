---
type: Orientation
title: Package (0.9)
description: "microscrap/mpsse 0.9.0 — bindings-only MPSSE SPI/I2C/GPIO helpers; helpers → MPSSE → FTDI; no ServiceProvider."
resource: .
tags: [orientation, mpsse, microscrap, bindings, 0.9]
generated: { by: "cursor-grok-4.6", at: "2026-09-23T23:30:00Z" }
status: draft
sources:
  - id: composer
    resource: composer.json
    title: Package name, version, PHP, require, autoload helpers
  - id: readme
    resource: README.md
    title: Package README
  - id: helpers
    resource: src/Helpers/mpsse.php
    title: Global mpsse_* helpers
  - id: mpsse
    resource: src/MPSSE.php
    title: Package MPSSE static wrapper
  - id: agents
    resource: AGENTS.md
    title: Agent rules for this package
---

# What it is

Composer package `microscrap/mpsse` at **0.9.0** — pure-PHP MPSSE SPI / I²C / GPIO helpers modeled on [libmpsse](https://github.com/devttys0/libmpsse), built on [`microscrap/ftdi`](https://github.com/microscrap/ftdi) and **ext-ftdi**.[^composer][^readme]

| Field | Value |
|-------|-------|
| Name | `microscrap/mpsse` |
| Version | `0.9.0` |
| PHP | `^8.4\|^8.5\|^8.6`[^composer] |
| Namespace | `Microscrap\Bindings\MPSSE\` → `src/`[^composer] |
| Require | `ext-ftdi` `^0.9.0`, `microscrap/ftdi` `^0.9.0`[^composer] |
| Suggest | `scrapyard-io/framework` `^0.9.0`[^composer] |
| Homepage | Ecosystem docs overview (see [Ecosystem docs](ecosystem-docs.md))[^composer] |
| Discovery | **None** — no provider / Chassis registration in this package[^readme] |
| Role | Bindings layer only (helpers + `MPSSE` wrapper + enums + `MPSSEContext`)[^helpers][^mpsse][^readme] |

Autoloads `src/Helpers/mpsse.php` global `mpsse_*` helpers, each guarded with `function_exists`.[^composer][^helpers]

# What it is not

- Not the native **ext-ftdi** extension — that is `php-io-extensions/ftdi`.[^readme]
- Not `microscrap/ftdi` — that package is the thinner FTDI helper layer; this package **depends on** it and adds MPSSE protocol helpers.[^composer]
- Not higher UART/USB/MPSSE adapters — those belong in `scrapyard-io/framework` (suggested peer).[^composer]
- Not a ServiceProvider package — no Chassis/Core/Fabricate/Machine coupling.[^readme][^agents]

# Public surface (summary)

| Layer | Location | Role |
|-------|----------|------|
| Helpers | `src/Helpers/mpsse.php` | Thin globals (`mpsse_open`, pin helpers, …) over `MPSSE` |
| Wrapper | `src/MPSSE.php` | Static API — pure-PHP libmpsse port |
| Context | `src/MPSSEContext.php` | Package-owned session object (holds `?FTDIContext`) |
| Enums | `src/Enums/*` | Mode, interface, endianness, clock, command, ACK, pins, devices |
| FTDI peer | `Ftdi\FTDI` / `ftdi_*` | Used underneath by `MPSSE` |

# Related

| Topic | Concept |
|-------|---------|
| Call stack | [Helpers → MPSSE → FTDI](../architecture/helpers-mpsse-ftdi.md) |
| Wrap rules | [1:1 libmpsse wrap](../conventions/one-to-one-libmpsse-wrap.md) |
| Enums | [Enums for MPSSE](../conventions/enums-mpsse.md) |
| Docs site | [Ecosystem docs](ecosystem-docs.md) |
| Peer | `microscrap/ftdi` 0.9.0 |

[^composer]: Package name, version, PHP, require, autoload helpers
[^readme]: Package README
[^helpers]: Global mpsse_* helpers
[^mpsse]: Package MPSSE static wrapper
[^agents]: Agent rules for this package
