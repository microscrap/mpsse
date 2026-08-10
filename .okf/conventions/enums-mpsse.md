---
type: Convention
title: Enums for MPSSE
description: "MPSSE mode/pin/command/device enums are int- or string-backed with FULLY UPPERCASE cases; no class constants."
resource: src/Enums/
tags: [convention, enums, mpsse]
generated: { by: "okf-documentation-generator/cursor", at: "2026-08-10T21:28:00Z" }
status: draft
sources:
  - id: readme
    resource: README.md
    title: Enum namespaces and UPPERCASE note
  - id: mode
    resource: src/Enums/MPSSEMode.php
    title: MPSSEMode enum
  - id: iface
    resource: src/Enums/MPSSEInterface.php
    title: MPSSEInterface enum
  - id: endian
    resource: src/Enums/MPSSEEndianness.php
    title: MPSSEEndianness enum
  - id: clock
    resource: src/Enums/MPSSEClockRate.php
    title: MPSSEClockRate enum
  - id: command
    resource: src/Enums/MPSSECommand.php
    title: MPSSECommand enum
  - id: ack
    resource: src/Enums/MPSSEAck.php
    title: MPSSEAck enum
  - id: pin
    resource: src/Enums/MPSSEPin.php
    title: MPSSEPin enum
  - id: gpio-pin
    resource: src/Enums/MPSSEGpioPin.php
    title: MPSSEGpioPin enum
  - id: device
    resource: src/Enums/MpsseSupportedDevice.php
    title: MpsseSupportedDevice enum
  - id: agents
    resource: AGENTS.md
    title: Enum case naming rule
---

# Why enums live here

Typed tokens for MPSSE modes, interfaces, endianness, clock rates, FTDI MPSSE command bytes, ACK/NACK, pin bitmasks, GPIO pin indexes, and supported device shortcuts. Keep new constants as enum cases — never class-level `const`.[^readme][^agents]

# Rules

- Use **int-backed** or **string-backed** enums under `Microscrap\Bindings\MPSSE\Enums\`.[^mode][^device]
- Case names are **FULLY UPPERCASE** (e.g. `MPSSEMode::SPI0`, `MPSSEClockRate::ONE_MHZ`).[^agents][^readme]
- No class-level constants in `src/`.[^agents]
- Pass `->value` (or accept the enum at typed call sites) into APIs that take raw `int` / `string`.[^readme]

# Enum inventory (0.7.0)

| Enum | Backing | Cases (summary) |
|------|---------|-----------------|
| `MPSSEMode` | int | `SPI0`–`SPI3`, `I2C`, `GPIO`, `BITBANG`[^mode] |
| `MPSSEInterface` | int | `IFACE_ANY`, `IFACE_A`–`IFACE_D`[^iface] |
| `MPSSEEndianness` | int | `MSB`, `LSB`[^endian] |
| `MPSSEClockRate` | int | `ONE_HUNDRED_KHZ` … `SIXTY_MHZ`[^clock] |
| `MPSSECommand` | int | FTDI MPSSE opcodes (`SET_BITS_LOW`, `TCK_DIVISOR`, …)[^command] |
| `MPSSEAck` | int | `ACK`, `NACK`[^ack] |
| `MPSSEPin` | int | Bitmask pins `SK`, `DO`, `DI`, `CS`, `GPIO0`–`GPIO3`[^pin] |
| `MPSSEGpioPin` | int | Index pins `GPIOL0`–`GPIOH7`[^gpio-pin] |
| `MpsseSupportedDevice` | string | `FT232H`, `FT2232H_A`/`_B`, `FT4232H_A`–`_D`[^device] |

# Related

* [1:1 libmpsse wrap](one-to-one-libmpsse-wrap.md)
* [Package (0.7)](../orientation/package.md)
* [Mode mismatch workflows](../traps/mode-mismatch-workflows.md)

[^readme]: Enum namespaces and UPPERCASE note
[^mode]: MPSSEMode enum
[^iface]: MPSSEInterface enum
[^endian]: MPSSEEndianness enum
[^clock]: MPSSEClockRate enum
[^command]: MPSSECommand enum
[^ack]: MPSSEAck enum
[^pin]: MPSSEPin enum
[^gpio-pin]: MPSSEGpioPin enum
[^device]: MpsseSupportedDevice enum
[^agents]: Enum case naming rule
