# microscrap/mpsse - MPSSE helper + static API for ScrapyardIO

[![Coverage](https://img.shields.io/badge/coverage-75.0%25-yellow)](#testing-pest-v4)

PHP library that provides MPSSE-oriented SPI/I2C/GPIO operations on top of [`microscrap/ftdi`](https://github.com/microscrap/ftdi) and the [`ext-ftdi`](https://github.com/php-io-extensions/ftdi) extension.

This package includes:

* Global helper functions (`mpsse_open`, `mpsse_close`)
* A full static-object API via `Microscrap\Bindings\MPSSE\MPSSE`
* Typed enums for modes, pins, commands, interfaces, endianness, and common clock rates

## Requirements

* PHP 8.3+
* `ext-ftdi` ^0.4.0
* `microscrap/ftdi` ^0.4.0

## Installation

Confirm `ext-ftdi` is loaded:

```bash
php -m | grep ftdi
```

Install package:

```bash
composer require microscrap/mpsse
```

Composer autoloads `src/Helpers/mpsse.php`, which registers global helpers.

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

if ($ctx === null) {
    throw new RuntimeException('Unable to open MPSSE device');
}

mpsse_close($ctx);
```

### Static object API style

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

### `mpsse_open(...): ?Microscrap\Bindings\MPSSE\MPSSEContext`

Wrapper for `MPSSE::open(...)`. Returns `null` if the underlying context did not open.

### `mpsse_close(Microscrap\Bindings\MPSSE\MPSSEContext $context): void`

Wrapper for `MPSSE::close(...)`.

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

Implemented feature coverage includes:

* `ext-ftdi` installation check (`extension_loaded('ftdi')` + semantic version format check)
* No-hardware fallback paths: invalid VID/PID open failure and closed-context guard behavior
* FT232H hardware workflows (open -> configure -> start -> transfer -> stop -> close)
* SPI loopback, GPIO pin control, bitbang, and I2C session/ACK flows in required call order
* Wrong-workflow assertions on mode mismatches (SPI transfer in I2C session, bitbang controls in SPI session)
* Helper workflow path: `mpsse_open(...)` / `mpsse_close(...)` on real hardware
* Latest measured total line coverage with FT232H attached: `75.0%`

## Code Completion Scan Results

Source scan summary (from current code):

* `src/MPSSE.php`: `41` public static methods
* `src/Helpers/mpsse.php`: `2` global helper functions
* `src/Enums`: `9` enum types

README API sections are aligned to the current scanned symbols.

## License

MIT. See [LICENSE.md](LICENSE.md).
