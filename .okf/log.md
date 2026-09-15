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
