---
type: Trap
title: Mode mismatch workflows
description: "SPI-only / bitbang-only ops fail intentionally when the session mode does not match; follow start → transfer → stop for SPI/I2C sessions."
resource: src/MPSSE.php
tags: [trap, mpsse, spi, i2c, workflow]
generated: { by: "okf-documentation-generator/cursor", at: "2026-08-10T21:28:00Z" }
status: draft
sources:
  - id: readme
    resource: README.md
    title: Usage order and mode-aware API notes
  - id: mpsse
    resource: src/MPSSE.php
    title: Mode checks on transfer / pin / session methods
  - id: tests
    resource: tests/Feature/MpsseFt232hWorkflowFeatureTest.php
    title: Wrong-workflow assertions for mode mismatches
---

# Symptom

Calls return failure codes / `null` unexpectedly: e.g. `MPSSE::transfer` / `fastTransfer` return `null` on an I2C-opened context, or bitbang pin direction/write helpers return `-1` on an SPI session. Closed / invalid contexts likewise reject `start` / `write` / `read` / `transfer`.[^mpsse][^tests]

# Cause

`MPSSE` configures and gates operations by `MPSSEContext::$mode`. Full-duplex transfer helpers are SPI-oriented; some GPIO/bitbang helpers refuse non-matching modes. That is intentional protocol safety, not a broken open.[^mpsse][^readme]

Feature tests assert wrong-workflow behavior (SPI transfer under I2C mode; bitbang controls under SPI mode; ops on a closed context).[^tests]

# Mitigation

1. Open with the correct `MPSSEMode` for the bus you intend (`SPI0`–`SPI3`, `I2C`, `GPIO`, `BITBANG`).[^readme]
2. For SPI/I2C data sessions, follow **start → transfer/read/write → stop** (then `close`).[^readme][^tests]
3. Use I2C ACK helpers (`getAck` / `setAck` / `sendAcks` / `sendNacks`) only in I2C-oriented flows.[^readme]
4. Prefer typed enums from [Enums for MPSSE](../conventions/enums-mpsse.md) so mode choices stay explicit.
5. Check return values / `is_null(...)` results; use `MPSSE::errorString($ctx)` when diagnosing open/session failures.[^readme]

# Related

* [Helpers → MPSSE → FTDI](../architecture/helpers-mpsse-ftdi.md)
* [1:1 libmpsse wrap](../conventions/one-to-one-libmpsse-wrap.md)
* [Enums for MPSSE](../conventions/enums-mpsse.md)

[^readme]: Usage order and mode-aware API notes
[^mpsse]: Mode checks on transfer / pin / session methods
[^tests]: Wrong-workflow assertions for mode mismatches
