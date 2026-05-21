<?php

namespace Microscrap\Bindings\MPSSE;

use Ftdi\FTDI;
use Microscrap\Bindings\MPSSE\Enums\MPSSEAck;
use Microscrap\Bindings\MPSSE\Enums\MPSSECommand;
use Microscrap\Bindings\MPSSE\Enums\MPSSEEndianness;
use Microscrap\Bindings\MPSSE\Enums\MpsseSupportedDevice;
use Microscrap\Bindings\MPSSE\Enums\MPSSEInterface;
use Microscrap\Bindings\MPSSE\Enums\MPSSEMode;
use Microscrap\Bindings\MPSSE\Enums\MPSSEPin;

/**
 * Pure-PHP port of libmpsse ({@link https://github.com/devttys0/libmpsse}) on top of {@see FTDI}.
 * All operations are static methods taking {@see MPSSEContext} as the first argument.
 */
final class MPSSE
{
    /**
     * Try each device in the built-in supported list and return the first that opens.
     * Mirrors the C {@code MPSSE()} function that scans {@code supported_devices[]}.
     */
    public static function openSupported(MPSSEMode $mode, int $freq, MPSSEEndianness $endianness): ?MPSSEContext
    {
        foreach (self::supportedDevices() as [$vid, $pid, $description]) {
            $ctx = self::openIndex($vid, $pid, $mode, $freq, $endianness, MPSSEInterface::IFACE_A, '', null, 0);
            if ($ctx->open) {
                $ctx->description = $description;

                return $ctx;
            }
            self::close($ctx);
        }

        return null;
    }

    public static function open(
        int $vid,
        int $pid,
        MPSSEMode $mode,
        int $freq,
        MPSSEEndianness $endianness,
        MPSSEInterface $iface,
        string $description = '',
        ?string $serial = null,
    ): MPSSEContext {
        return self::openIndex($vid, $pid, $mode, $freq, $endianness, $iface, $description, $serial, 0);
    }

    public static function openDevice(
        MpsseSupportedDevice $device,
        MPSSEMode $mode,
        int $freq,
        MPSSEEndianness $endianness,
        MPSSEInterface $iface = MPSSEInterface::IFACE_A,
        ?string $serial = null,
    ): MPSSEContext {
        return self::open(
            $device->vendorId(),
            $device->productId(),
            $mode,
            $freq,
            $endianness,
            $iface,
            $device->description(),
            $serial,
        );
    }

    public static function openIndex(
        int $vid,
        int $pid,
        MPSSEMode $mode,
        int $freq,
        MPSSEEndianness $endianness,
        MPSSEInterface $iface,
        string $description,
        ?string $serial,
        int $index,
    ): MPSSEContext {
        $ctx = new MPSSEContext;
        self::flushAfterRead($ctx, false);

        $ctx->ftdi = FTDI::ftdiNew();
        ftdi_set_interface($ctx->ftdi, $iface->value);

        if (ftdi_usb_open_desc_index($ctx->ftdi, $vid, $pid, $description, $serial ?? '', $index) !== 0) {
            return $ctx;
        }

        $ctx->mode = $mode->value;
        $ctx->vid = $vid;
        $ctx->pid = $pid;
        $ctx->status = 1; // STOPPED
        $ctx->endianess = $endianness->value;
        $ctx->xsize = ($mode === MPSSEMode::I2C) ? 64 : (63 * 1024); // I2C_TRANSFER_SIZE : SPI_RW_SIZE

        $status = 0;
        $status |= ftdi_usb_reset($ctx->ftdi);
        $status |= ftdi_set_latency_timer($ctx->ftdi, 2);                   // LATENCY_MS
        $status |= ftdi_write_data_set_chunksize($ctx->ftdi, 65535);        // CHUNK_SIZE
        $status |= ftdi_read_data_set_chunksize($ctx->ftdi, 65535);         // CHUNK_SIZE
        $status |= ftdi_set_bitmode($ctx->ftdi, 0, 0);                      // BITMODE_RESET

        if ($status === 0) {
            ftdi_set_timeouts($ctx->ftdi, 120000, 120000);                  // USB_TIMEOUT

            if ($mode !== MPSSEMode::BITBANG) {
                if (ftdi_set_bitmode($ctx->ftdi, 0, 2) === 0) {             // BITMODE_MPSSE
                    if (self::setClock($ctx, $freq) === 0 && self::setMode($ctx, $endianness) === 0) {
                        $ctx->open = true;
                        usleep(25000);                                       // SETUP_DELAY
                        ftdi_usb_purge_buffers($ctx->ftdi);
                    }
                }
            } elseif (ftdi_set_bitmode($ctx->ftdi, 0xFF, 1) === 0) {       // BITMODE_BITBANG
                $ctx->open = true;
            }
        }

        return $ctx;
    }

