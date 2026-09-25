<?php

use Ftdi\FTDIContext;
use Microscrap\Bindings\MPSSE\Enums\MPSSECommand;
use Microscrap\Bindings\MPSSE\Enums\MPSSEMode;
use Microscrap\Bindings\MPSSE\MPSSE;
use Microscrap\Bindings\MPSSE\MPSSEContext;
use Microscrap\Bindings\MPSSE\MPSSERecording;

/** An I2C-mode context that never touches USB: recording answers every read itself. */
function recordableI2CContext(): MPSSEContext
{
    $ctx = new MPSSEContext;
    $ctx->ftdi = new FTDIContext;
    $ctx->open = true;
    $ctx->mode = MPSSEMode::I2C->value;
    $ctx->status = 1;
    $ctx->xsize = 1;
    $ctx->tx = 0x11;
    $ctx->rx = 0x20;
    $ctx->pstart = 0x03;
    $ctx->pidle = 0x03;
    $ctx->pstop = 0x03;
    $ctx->tris = 0x13;

    return $ctx;
}

it('records one ACK read per written byte and ends the stream with SEND_IMMEDIATE', function () {
    $ctx = recordableI2CContext();

    $recording = MPSSE::record($ctx, function () use ($ctx) {
        MPSSE::start($ctx);
        MPSSE::write($ctx, "\x30\x0F");
        MPSSE::stop($ctx);
    });

    expect($recording->reads)->toBe([[1, true], [1, true]])
        ->and($recording->responseLength())->toBe(2)
        ->and($recording->commands)->not->toBe('')
        ->and($recording->commands[-1])->toBe(chr(MPSSECommand::SEND_IMMEDIATE->value));
});

it('records data reads apart from ACK reads', function () {
    $ctx = recordableI2CContext();

    $recording = MPSSE::record($ctx, function () use ($ctx) {
        MPSSE::start($ctx);
        MPSSE::write($ctx, "\x31");
        MPSSE::sendNacks($ctx);
        expect(MPSSE::read($ctx, 3))->toBe("\0\0\0");
        MPSSE::stop($ctx);
    });

    expect($recording->reads[0])->toBe([1, true])
        ->and(array_sum(array_map(fn (array $read): int => $read[0], array_slice($recording->reads, 1))))->toBe(3)
        ->and(array_filter(array_slice($recording->reads, 1), fn (array $read): bool => $read[1]))->toBe([]);
});

it('assumes an ACK while recording, so a transaction is recorded whole', function () {
    $ctx = recordableI2CContext();

    MPSSE::record($ctx, function () use ($ctx) {
        MPSSE::write($ctx, "\x30");

        expect(MPSSE::getAck($ctx))->toBe(0);
    });
});

it('moves context state exactly as the live calls would', function () {
    $ctx = recordableI2CContext();

    MPSSE::record($ctx, fn () => MPSSE::start($ctx));
    expect($ctx->status)->toBe(0);

    MPSSE::record($ctx, fn () => MPSSE::stop($ctx));
    expect($ctx->status)->toBe(1);
});

it('stops recording even when the body throws', function () {
    $ctx = recordableI2CContext();

    expect(fn () => MPSSE::record($ctx, fn () => throw new RuntimeException('boom')))->toThrow(RuntimeException::class, 'boom')
        ->and($ctx->recording)->toBeNull();
});

it('decodes a reply into ACK state and data bytes', function () {
    $recording = new MPSSERecording;
    $recording->expect(1, true);
    $recording->expect(1, true);
    $recording->expect(2, false);

    expect($recording->decode("\x00\x00\x33\x44"))->toBe([true, "\x33\x44"])
        ->and($recording->decode("\x00\x01\x33\x44"))->toBe([false, "\x33\x44"])
        ->and($recording->decode("\xFE\x00\x33\x44"))->toBe([true, "\x33\x44"]);
});

it('cuts a recording into segments, each ending in SEND_IMMEDIATE, with context state carried across', function () {
    $ctx = recordableI2CContext();
    $segments = [];

    $last = MPSSE::record($ctx, function () use ($ctx, &$segments) {
        MPSSE::start($ctx);
        MPSSE::write($ctx, "\x30\x0F");
        $segments[] = MPSSE::cut($ctx);
        MPSSE::start($ctx);
        MPSSE::write($ctx, "\x31");
        MPSSE::sendNacks($ctx);
        MPSSE::read($ctx, 1);
        MPSSE::stop($ctx);
    });

    expect($segments[0]->reads)->toBe([[1, true], [1, true]])
        ->and($segments[0]->commands[-1])->toBe(chr(MPSSECommand::SEND_IMMEDIATE->value))
        ->and($last->reads)->toBe([[1, true], [1, false]])
        ->and($last->commands[-1])->toBe(chr(MPSSECommand::SEND_IMMEDIATE->value))
        ->and($ctx->status)->toBe(1)
        ->and($ctx->recording)->toBeNull();
});

it('refuses to cut outside a recording', function () {
    expect(fn () => MPSSE::cut(recordableI2CContext()))->toThrow(LogicException::class, 'MPSSE::cut() outside MPSSE::record()');
});
