## 2026-10-03
* **Update**: 0.10.0 port over ext-ftdi 0.10.0, a C rewrite that absorbs `microscrap/ftdi`. `Ftdi\FTDI` is gone; `MPSSE` calls the extension's global `ftdi_*` functions, and `Ftdi\FtdiVendorId` / `Ftdi\FtdiProductId` replace the `Microscrap\Bindings\FTDI\Enums` pair. `ftdi_new()` returns `?FTDIContext`; on `null`, `open*` returns an unopened `MPSSEContext`. Requires only `php` and `ext-ftdi` `^0.10.0`; docs URLs now `0.10.x`. Pest suite (30 tests, FT232H hardware feature tests included) passes on macOS NTS and ZTS; a LIS3DH answers WHO_AM_I 0x33 at 0x18 over FT232H MPSSE I2C.

## 2026-09-23
* **Update**: 0.9.0 line over ext-ftdi 0.9.0 and microscrap/ftdi 0.9.0. Manifest, README, AGENTS and concept versions only; no code change. `MPSSE::version()` still reports libmpsse 1.3 — that is the ported C library's version, not this package's.

## 2026-09-14
* **Update**: relabeled 0.7.0 → 0.8.0 with `ext-posi` / `ext-ftdi` 0.8.0. No code change.

# Log

## 2026-08-10

* **Creation**: Initial OKF v0.2 bundle for `microscrap/mpsse` 0.7.0 from package sources + `okf/SPEC.md` (GoogleCloudPlatform/knowledge-catalog).
* **Creation**: [Package (0.7)](/orientation/package.md), [Ecosystem docs](/orientation/ecosystem-docs.md).
* **Creation**: [Helpers → MPSSE → FTDI](/architecture/helpers-mpsse-ftdi.md) — helpers → package `MPSSE` wrapper → `Ftdi\FTDI` / `ftdi_*` (unlike `microscrap/ftdi`, which has no intermediate wrapper).
* **Creation**: Conventions — [1:1 libmpsse wrap](/conventions/one-to-one-libmpsse-wrap.md), [Enums for MPSSE](/conventions/enums-mpsse.md).
* **Creation**: Traps — [USB permissions / ftdi_sio](/traps/usb-permissions-ftdi-sio.md), [`function_exists` load order](/traps/function-exists-load-order.md), [Mode mismatch workflows](/traps/mode-mismatch-workflows.md).
* **Creation**: Subdirectory indexes under `orientation/`, `architecture/`, `conventions/`, `traps/`; root [index.md](/index.md).
* **Creation**: Root `AGENTS.md` — read `.okf/index.md` first + quick concept map.
* Pattern mirrored from `microscrap/ftdi` OKF layout; adapted for MPSSE bindings with an intermediate `MPSSE` class and package-owned `MPSSEContext`.
* All new concepts left `status: draft` pending Angel human verification.