    public static function close(MPSSEContext $ctx): void
    {
        if ($ctx->ftdi !== null) {
            if ($ctx->open) {
                ftdi_set_bitmode($ctx->ftdi, 0, 0);                         // BITMODE_RESET
            }
            ftdi_usb_close($ctx->ftdi);
            ftdi_deinit($ctx->ftdi);
            ftdi_free($ctx->ftdi);
        }
        $ctx->open = false;
        $ctx->ftdi = null;
    }

    public static function errorString(?MPSSEContext $ctx): string
    {
        if ($ctx === null || $ctx->ftdi === null) {
            return 'NULL MPSSE context pointer!';
        }

        return ftdi_get_error_string($ctx->ftdi);
    }

    /**
     * Configure tx/rx/txrx command bytes and GPIO idle states for the requested mode.
     * Mirrors {@code SetMode()} in libmpsse.
     */
    public static function setMode(MPSSEContext $ctx, MPSSEEndianness $endianness): int
    {
        if ($ctx->ftdi === null) {
            return -1;
        }

        $ctx->endianess = $endianness->value;

        // MPSSE data-transfer direction bits (FTDI AN_108 §3.3):
        //   0x10 = DO_WRITE  (clock data out on TDI/DO)
        //   0x20 = DO_READ   (clock data in  on TDO/DI)
        //   0x01 = WRITE_NEG (clock data out on falling edge)
        //   0x04 = READ_NEG  (clock data in  on falling edge)
        $ctx->tx   = 0x10 | $endianness->value; // MPSSE_DO_WRITE | endianness
        $ctx->rx   = 0x20 | $endianness->value; // MPSSE_DO_READ  | endianness
        $ctx->txrx = 0x10 | 0x20 | $endianness->value;

        // SK/DO/CS and GPIO0–3 are outputs; DI is an input.  SK and CS idle high.
        $ctx->tris   = 0xFB; // DEFAULT_TRIS = SK|DO|CS|GPIO0|GPIO1|GPIO2|GPIO3
        $ctx->pidle  = 0x09; // DEFAULT_PORT = SK|CS
        $ctx->pstart = 0x09;
        $ctx->pstop  = 0x09;
        $ctx->pstart &= ~MPSSEPin::CS->value;

        self::setLoopback($ctx, false);
        self::setAck($ctx, MPSSEAck::ACK->value);

        $setupCommands = chr(MPSSECommand::DISABLE_ADAPTIVE_CLOCK->value);
        $retval = 0;

        switch ($ctx->mode) {
            case MPSSEMode::SPI0->value:
                $ctx->pidle  &= ~MPSSEPin::SK->value;
                $ctx->pstart &= ~MPSSEPin::SK->value;
                $ctx->pstop  &= ~MPSSEPin::SK->value;
                $ctx->tx    |= 0x01;  // WRITE_NEG — propagate data on falling edge
                $ctx->rx    &= ~0x04; // ~READ_NEG  — sample on rising edge
                $ctx->txrx  |= 0x01;
                $ctx->txrx  &= ~0x04;
                break;
            case MPSSEMode::SPI3->value:
                $ctx->pidle  |= MPSSEPin::SK->value;
                $ctx->pstart |= MPSSEPin::SK->value;
                $ctx->pstop  &= ~MPSSEPin::SK->value;
                $ctx->tx    |= 0x01;
                $ctx->rx    &= ~0x04;
                $ctx->txrx  |= 0x01;
                $ctx->txrx  &= ~0x04;
                break;
            case MPSSEMode::SPI1->value:
                $ctx->pidle  &= ~MPSSEPin::SK->value;
                $ctx->pstart &= ~MPSSEPin::SK->value;
                $ctx->pstop  |= MPSSEPin::SK->value;
                $ctx->rx    |= 0x04;  // READ_NEG
                $ctx->tx    &= ~0x01; // ~WRITE_NEG
                $ctx->txrx  |= 0x04;
                $ctx->txrx  &= ~0x01;
                break;
            case MPSSEMode::SPI2->value:
                $ctx->pidle  |= MPSSEPin::SK->value;
                $ctx->pstart |= MPSSEPin::SK->value;
                $ctx->pstop  |= MPSSEPin::SK->value;
                $ctx->rx    |= 0x04;
                $ctx->tx    &= ~0x01;
                $ctx->txrx  |= 0x04;
                $ctx->txrx  &= ~0x01;
                break;
            case MPSSEMode::I2C->value:
                $ctx->tx |= 0x01; // WRITE_NEG
                $ctx->rx &= ~0x04;
                $ctx->pidle  |= MPSSEPin::DO->value | MPSSEPin::DI->value;
                $ctx->pstart &= ~MPSSEPin::DO->value & ~MPSSEPin::DI->value;
                $ctx->pstop  &= ~MPSSEPin::DO->value & ~MPSSEPin::DI->value;
                $setupCommands .= chr(MPSSECommand::ENABLE_3_PHASE_CLOCK->value);
                break;
            case MPSSEMode::GPIO->value:
                break;
            default:
                $retval = -1;
                break;
        }

        if ($retval === 0 && $setupCommands !== '') {
            $retval = self::rawWrite($ctx, $setupCommands);
        }

        if ($retval === 0) {
            $retval = self::setBitsLow($ctx, $ctx->pidle);
        }

        if ($retval === 0) {
            $ctx->trish = 0xFF;
            $ctx->gpioh = 0x00;
            $retval = self::rawWrite(
                $ctx,
                chr(MPSSECommand::SET_BITS_HIGH->value).chr($ctx->gpioh).chr($ctx->trish),
            );
        }

        return $retval;
    }

