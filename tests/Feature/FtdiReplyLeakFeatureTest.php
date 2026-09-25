<?php

use DeptOfScrapyardRobotics\Tests\Support\MpsseHardware;
use Microscrap\Bindings\MPSSE\Enums\MPSSEClockRate;
use Microscrap\Bindings\MPSSE\Enums\MPSSEMode;
use Microscrap\Bindings\MPSSE\MPSSE;
use Microscrap\Bindings\MPSSE\MPSSEContext;

/** Bytes of PHP heap still held after $times calls of $read, past a warm-up. */
function heapGrowth(Closure $read, int $times): int
{
    for ($i = 0; $i < 100; $i++) {
        $read();
    }

    gc_collect_cycles();
    $before = memory_get_usage();

    for ($i = 0; $i < $times; $i++) {
        $read();
    }

    gc_collect_cycles();

    return memory_get_usage() - $before;
}

/** One submitted pin read: read submit, write submit, events until both finish, then the reply. */
function submittedPinRead(MPSSEContext $ctx): string|false
{
    $recording = MPSSE::record($ctx, fn () => mpsse_read_pins($ctx));
    $read = ftdi_read_data_submit($ctx->ftdi, $recording->responseLength());
    $write = ftdi_write_data_submit($ctx->ftdi, $recording->commands, strlen($recording->commands));

    while (ftdi_transfer_completed($write) === 0 || ftdi_transfer_completed($read) === 0) {
        ftdi_handle_events_timeout($ctx->ftdi, 1_000);
    }

    ftdi_transfer_data_done($write);

    return ftdi_transfer_read_done($read);
}

it('frees every reply ftdi_read_data hands back', function () {
    $ctx = MpsseHardware::openFt232h(MPSSEMode::I2C, MPSSEClockRate::FOUR_HUNDRED_KHZ->value);

    try {
        expect(heapGrowth(fn () => mpsse_read_pins($ctx), 5_000))->toBeLessThan(16 * 1024);
    } finally {
        MpsseHardware::close($ctx);
    }
});

it('frees every reply ftdi_transfer_read_done hands back', function () {
    $ctx = MpsseHardware::openFt232h(MPSSEMode::I2C, MPSSEClockRate::FOUR_HUNDRED_KHZ->value);

    try {
        expect(submittedPinRead($ctx))->toBeString()->toHaveLength(2)
            ->and(heapGrowth(fn () => submittedPinRead($ctx), 5_000))->toBeLessThan(16 * 1024);
    } finally {
        MpsseHardware::close($ctx);
    }
});
