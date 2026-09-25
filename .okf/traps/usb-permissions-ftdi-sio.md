---
type: Trap
title: "USB permissions / ftdi_sio"
description: "Linux ftdi_sio / missing udev rules commonly block MPSSE open even when ext-ftdi is loaded (same family as microscrap/ftdi)."
resource: src/MPSSE.php
tags: [trap, usb, linux, ftdi, mpsse, permissions]
generated: { by: "okf-documentation-generator/cursor", at: "2026-08-10T21:28:00Z" }
status: draft
sources:
  - id: readme
    resource: README.md
    title: Requirements and open usage
  - id: mpsse
    resource: src/MPSSE.php
    title: Open path uses FTDI / ftdi_* under the hood
  - id: helpers
    resource: src/Helpers/mpsse.php
    title: mpsse_open failure path and error string
  - id: composer
    resource: composer.json
    title: Requires ext-ftdi and microscrap/ftdi
---

# Symptom

`mpsse_open` / `MPSSE::open*` fails even though `php -m | grep ftdi` shows the extension and the device is plugged in. `MPSSE::errorString` (or the optional `&$error` from `mpsse_open`) may report access, busy, or driver-related failures.[^readme][^helpers]

# Cause

On Linux, the in-kernel `ftdi_sio` serial driver often claims FTDI devices first. Without a detach / blacklist / udev rule, userspace libftdi (via **ext-ftdi**) cannot open the device. Separately, insufficient USB permissions (no udev `MODE`/`GROUP` for the VID/PID) produce permission denials.[^readme]

This package only wraps MPSSE on top of FTDI — it does not install udev rules or unload kernel modules.[^composer][^mpsse]

# Mitigation

- Confirm **ext-ftdi**, host libftdi, and `microscrap/ftdi` are installed (see README OS packages).[^readme]
- On Linux: ensure the device is available to libusb/libftdi (unbind `ftdi_sio` / appropriate udev rules for `0x0403` products as needed).
- Check `$ctx->open` and `MPSSE::errorString($ctx)` after open attempts; `mpsse_open` returns `null` when open fails.[^helpers]
- Higher-level adapters may live in `scrapyard-io/framework` — still expect host USB setup to be correct.

# Related

* [Helpers → MPSSE → FTDI](../architecture/helpers-mpsse-ftdi.md)
* [Package (0.9)](../orientation/package.md)

[^readme]: Requirements and open usage
[^mpsse]: Open path uses FTDI / ftdi_* under the hood
[^helpers]: mpsse_open failure path and error string
[^composer]: Requires ext-ftdi and microscrap/ftdi