    /**
     * Toggle bit-wise transfer mode on the tx/rx/txrx command bytes.
     * Must be called before/after {@see self::writeBits()} and {@see self::readBits()}.
     */
    public static function enableBitmode(MPSSEContext $ctx, bool $tf): void
    {
        if (! self::isValidContext($ctx)) {
            return;
        }
        // 0x02 = MPSSE_BITMODE flag in the FTDI transfer command byte
        if ($tf) {
            $ctx->tx    |= 0x02;
            $ctx->rx    |= 0x02;
            $ctx->txrx  |= 0x02;
        } else {
            $ctx->tx    &= ~0x02;
            $ctx->rx    &= ~0x02;
            $ctx->txrx  &= ~0x02;
        }
    }

    /**
     * Program the TCK divisor for the desired clock frequency.
     * Uses the 60 MHz base clock when freq > 6 MHz, otherwise 12 MHz.
     */
    public static function setClock(MPSSEContext $ctx, int $freq): int
    {
        if ($ctx->ftdi === null) {
            return -1;
        }

        if ($freq > 6_000_000) { // SIX_MHZ
            $systemClock = 60_000_000; // SIXTY_MHZ
            $clkCmd = MPSSECommand::TCK_X5;
        } else {
            $systemClock = 12_000_000; // TWELVE_MHZ
            $clkCmd = MPSSECommand::TCK_D5;
        }

        if (self::rawWrite($ctx, chr($clkCmd->value)) !== 0) {
            return -1;
        }

        $divisor = ($freq <= 0) ? 0xFFFF : max(0, self::freq2div($systemClock, $freq));

        $buf = chr(MPSSECommand::TCK_DIVISOR->value).chr($divisor & 0xFF).chr(($divisor >> 8) & 0xFF);
        if (self::rawWrite($ctx, $buf) !== 0) {
            return -1;
        }

        $ctx->clock = self::div2freq($systemClock, $divisor);

        return 0;
    }

    public static function getClock(MPSSEContext $ctx): int
    {
        return self::isValidContext($ctx) ? $ctx->clock : 0;
    }

    public static function getVid(MPSSEContext $ctx): int
    {
        return self::isValidContext($ctx) ? $ctx->vid : 0;
    }

    public static function getPid(MPSSEContext $ctx): int
    {
        return self::isValidContext($ctx) ? $ctx->pid : 0;
    }

    public static function getDescription(MPSSEContext $ctx): string
    {
        return self::isValidContext($ctx) ? $ctx->description : '';
    }

    public static function setLoopback(MPSSEContext $ctx, bool $enable): int
    {
        if (! self::isValidContext($ctx)) {
            return -1;
        }
        $cmd = $enable ? MPSSECommand::LOOPBACK_START : MPSSECommand::LOOPBACK_END;

        return self::rawWrite($ctx, chr($cmd->value));
    }

    public static function setCSIdle(MPSSEContext $ctx, bool $idle): void
    {
        if (! self::isValidContext($ctx)) {
            return;
        }
        if ($idle) {
            $ctx->pidle  |= MPSSEPin::CS->value;
            $ctx->pstop  |= MPSSEPin::CS->value;
            $ctx->pstart &= ~MPSSEPin::CS->value;
        } else {
            $ctx->pidle  &= ~MPSSEPin::CS->value;
            $ctx->pstop  &= ~MPSSEPin::CS->value;
            $ctx->pstart |= MPSSEPin::CS->value;
        }
    }

    /**
     * Keep ADBUS3 (hardware CS) deasserted in all MPSSE port states so that
     * chip-select can be driven manually on a GPIO pin instead.
     */
    public static function disableHardwareChipSelect(MPSSEContext $ctx): int
    {
        if (! self::isValidContext($ctx)) {
            return -1;
        }

        $ctx->pidle  |= MPSSEPin::CS->value;
        $ctx->pstart |= MPSSEPin::CS->value;
        $ctx->pstop  |= MPSSEPin::CS->value;

        return self::setBitsLow($ctx, $ctx->pidle);
    }

