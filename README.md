# microscrap/mpsse — MPSSE helpers for FTDI

> **Docs (production):** [ScrapyardIO · microscrap/mpsse 0.7.x](https://scrapyard-io.projectsaturnstudios.com/ecosystem/microscrap/mpsse/0.7.x/overview)

[![Docs](https://img.shields.io/badge/docs-ScrapyardIO-0ea5e9?logo=readthedocs&logoColor=white)](https://scrapyard-io.projectsaturnstudios.com/ecosystem/microscrap/mpsse/0.7.x/overview)
[![Packagist Version](https://img.shields.io/packagist/v/microscrap/mpsse.svg?label=packagist)](https://packagist.org/packages/microscrap/mpsse)
[![PHP Version Require](https://img.shields.io/packagist/php-v/microscrap/mpsse.svg)](https://packagist.org/packages/microscrap/mpsse)
[![License: MIT](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Requires ext-ftdi](https://img.shields.io/badge/ext--ftdi-%5E0.9.0-777bb4?logo=php&logoColor=white)](https://github.com/php-io-extensions/ftdi)

PHP library that provides MPSSE-oriented SPI / I²C / GPIO operations on top of [`microscrap/ftdi`](https://github.com/microscrap/ftdi) and the [`ext-ftdi`](https://github.com/php-io-extensions/ftdi) extension. Pure-PHP port of [libmpsse](https://github.com/devttys0/libmpsse) patterns via `Microscrap\Bindings\MPSSE\MPSSE`.

This is the **bindings** package — not the native extension. Ecosystem docs: [`0.7.x`](https://scrapyard-io.projectsaturnstudios.com/ecosystem/microscrap/mpsse/0.7.x/overview).

## Highlights

* Global helper functions (`mpsse_open`, `mpsse_close`, pin helpers, …)
* Full static API via `Microscrap\Bindings\MPSSE\MPSSE`
* Typed enums for modes, pins, commands, interfaces, endianness, clock rates, and supported devices (**FULLY UPPERCASE** cases)
* Built on `ext-ftdi` `^0.9.0` + `microscrap/ftdi` `^0.9.0`

## Requirements

* PHP `^8.4|^8.5|^8.6`
* **ext-ftdi** `^0.9.0` — [php-io-extensions/ftdi](https://github.com/php-io-extensions/ftdi)
* **microscrap/ftdi** `^0.9.0`
* Runtime dependency of ext-ftdi:
  * Debian/Ubuntu/Raspberry Pi OS: `libftdi1-2` (dev package for builds: `libftdi1-dev`)
  * macOS: `brew install libftdi`

## Installation

Confirm **ext-ftdi** is loaded:

```bash
php -m | grep ftdi
```

Install package:

```bash
composer require microscrap/mpsse:^0.9.0
```

Composer autoloads `src/Helpers/mpsse.php`, registering global helpers when the name is free (`function_exists` guard).

Suggested peer:

```bash
composer require scrapyard-io/framework:^0.9.0 # higher adapters
```

There is **no** ServiceProvider / Chassis discovery in this package — bindings only.

## Usage

### Helper style

```php
<?php

use Microscrap\Bindings\MPSSE\Enums\MPSSEMode;
use Microscrap\Bindings\MPSSE\Enums\MPSSEEndianness;
use Microscrap\Bindings\MPSSE\Enums\MPSSEInterface;
use Microscrap\Bindings\MPSSE\Enums\MPSSEClockRate;

$ctx = mpsse_open(
    0x0403,
    0x6014,
    MPSSEMode::SPI0,
    MPSSEClockRate::ONE_MHZ->value,
    MPSSEEndianness::MSB,
    MPSSEInterface::IFACE_A
);

if (is_null($ctx)) {
    throw new RuntimeException('Unable to open MPSSE device');
}

mpsse_close($ctx);
```

### Static API style

```php
<?php

use Microscrap\Bindings\MPSSE\MPSSE;
use Microscrap\Bindings\MPSSE\Enums\MPSSEMode;
use Microscrap\Bindings\MPSSE\Enums\MPSSEEndianness;
use Microscrap\Bindings\MPSSE\Enums\MPSSEClockRate;
use Microscrap\Bindings\MPSSE\Enums\MpsseSupportedDevice;

$ctx = MPSSE::openDevice(
    MpsseSupportedDevice::FT232H,
    MPSSEMode::SPI0,
    MPSSEClockRate::ONE_MHZ->value,
    MPSSEEndianness::MSB
);

if (! $ctx->open) {
    throw new RuntimeException(MPSSE::errorString($ctx));
}

MPSSE::start($ctx);
MPSSE::write($ctx, "\x9F");      // SPI flash JEDEC ID command (example)
$id = MPSSE::read($ctx, 3);
MPSSE::stop($ctx);

MPSSE::close($ctx);
```

---

## Global Helper API

Helpers are defined only when the name is free (`function_exists` guard).

### `mpsse_open(...): ?Microscrap\Bindings\MPSSE\MPSSEContext`

Wrapper for `MPSSE::open(...)`. Returns `null` if the underlying context did not open. Optional `string &$error` receives `MPSSE::errorString(...)` on failure.

### `mpsse_close(Microscrap\Bindings\MPSSE\MPSSEContext $context): void`

Wrapper for `MPSSE::close(...)`.

### `mpsse_check_ftdi_device(string $device): bool`

Returns whether `$device` matches a `FtdiProductId` case name.

### `mpsse_configure_pin_direction(MPSSEContext $ctx, int $pin, bool $asOutput): int`

Wrapper for `MPSSE::configurePinDirection(...)`.

### `mpsse_pin_high` / `mpsse_pin_low` / `mpsse_pin_state` / `mpsse_read_pins`

Thin wrappers for the matching `MPSSE::` pin helpers.

---

## Static Method API

All methods below are static methods on `Microscrap\Bindings\MPSSE\MPSSE`.

### Open/close

* `openSupported(MPSSEMode $mode, int $freq, MPSSEEndianness $endianness): ?MPSSEContext`
* `open(int $vid, int $pid, MPSSEMode $mode, int $freq, MPSSEEndianness $endianness, MPSSEInterface $iface, string $description = '', ?string $serial = null): MPSSEContext`
* `openDevice(MpsseSupportedDevice $device, MPSSEMode $mode, int $freq, MPSSEEndianness $endianness, MPSSEInterface $iface = MPSSEInterface::IFACE_A, ?string $serial = null): MPSSEContext`
* `openIndex(int $vid, int $pid, MPSSEMode $mode, int $freq, MPSSEEndianness $endianness, MPSSEInterface $iface, string $description, ?string $serial, int $index): MPSSEContext`
* `close(MPSSEContext $ctx): void`
* `errorString(?MPSSEContext $ctx): string`

### Mode/clock/session controls

* `setMode(MPSSEContext $ctx, MPSSEEndianness $endianness): int`
* `enableBitmode(MPSSEContext $ctx, bool $tf): void`
* `setClock(MPSSEContext $ctx, int $freq): int`
* `getClock(MPSSEContext $ctx): int`
* `getVid(MPSSEContext $ctx): int`
* `getPid(MPSSEContext $ctx): int`
* `getDescription(MPSSEContext $ctx): string`
* `setLoopback(MPSSEContext $ctx, bool $enable): int`
* `setCSIdle(MPSSEContext $ctx, bool $idle): void`
* `disableHardwareChipSelect(MPSSEContext $ctx): int`
* `enableHardwareChipSelect(MPSSEContext $ctx): int`
* `flushAfterRead(MPSSEContext $ctx, bool $tf): void`
* `start(MPSSEContext $ctx): int`
* `stop(MPSSEContext $ctx): int`

### Data transfer

* `write(MPSSEContext $ctx, string $data): int`
* `read(MPSSEContext $ctx, int $size): ?string`
* `writeBits(MPSSEContext $ctx, int $bits, int $size): int`
* `readBits(MPSSEContext $ctx, int $size): int`
* `transfer(MPSSEContext $ctx, string $data): ?string`
* `fastWrite(MPSSEContext $ctx, string $data): int`
* `fastRead(MPSSEContext $ctx, int $size): ?string`
* `fastTransfer(MPSSEContext $ctx, string $wdata): ?string`

### ACK/NACK controls (I2C-oriented)

* `getAck(MPSSEContext $ctx): int`
* `setAck(MPSSEContext $ctx, int $ack): void`
* `sendAcks(MPSSEContext $ctx): void`
* `sendNacks(MPSSEContext $ctx): void`

### GPIO/bitbang helpers

* `pinHigh(MPSSEContext $ctx, int $pin): int`
* `pinLow(MPSSEContext $ctx, int $pin): int`
* `setDirection(MPSSEContext $ctx, int $direction): int`
* `writePins(MPSSEContext $ctx, int $data): int`
* `readPins(MPSSEContext $ctx): int`
* `pinState(MPSSEContext $ctx, int $pin, int $state): int`
* `configurePinDirection(MPSSEContext $ctx, int $pin, bool $asOutput): int`
* `tristate(MPSSEContext $ctx): int`

### Misc

* `version(): int`

---

## Enums

This package ships typed enums in `Microscrap\Bindings\MPSSE\Enums`:

* `MPSSEMode`
* `MPSSEInterface`
* `MPSSEEndianness`
* `MPSSEClockRate`
* `MPSSECommand`
* `MPSSEAck`
* `MPSSEPin`
* `MPSSEGpioPin`
* `MpsseSupportedDevice`

Cases are **FULLY UPPERCASE**. No class-level constants.

---

## Testing (Pest v4)

Run the feature suite:

```bash
./vendor/bin/pest
```

Run with coverage:

```bash
XDEBUG_MODE=coverage ./vendor/bin/pest --coverage
```

Feature coverage includes:

* `ext-ftdi` installation check (`extension_loaded('ftdi')` + semantic version format check)
* No-hardware fallback paths: invalid VID/PID open failure and closed-context guard behavior
* FT232H hardware workflows (open → configure → start → transfer → stop → close)
* SPI loopback, GPIO pin control, bitbang, and I2C session/ACK flows in required call order
* Wrong-workflow assertions on mode mismatches
* Helper workflow path: `mpsse_open(...)` / `mpsse_close(...)` on real hardware

## License

MIT. See [LICENSE.md](LICENSE.md).
