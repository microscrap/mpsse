<?php

namespace DeptOfScrapyardRobotics\Tests;

use DeptOfScrapyardRobotics\Tests\Support\MpsseHardware;
use Microscrap\Bindings\MPSSE\Enums\MPSSEAck;
use Microscrap\Bindings\MPSSE\Enums\MPSSEClockRate;
use Microscrap\Bindings\MPSSE\Enums\MPSSEEndianness;
use Microscrap\Bindings\MPSSE\Enums\MPSSEInterface;
use Microscrap\Bindings\MPSSE\Enums\MPSSEMode;
use Microscrap\Bindings\MPSSE\Enums\MpsseSupportedDevice;
use Microscrap\Bindings\MPSSE\MPSSE;
use Microscrap\Bindings\MPSSE\MPSSEContext;

it('opens an FT232H through the supported-device workflow and reads session metadata', function (): void {
    MpsseHardware::withFt232h(function (MPSSEContext $context): void {
        expect($context->open)->toBeTrue();
        expect(MPSSE::getVid($context))->toBe(MpsseSupportedDevice::FT232H->vendorId());
        expect(MPSSE::getPid($context))->toBe(MpsseSupportedDevice::FT232H->productId());
        expect(MPSSE::getDescription($context))->toBeString();
        expect(MPSSE::getClock($context))->toBeGreaterThan(0);
        expect(MPSSE::version())->toBe(0x13);
    });
});

it('finds the FT232H through openSupported after scanning supported devices', function (): void {
    $context = MPSSE::openSupported(
        MPSSEMode::SPI0,
        MPSSEClockRate::ONE_MHZ->value,
        MPSSEEndianness::MSB,
    );

    if ($context === null) {
        throw new \PHPUnit\Framework\SkippedWithMessageException('No supported MPSSE device detected');
    }

    try {
        expect($context->open)->toBeTrue();
        expect(MPSSE::getPid($context))->toBe(MpsseSupportedDevice::FT232H->productId());
    } finally {
        MpsseHardware::close($context);
    }
});

it('supports helper open and close workflow on real hardware', function (): void {
    $context = mpsse_open(
        MpsseSupportedDevice::FT232H->vendorId(),
        MpsseSupportedDevice::FT232H->productId(),
        MPSSEMode::SPI0,
        MPSSEClockRate::ONE_MHZ->value,
        MPSSEEndianness::MSB,
        MPSSEInterface::IFACE_A,
    );

    if ($context === null) {
        throw new \PHPUnit\Framework\SkippedWithMessageException('FT232H not detected through helper open');
    }

    mpsse_close($context);

    expect($context->open)->toBeFalse();
    expect($context->ftdi)->toBeNull();
});

it('runs SPI loopback transfer workflow in the required order', function (): void {
    MpsseHardware::withFt232h(function (MPSSEContext $context): void {
        expect(MPSSE::setLoopback($context, true))->toBe(0);
        expect(MPSSE::disableHardwareChipSelect($context))->toBe(0);
        expect(MPSSE::setClock($context, MPSSEClockRate::TWO_MHZ->value))->toBe(0);
        expect(MPSSE::getClock($context))->toBeGreaterThan(0);
        expect(MPSSE::setMode($context, MPSSEEndianness::MSB))->toBe(0);

        expect(MPSSE::start($context))->toBe(0);

        $payload = "\xAA\x55\x9F";
        $length = strlen($payload);

        expect(MPSSE::write($context, $payload))->toBe(0);
        expect(MPSSE::read($context, $length))->toBeString()->toHaveLength($length);

        $transferred = MPSSE::transfer($context, $payload);
        expect($transferred)->toBeString()->toHaveLength($length);

        expect(MPSSE::fastWrite($context, $payload))->toBe(0);
        expect(MPSSE::fastRead($context, $length))->toBeString()->toHaveLength($length);
        expect(MPSSE::fastTransfer($context, $payload))->toBeString()->toHaveLength($length);

        expect(MPSSE::writeBits($context, 0b1010, 4))->toBe(0);
        expect(MPSSE::readBits($context, 4))->toBeInt();

        expect(MPSSE::stop($context))->toBe(0);
        expect(MPSSE::setLoopback($context, false))->toBe(0);
    });
});

it('runs GPIO and pin-state workflow while the bus is stopped', function (): void {
    MpsseHardware::withFt232h(function (MPSSEContext $context): void {
        expect(MPSSE::configurePinDirection($context, 0, true))->toBe(0);
        expect(MPSSE::pinHigh($context, 0))->toBe(0);
        expect(MPSSE::pinLow($context, 0))->toBe(0);

        $pinState = MPSSE::readPins($context);
        expect($pinState)->toBeInt();
        expect(MPSSE::pinState($context, 0, $pinState))->toBeIn([0, 1]);
    });
});

it('runs bitbang workflow after switching the device into bitbang mode', function (): void {
    MpsseHardware::withFt232h(function (MPSSEContext $context): void {
        expect(MPSSE::setDirection($context, 0xFF))->toBe(0);
        expect(MPSSE::writePins($context, 0x0F))->toBe(0);
        expect(MPSSE::readPins($context))->toBeInt();
    }, MPSSEMode::BITBANG);
});

it('runs I2C session and ACK workflow without assuming a connected slave', function (): void {
    MpsseHardware::withFt232h(function (MPSSEContext $context): void {
        MPSSE::sendAcks($context);
        expect(MPSSE::getAck($context))->toBeInt();

        MPSSE::sendNacks($context);
        MPSSE::setAck($context, MPSSEAck::ACK->value);

        expect(MPSSE::start($context))->toBe(0);
        expect(MPSSE::write($context, "\x00"))->toBeInt();
        expect(MPSSE::stop($context))->toBe(0);
    }, MPSSEMode::I2C);
});

it('rejects SPI-only transfer workflow when the session was opened in I2C mode', function (): void {
    MpsseHardware::withFt232h(function (MPSSEContext $context): void {
        expect(MPSSE::transfer($context, "\x9F"))->toBeNull();
        expect(MPSSE::fastTransfer($context, "\x9F"))->toBeNull();
    }, MPSSEMode::I2C);
});

it('rejects bitbang-only controls when the session was opened in SPI mode', function (): void {
    MpsseHardware::withFt232h(function (MPSSEContext $context): void {
        expect(MPSSE::setDirection($context, 0xFF))->toBe(-1);
        expect(MPSSE::writePins($context, 0x0F))->toBe(-1);
    });
});

it('rejects SPI start workflow calls on a closed context', function (): void {
    $context = new MPSSEContext;

    expect(MPSSE::start($context))->toBe(-1);
    expect(MPSSE::write($context, "\x9F"))->toBe(-1);
    expect(MPSSE::read($context, 1))->toBeNull();
    expect(MPSSE::transfer($context, "\x9F"))->toBeNull();
    expect(MPSSE::errorString($context))->toBe('NULL MPSSE context pointer!');
});