    /**
     * Configure ADBUS3 as active-low chip-select (idle high, asserted in Start()).
     * Mirrors libmpsse SetCSIdle(mpsse, 1) plus driving the idle pin state.
     */
    public static function enableHardwareChipSelect(MPSSEContext $ctx): int
    {
        if (! self::isValidContext($ctx)) {
            return -1;
        }

        self::setCSIdle($ctx, true);

        return self::setBitsLow($ctx, $ctx->pidle);
    }

    public static function flushAfterRead(MPSSEContext $ctx, bool $tf): void
    {
        $ctx->flushAfterRead = $tf;
    }

    public static function start(MPSSEContext $ctx): int
    {
        if (! self::isValidContext($ctx)) {
            $ctx->status = 1; // STOPPED
            return -1;
        }

        $status = 0;

        if ($ctx->mode === MPSSEMode::I2C->value && $ctx->status === 0 /* STARTED */) {
            // I2C repeated start: clock low, then idle
            $status |= self::setBitsLow($ctx, $ctx->pidle & ~MPSSEPin::SK->value);
            $status |= self::setBitsLow($ctx, $ctx->pidle);
        }

        $status |= self::setBitsLow($ctx, $ctx->pstart);

        // SPI mode 3: clock idles high but must go low before data to avoid glitches
        if ($ctx->mode === MPSSEMode::SPI3->value) {
            $status |= self::setBitsLow($ctx, $ctx->pstart & ~MPSSEPin::SK->value);
        // SPI mode 1: clock idles low but must go high before data to avoid glitches
        } elseif ($ctx->mode === MPSSEMode::SPI1->value) {
            $status |= self::setBitsLow($ctx, $ctx->pstart | MPSSEPin::SK->value);
        }

        $ctx->status = 0; // STARTED

        return $status;
    }

    public static function stop(MPSSEContext $ctx): int
    {
        if (! self::isValidContext($ctx)) {
            $ctx->status = 1;
            return -1;
        }

        $retval = 0;

        if ($ctx->mode === MPSSEMode::I2C->value) {
            // Drive data low while clock is low before asserting the stop condition
            $retval |= self::setBitsLow($ctx, $ctx->pidle & ~MPSSEPin::DO->value & ~MPSSEPin::SK->value);
        }

        $retval |= self::setBitsLow($ctx, $ctx->pstop);

        if ($retval === 0) {
            $retval |= self::setBitsLow($ctx, $ctx->pidle);
        }

        $ctx->status = 1; // STOPPED

        return $retval;
    }

    /**
     * Send data over the configured serial protocol.
     * In I2C mode each byte is sent individually so the ACK bit can be read back.
     */
    public static function write(MPSSEContext $ctx, string $data): int
    {
        if (! self::isValidContext($ctx) || ! $ctx->mode) {
            return -1;
        }

        $size = strlen($data);
        $n    = 0;

        while ($n < $size) {
            $txsize = min($size - $n, $ctx->xsize);
            if ($ctx->mode === MPSSEMode::I2C->value) {
                $txsize = 1;
            }

            $buf = self::buildBlockBuffer($ctx, $ctx->tx, substr($data, $n, $txsize));
            if ($buf === null) {
                return -1;
            }

            if (self::rawWrite($ctx, $buf) !== 0) {
                return -1;
            }

            $n += $txsize;

            if ($ctx->mode === MPSSEMode::I2C->value) {
                $ack = self::rawRead($ctx, 1);
                if ($ack === false) {
                    return -1;
                }
                $ctx->rack = ord($ack);
            }
        }

        return 0;
    }

    public static function read(MPSSEContext $ctx, int $size): ?string
    {
        return self::internalRead($ctx, $size);
    }

    /**
     * Bit-wise write of up to 8 bits.
     * Each bit in {@code $bits} is expanded to a full byte (0x00 or 0xFF)
     * respecting endianness, then sent with bitmode enabled.
     */
    public static function writeBits(MPSSEContext $ctx, int $bits, int $size): int
    {
        $size = min($size, 8);
        $data = str_repeat("\0", $size);

        for ($i = 0; $i < $size; $i++) {
            if (($bits & (1 << $i)) !== 0) {
                $pos = ($ctx->endianess === MPSSEEndianness::LSB->value) ? $i : ($size - $i - 1);
                $data[$pos] = "\xFF";
            }
        }

        self::enableBitmode($ctx, true);
        $retval = self::write($ctx, $data);
        self::enableBitmode($ctx, false);

        return $retval;
    }

    /** Bit-wise read of up to 8 bits, returned as a byte with bits shifted to the correct position. */
    public static function readBits(MPSSEContext $ctx, int $size): int
    {
        $size = min($size, 8);

        self::enableBitmode($ctx, true);
        $rdata = self::internalRead($ctx, $size);
        self::enableBitmode($ctx, false);

        if ($rdata === null || $rdata === '') {
            return 0;
        }

        $bits = ord($rdata[strlen($rdata) - 1]);

        if ($ctx->endianess === MPSSEEndianness::MSB->value) {
            $bits <<= (8 - $size); // MSB-in: shift left to align
        } else {
            $bits >>= (8 - $size); // LSB-in: shift right to align
        }

        return $bits;
    }

