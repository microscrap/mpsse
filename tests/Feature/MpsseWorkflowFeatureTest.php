<?php

namespace DeptOfScrapyardRobotics\Tests;

use Microscrap\Bindings\MPSSE\Enums\MPSSEClockRate;
use Microscrap\Bindings\MPSSE\Enums\MPSSEEndianness;
use Microscrap\Bindings\MPSSE\Enums\MPSSEInterface;
use Microscrap\Bindings\MPSSE\Enums\MPSSEMode;
use Microscrap\Bindings\MPSSE\MPSSE;
use Microscrap\Bindings\MPSSE\MPSSEContext;

it('supports the right workflow: open attempt, validate, and close safely', function (): void {
    $ctx = MPSSE::open(
        0xFFFF,
        0xFFFF,
        MPSSEMode::SPI0,
        MPSSEClockRate::ONE_MHZ->value,
        MPSSEEndianness::MSB,
        MPSSEInterface::IFACE_A,
    );

    expect($ctx)->toBeInstanceOf(MPSSEContext::class);

    if ($ctx->open) {
        expect(MPSSE::start($ctx))->toBeInt();
        expect(MPSSE::stop($ctx))->toBeInt();
    } else {
        expect(MPSSE::errorString($ctx))->toBeString()->not->toBe('');
    }

    MPSSE::close($ctx);

    expect($ctx->open)->toBeFalse();
    expect($ctx->ftdi)->toBeNull();
});

it('handles wrong workflow calls by returning failure sentinels', function (): void {
    $ctx = new MPSSEContext;

    expect(MPSSE::start($ctx))->toBe(-1);
    expect(MPSSE::write($ctx, "\x9F"))->toBe(-1);
    expect(MPSSE::read($ctx, 1))->toBeNull();
    expect(MPSSE::transfer($ctx, "\x9F"))->toBeNull();
    expect(MPSSE::configurePinDirection($ctx, 0, true))->toBe(-1);
    expect(MPSSE::errorString($ctx))->toBe('NULL MPSSE context pointer!');
});

it('handles helper workflow by returning null when open fails', function (): void {
    $ctx = mpsse_open(
        0xFFFF,
        0xFFFF,
        MPSSEMode::SPI0,
        MPSSEClockRate::ONE_MHZ->value,
        MPSSEEndianness::MSB,
        MPSSEInterface::IFACE_A,
    );

    expect($ctx)->toBeNull();
});
