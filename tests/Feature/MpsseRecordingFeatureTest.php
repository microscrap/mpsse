<?php

use DeptOfScrapyardRobotics\Tests\Support\MpsseHardware;
use Microscrap\Bindings\MPSSE\Enums\MPSSEClockRate;
use Microscrap\Bindings\MPSSE\Enums\MPSSEMode;
use Microscrap\Bindings\MPSSE\MPSSE;
use Microscrap\Bindings\MPSSE\MPSSEContext;

/** Blocking read of exactly $length reply bytes, giving up after 100 ms of silence. */
function readReply(MPSSEContext $ctx, int $length): string
{
    $reply = '';
    $deadline = hrtime(true) + 100_000_000;

    while (strlen($reply) < $length && hrtime(true) < $deadline) {
        $chunk = ftdi_read_data($ctx->ftdi, $length - strlen($reply));
        $reply .= is_string($chunk) ? $chunk : '';
    }

    return $reply;
}

/** The LIS3DH's address on this FT232H (0x18 or 0x19), probed live. */
function lis3dhAddress(MPSSEContext $ctx): int
{
    foreach ([0x18, 0x19] as $address) {
        MPSSE::start($ctx);
        MPSSE::write($ctx, chr($address << 1));
        $acked = MPSSE::getAck($ctx) === 0;
        MPSSE::stop($ctx);

        if ($acked) {
            return $address;
        }
    }

    throw new RuntimeException('No LIS3DH at 0x18 or 0x19 on the FT232H.');
}

it('sends a recorded WHO_AM_I transaction in one write and gets 0x33 back', function () {
    $ctx = MpsseHardware::openFt232h(MPSSEMode::I2C, MPSSEClockRate::FOUR_HUNDRED_KHZ->value);

    try {
        $address = lis3dhAddress($ctx);

        $recording = MPSSE::record($ctx, function () use ($ctx, $address) {
            MPSSE::start($ctx);
            MPSSE::write($ctx, chr($address << 1)."\x0F");
            MPSSE::start($ctx);
            MPSSE::write($ctx, chr(($address << 1) | 1));
            MPSSE::sendNacks($ctx);
            MPSSE::read($ctx, 1);
            MPSSE::stop($ctx);
        });

        expect(ftdi_write_data($ctx->ftdi, $recording->commands, strlen($recording->commands)))->toBe(strlen($recording->commands))
            ->and($recording->decode(readReply($ctx, $recording->responseLength())))->toBe([true, "\x33"]);
    } finally {
        MpsseHardware::close($ctx);
    }
});

it('reports a NACK from an empty address in the recorded reply', function () {
    $ctx = MpsseHardware::openFt232h(MPSSEMode::I2C, MPSSEClockRate::FOUR_HUNDRED_KHZ->value);

    try {
        $recording = MPSSE::record($ctx, function () use ($ctx) {
            MPSSE::start($ctx);
            MPSSE::write($ctx, chr(0x50 << 1));
            MPSSE::stop($ctx);
        });

        ftdi_write_data($ctx->ftdi, $recording->commands, strlen($recording->commands));

        expect($recording->decode(readReply($ctx, $recording->responseLength()))[0])->toBeFalse();
    } finally {
        MpsseHardware::close($ctx);
    }
});

it('sends a WHO_AM_I recorded in two segments, the repeated START crossing two USB writes', function () {
    $ctx = MpsseHardware::openFt232h(MPSSEMode::I2C, MPSSEClockRate::FOUR_HUNDRED_KHZ->value);

    try {
        $address = lis3dhAddress($ctx);
        $segments = [];

        $read_phase = MPSSE::record($ctx, function () use ($ctx, $address, &$segments) {
            MPSSE::start($ctx);
            MPSSE::write($ctx, chr($address << 1)."\x0F");
            $segments[] = MPSSE::cut($ctx);
            MPSSE::start($ctx);
            MPSSE::write($ctx, chr(($address << 1) | 1));
            MPSSE::sendNacks($ctx);
            MPSSE::read($ctx, 1);
            MPSSE::stop($ctx);
        });

        $write_phase = $segments[0];

        ftdi_write_data($ctx->ftdi, $write_phase->commands, strlen($write_phase->commands));
        $first = $write_phase->decode(readReply($ctx, $write_phase->responseLength()));

        ftdi_write_data($ctx->ftdi, $read_phase->commands, strlen($read_phase->commands));

        expect($first)->toBe([true, ''])
            ->and($read_phase->decode(readReply($ctx, $read_phase->responseLength())))->toBe([true, "\x33"]);
    } finally {
        MpsseHardware::close($ctx);
    }
});