    /** Simultaneous read+write (SPI modes only), chunked to 512-byte blocks. */
    public static function transfer(MPSSEContext $ctx, string $data): ?string
    {
        if (! self::isValidContext($ctx)) {
            return null;
        }

        $mode = MPSSEMode::tryFrom($ctx->mode);
        if ($mode === null || $mode->value < MPSSEMode::SPI0->value || $mode->value > MPSSEMode::SPI3->value) {
            return null;
        }

        $size = strlen($data);
        $buf  = str_repeat("\0", $size);
        $n    = 0;

        while ($n < $size) {
            $rxsize = min($size - $n, 512); // SPI_TRANSFER_SIZE
            $txdata = self::buildBlockBuffer($ctx, $ctx->txrx, substr($data, $n, $rxsize));
            if ($txdata === null || self::rawWrite($ctx, $txdata) !== 0) {
                return null;
            }
            $read = self::rawRead($ctx, $rxsize);
            if ($read === false) {
                return null;
            }
            $len = strlen($read);
            for ($i = 0; $i < $len; $i++) {
                $buf[$n + $i] = $read[$i];
            }
            $n += $len;
        }

        return $buf;
    }

    public static function getAck(MPSSEContext $ctx): int
    {
        return self::isValidContext($ctx) ? ($ctx->rack & 0x01) : 0;
    }

    public static function setAck(MPSSEContext $ctx, int $ack): void
    {
        if (! self::isValidContext($ctx)) {
            return;
        }
        $ctx->tack = ($ack === MPSSEAck::NACK->value) ? 0xFF : 0x00;
    }

    public static function sendAcks(MPSSEContext $ctx): void
    {
        self::setAck($ctx, MPSSEAck::ACK->value);
    }

    public static function sendNacks(MPSSEContext $ctx): void
    {
        self::setAck($ctx, MPSSEAck::NACK->value);
    }

    public static function pinHigh(MPSSEContext $ctx, int $pin): int
    {
        return self::isValidContext($ctx) ? self::gpioWrite($ctx, $pin, 1) : -1;
    }

    public static function pinLow(MPSSEContext $ctx, int $pin): int
    {
        return self::isValidContext($ctx) ? self::gpioWrite($ctx, $pin, 0) : -1;
    }

    /** Set all-pin direction mask (BITBANG mode only). */
    public static function setDirection(MPSSEContext $ctx, int $direction): int
    {
        if (! self::isValidContext($ctx) || $ctx->mode !== MPSSEMode::BITBANG->value || $ctx->ftdi === null) {
            return -1;
        }

        return ftdi_set_bitmode($ctx->ftdi, $direction, 1) === 0 ? 0 : -1; // BITMODE_BITBANG = 1
    }

    /** Write all pins simultaneously (BITBANG mode only). */
    public static function writePins(MPSSEContext $ctx, int $data): int
    {
        if (! self::isValidContext($ctx) || $ctx->mode !== MPSSEMode::BITBANG->value || $ctx->ftdi === null) {
            return -1;
        }

        return ftdi_write_data($ctx->ftdi, chr($data & 0xFF), 1) === 1 ? 0 : -1;
    }

    public static function readPins(MPSSEContext $ctx): int
    {
        return ($ctx->ftdi !== null) ? ftdi_read_pins($ctx->ftdi) : 0;
    }

    /**
     * Check whether a specific pin is high (1) or low (0).
     * Pass {@code $state = -1} to call {@see self::readPins()} automatically.
     * In non-BITBANG modes the pin number is offset by the number of low GPIO pins.
     */
    public static function pinState(MPSSEContext $ctx, int $pin, int $state): int
    {
        if ($state === -1) {
            $state = self::readPins($ctx);
        }

        if ($ctx->mode !== MPSSEMode::BITBANG->value) {
            $pin += 4; // NUM_GPIOL_PINS
        }

        return ($state & (1 << $pin)) >> $pin;
    }

