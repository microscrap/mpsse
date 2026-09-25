<?php

namespace DeptOfScrapyardRobotics\Tests;

use DeptOfScrapyardRobotics\Tests\Support\MpsseHardware;
use Microscrap\Bindings\MPSSE\Enums\MPSSEMode;
use Microscrap\Bindings\MPSSE\MPSSE;
use Microscrap\Bindings\MPSSE\MPSSEContext;

it('reports a failed pin read on a closed context as -1', function (): void {
    $context = new MPSSEContext;

    expect(MPSSE::readPins($context))->toBe(-1)
        ->and(mpsse_read_pins($context))->toBe(-1);
});

it('reads every GPIO pin, GPIOL0-3 and GPIOH0-7, back through pinState()', function (): void {
    MpsseHardware::withFt232h(function (MPSSEContext $context): void {
        foreach (range(0, 11) as $pin) {
            expect(MPSSE::configurePinDirection($context, $pin, true))->toBe(0);

            expect(MPSSE::pinHigh($context, $pin))->toBe(0);
            expect(MPSSE::pinState($context, $pin, MPSSE::readPins($context)))->toBe(1, "pin {$pin} high");

            expect(MPSSE::pinLow($context, $pin))->toBe(0);
            expect(MPSSE::pinState($context, $pin, MPSSE::readPins($context)))->toBe(0, "pin {$pin} low");
        }
    }, MPSSEMode::GPIO);
});

it('returns both pin bytes in GPIO mode', function (): void {
    MpsseHardware::withFt232h(function (MPSSEContext $context): void {
        expect(MPSSE::configurePinDirection($context, 11, true))->toBe(0);
        expect(MPSSE::pinHigh($context, 11))->toBe(0);

        $pins = MPSSE::readPins($context);

        expect($pins)->toBeGreaterThanOrEqual(0)
            ->and($pins & (1 << 15))->toBe(1 << 15);
    }, MPSSEMode::GPIO);
});