    /**
     * Configure a single GPIO pin as an input or output by updating the direction register.
     *
     * For MPSSE GPIO mode (pins 0–3 = GPIOL, pins 4–11 = GPIOH):
     *   Clears or sets the corresponding bit in {@see MPSSEContext::$tris} / {@see MPSSEContext::$trish}
     *   and immediately writes the SET_BITS_LOW / SET_BITS_HIGH command to apply it.
     *
     * For BITBANG mode:
     *   Updates {@see MPSSEContext::$bitbangDirection} and calls {@see self::setDirection()}.
     *
     * Returns 0 on success, -1 on failure.
     */
    public static function configurePinDirection(MPSSEContext $ctx, int $pin, bool $asOutput): int
    {
        if (! self::isValidContext($ctx)) {
            return -1;
        }

        if ($ctx->mode === MPSSEMode::BITBANG->value) {
            if ($asOutput) {
                $ctx->bitbangDirection |= (1 << $pin);
            } else {
                $ctx->bitbangDirection &= ~(1 << $pin);
            }

            return self::setDirection($ctx, $ctx->bitbangDirection);
        }

        // MPSSE GPIO mode — GPIOL0–3 live in the low-byte tris register
        if ($pin >= 0 && $pin <= 3) {
            $pinBit = MPSSEPin::GPIO0->value << $pin; // 0x10, 0x20, 0x40, 0x80
            if ($asOutput) {
                $ctx->tris |= $pinBit;
            } else {
                $ctx->tris &= ~$pinBit;
            }

            return self::setBitsLow($ctx, $ctx->pidle);
        }

        // GPIOH0–7 live in the high-byte trish register (FT232H / FT2232H ACBUS)
        if ($pin >= 4 && $pin <= 11) {
            $bit = $pin - 4;
            if ($asOutput) {
                $ctx->trish |= (1 << $bit);
            } else {
                $ctx->trish &= ~(1 << $bit);
            }

            return self::setBitsHigh($ctx, $ctx->gpioh);
        }

        return -1;
    }

    /** Drive all I/O pins to tristate (FT232H only). */
    public static function tristate(MPSSEContext $ctx): int
    {
        return self::rawWrite($ctx, chr(MPSSECommand::TRISTATE_IO->value)."\xFF\xFF");
    }

    /**
     * Returns a packed version byte: high nibble = major, low nibble = minor.
     * Mirrors {@code Version()} in libmpsse (PACKAGE_VERSION = "1.3").
     */
    public static function version(): int
    {
        return (1 << 4) | (3 & 0x0F); // major=1, minor=3
    }

    /** Fast single-block write (SPI only) — skips the I2C ACK machinery. */
    public static function fastWrite(MPSSEContext $ctx, string $data): int
    {
        if (! self::isValidContext($ctx) || ! $ctx->mode) {
            return -1;
        }

        $size = strlen($data);
        $n    = 0;

        while ($n < $size) {
            $txsize = min($size - $n, $ctx->xsize);
            $buf = self::fastBuildBlockBuffer($ctx, $ctx->tx, substr($data, $n, $txsize));
            if ($buf === null || self::rawWrite($ctx, $buf) !== 0) {
                return -1;
            }
            $n += $txsize;
        }

        return $n === $size ? 0 : -1;
    }

    /** Fast single-block read (SPI only). */
    public static function fastRead(MPSSEContext $ctx, int $size): ?string
    {
        if (! self::isValidContext($ctx) || ! $ctx->mode) {
            return null;
        }

        $out = '';
        $n   = 0;

        while ($n < $size) {
            $rxsize = min($size - $n, $ctx->xsize);
            $buf = self::fastBuildBlockBuffer($ctx, $ctx->rx, str_repeat("\0", $rxsize));
            if ($buf === null || self::rawWrite($ctx, $buf) !== 0) {
                return null;
            }
            $read = self::rawRead($ctx, $rxsize);
            if ($read === false) {
                return null;
            }
            $out .= $read;
            $n += strlen($read);
        }

        return $n === $size ? $out : null;
    }

    /** Fast simultaneous read+write (SPI only). */
    public static function fastTransfer(MPSSEContext $ctx, string $wdata): ?string
    {
        if (! self::isValidContext($ctx)) {
            return null;
        }

        $mode = MPSSEMode::tryFrom($ctx->mode);
        if ($mode === null || $mode->value < MPSSEMode::SPI0->value || $mode->value > MPSSEMode::SPI3->value) {
            return null;
        }

        $size  = strlen($wdata);
        $rdata = str_repeat("\0", $size);
        $n     = 0;

        while ($n < $size) {
            $rxsize = min($size - $n, 512); // SPI_TRANSFER_SIZE
            $block  = self::buildBlockBuffer($ctx, $ctx->txrx, substr($wdata, $n, $rxsize));
            if ($block === null || self::rawWrite($ctx, $block) !== 0) {
                return null;
            }
            $read = self::rawRead($ctx, $rxsize);
            if ($read === false) {
                return null;
            }
            $len = strlen($read);
            for ($i = 0; $i < $len; $i++) {
                $rdata[$n + $i] = $read[$i];
            }
            $n += $len;
        }

        return $n === $size ? $rdata : null;
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /** Write {@code $buf} to the FTDI chip; returns 0 on success, -1 on failure. */
    private static function rawWrite(MPSSEContext $ctx, string $buf): int
    {
        if (! $ctx->mode || $ctx->ftdi === null) {
            return -1;
        }
        $size = strlen($buf);

        return ftdi_write_data($ctx->ftdi, $buf, $size) === $size ? 0 : -1;
    }

    /**
     * Read exactly {@code $size} bytes from the FTDI chip in a retry loop.
     * Returns the accumulated string on success, {@code false} on transport failure.
     *
     * The FTDI chip's USB latency timer is set to 2 ms (LATENCY_MS in {@see self::openIndex()}).
     * This means the chip may not flush response bytes to the USB host immediately, so a
     * single {@code ftdi_read_data} call often returns empty.  We retry up to ~10 ms
     * total (500 µs × 20) before giving up, which is sufficient for all MPSSE protocols.
     */
    private static function rawRead(MPSSEContext $ctx, int $size): string|false
    {
        if (! $ctx->mode || $ctx->ftdi === null) {
            return false;
        }

        $acc     = '';
        $n       = 0;
        $retries = 0;

        while ($n < $size) {
            $chunk = ftdi_read_data($ctx->ftdi, $size - $n);
            if ($chunk === '' || $chunk === false) {
                if (++$retries > 20) {      // 20 × 500 µs = 10 ms max wait
                    break;
                }
                usleep(500);
                continue;
            }
            $retries = 0;
            $acc    .= $chunk;
            $n      += strlen($chunk);
        }

        if ($ctx->flushAfterRead) {
            ftdi_usb_purge_rx_buffer($ctx->ftdi);
        }

        return $n === $size ? $acc : false;
    }

    /** Issue read-command blocks and accumulate the response. Used by {@see self::read()} and {@see self::readBits()}. */
    private static function internalRead(MPSSEContext $ctx, int $size): ?string
    {
        if (! self::isValidContext($ctx) || ! $ctx->mode) {
            return null;
        }

        if ($size === 0) {
            return '';
        }

        $buf = str_repeat("\0", $size);
        $n   = 0;

        while ($n < $size) {
            $rxsize = min($size - $n, $ctx->xsize);
            $cmd    = self::buildBlockBuffer($ctx, $ctx->rx, str_repeat("\0", $rxsize));
            if ($cmd === null || self::rawWrite($ctx, $cmd) !== 0) {
                break;
            }
            $read = self::rawRead($ctx, $rxsize);
            if ($read === false) {
                break;
            }
            $len = strlen($read);
            for ($i = 0; $i < $len; $i++) {
                $buf[$n + $i] = $read[$i];
            }
            $n += $len;
        }

        return $n === $size ? $buf : null;
    }

    /**
     * Assemble a fully-framed MPSSE command buffer for {@code $size} bytes of {@code $data}.
     *
     * Each block has a 3-byte header (cmd, lo, hi) plus the payload.
     * In I2C mode three extra 3-byte sequences are prepended/appended per block
     * to handle the SCL/SDA pin state and the ACK bit.
     */
    private static function buildBlockBuffer(MPSSEContext $ctx, int $cmd, string $data): ?string
    {
        $size = strlen($data);

        // Block size: 1 in I2C, or 1 when MPSSE_BITMODE (0x02) flag is set
        $xferSize = ($ctx->mode === MPSSEMode::I2C->value || ($cmd & 0x02) !== 0)
            ? 1
            : $ctx->xsize;

        $numBlocks = intdiv($size, $xferSize) + ($size % $xferSize !== 0 ? 1 : 0);

        $buf = '';
        $k   = 0; // offset into $data

        for ($j = 0; $j < $numBlocks; $j++) {
            $dsize = min($size - $k, $xferSize);
            $rsize = $dsize - 1; // FTDI reports size-1

            if ($ctx->mode === MPSSEMode::I2C->value) {
                // Ensure SCL is low before clocking data
                $buf .= chr(MPSSECommand::SET_BITS_LOW->value);
                $buf .= chr($ctx->pstart & ~MPSSEPin::SK->value);
                // On receive, release DO to avoid bus contention
                $buf .= chr(($cmd === $ctx->rx) ? ($ctx->tris & ~MPSSEPin::DO->value) : $ctx->tris);
            }

            $buf .= chr($cmd);
            $buf .= chr($rsize & 0xFF);
            if (($cmd & 0x02) === 0) { // not MPSSE_BITMODE — two-byte length
                $buf .= chr(($rsize >> 8) & 0xFF);
            }

            if ($cmd === $ctx->tx || $cmd === $ctx->txrx) {
                $buf .= substr($data, $k, $dsize);
                $k   += $dsize;
            }

            if ($ctx->mode === MPSSEMode::I2C->value) {
                if ($cmd === $ctx->rx) {
                    // Clock out the ACK/NACK bit after each received byte
                    $buf .= chr(MPSSECommand::SET_BITS_LOW->value);
                    $buf .= chr($ctx->pstart & ~MPSSEPin::SK->value);
                    $buf .= chr($ctx->tris);
                    $buf .= chr($ctx->tx | 0x02); // tx | MPSSE_BITMODE
                    $buf .= chr(0);
                    $buf .= chr($ctx->tack);
                } elseif ($cmd === $ctx->tx) {
                    // Release DO and clock in the slave ACK bit
                    $buf .= chr(MPSSECommand::SET_BITS_LOW->value);
                    $buf .= chr($ctx->pstart & ~MPSSEPin::SK->value);
                    $buf .= chr($ctx->tris & ~MPSSEPin::DO->value);
                    $buf .= chr($ctx->rx | 0x02); // rx | MPSSE_BITMODE
                    $buf .= chr(0);
                    $buf .= chr(MPSSECommand::SEND_IMMEDIATE->value);
                }
            }
        }

        return $buf;
    }

    /** Drive the ADBUS low-byte pins (SCK/DO/DI/CS/GPIO0–3) to {@code $port}. */
    private static function setBitsLow(MPSSEContext $ctx, int $port): int
    {
        return self::rawWrite(
            $ctx,
            chr(MPSSECommand::SET_BITS_LOW->value).chr($port & 0xFF).chr($ctx->tris & 0xFF),
        );
    }

    /** Drive the ACBUS high-byte pins (GPIOH0–7) to {@code $port}. */
    private static function setBitsHigh(MPSSEContext $ctx, int $port): int
    {
        return self::rawWrite(
            $ctx,
            chr(MPSSECommand::SET_BITS_HIGH->value).chr($port & 0xFF).chr($ctx->trish & 0xFF),
        );
    }

    /** Set a single GPIO pin high or low, handling BITBANG vs MPSSE GPIO routing. */
    private static function gpioWrite(MPSSEContext $ctx, int $pin, int $direction): int
    {
        if ($ctx->mode === MPSSEMode::BITBANG->value) {
            if ($direction === 1) {
                $ctx->bitbang |= 1 << $pin;
            } else {
                $ctx->bitbang &= ~(1 << $pin);
            }

            if (self::setBitsHigh($ctx, $ctx->bitbang) === 0) {
                return self::rawWrite($ctx, chr($ctx->bitbang & 0xFF));
            }

            return -1;
        }

        // GPIOL0–3: only writable while the bus is stopped
        if ($pin < 4 /* NUM_GPIOL_PINS */ && $ctx->status === 1 /* STOPPED */) {
            $pinBit = MPSSEPin::GPIO0->value << $pin;
            if ($direction === 1) {
                $ctx->pstart |= $pinBit;
                $ctx->pidle  |= $pinBit;
                $ctx->pstop  |= $pinBit;
            } else {
                $ctx->pstart &= ~$pinBit;
                $ctx->pidle  &= ~$pinBit;
                $ctx->pstop  &= ~$pinBit;
            }

            return self::setBitsLow($ctx, $ctx->pidle);
        }

        // GPIOH0–7 (pins 4–11): routed through the high-byte register
        if ($pin >= 4 && $pin < 12 /* NUM_GPIO_PINS */) {
            $bit = $pin - 4; // NUM_GPIOL_PINS
            if ($direction === 1) {
                $ctx->gpioh |= 1 << $bit;
            } else {
                $ctx->gpioh &= ~(1 << $bit);
            }

            return self::setBitsHigh($ctx, $ctx->gpioh);
        }

        return -1;
    }

    /** {@code freq2div} from libmpsse: converts a target frequency to a TCK divisor. */
    private static function freq2div(int $systemClock, int $freq): int
    {
        return intdiv(intdiv($systemClock, $freq), 2) - 1;
    }

    /** {@code div2freq} from libmpsse: converts a TCK divisor back to the actual frequency. */
    private static function div2freq(int $systemClock, int $div): int
    {
        return intdiv($systemClock, (1 + $div) * 2);
    }

    private static function isValidContext(MPSSEContext $ctx): bool
    {
        return $ctx->open && $ctx->ftdi !== null;
    }

    /**
     * Simplified block-buffer builder used by the Fast* methods.
     * Omits I2C ACK scaffolding and always emits a 3-byte header (no MPSSE_BITMODE support).
     */
    private static function fastBuildBlockBuffer(MPSSEContext $ctx, int $cmd, string $data): ?string
    {
        $size = strlen($data);
        if ($size === 0) {
            return null;
        }

        $rsize = $size - 1;
        $buf   = chr($cmd).chr($rsize & 0xFF).chr(($rsize >> 8) & 0xFF);

        if ($cmd === $ctx->tx || $cmd === $ctx->txrx) {
            if (strlen($buf) + $size > (63 * 1024) + 3) { // SPI_RW_SIZE + CMD_SIZE
                return null;
            }
            $buf .= $data;
        }
        // For rx commands the header alone is sent; the chip streams back $size bytes.

        return $buf;
    }

    /**
     * Devices supported by libmpsse out of the box.
     * Mirrors {@code supported_devices[]} in {@code mpsse.c} (sentinel entry excluded).
     *
     * @return list<array{int, int, string}>
     */
    private static function supportedDevices(): array
    {
        return MpsseSupportedDevice::toSupportedDevicesTable();
    }
}
