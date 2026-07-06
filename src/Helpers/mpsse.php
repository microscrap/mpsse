<?php

use Microscrap\Bindings\FTDI\Enums\FtdiProductId;
use Microscrap\Bindings\MPSSE\Enums\MPSSEEndianness;
use Microscrap\Bindings\MPSSE\Enums\MPSSEInterface;
use Microscrap\Bindings\MPSSE\Enums\MPSSEMode;
use Microscrap\Bindings\MPSSE\MPSSE;
use Microscrap\Bindings\MPSSE\MPSSEContext;

if (! function_exists('mpsse_open')) {

    function mpsse_open(
        int $vid,
        int $pid,
        MPSSEMode $mode,
        int $freq,
        MPSSEEndianness $endianness,
        MPSSEInterface $iface = MPSSEInterface::IFACE_A,
        string $description = '',
        ?string $serial = null,
        string &$error = '',
    ): ?MPSSEContext {
        $context = MPSSE::open($vid, $pid, $mode, $freq, $endianness, $iface, $description, $serial);

        if ($context->open) {
            return $context;
        }

        $error = MPSSE::errorString($context);
        MPSSE::close($context);

        return null;
    }
}

if (! function_exists('mpsse_check_ftdi_device')) {
    function mpsse_check_ftdi_device(string $device): bool
    {
        $cases = array_filter(FtdiProductId::cases(), fn (FtdiProductId $case) => $case->name === $device);

        return count($cases) > 0;
    }
}

if (! function_exists('mpsse_configure_pin_direction')) {
    function mpsse_configure_pin_direction(MPSSEContext $ctx, int $pin, bool $asOutput): int
    {
        return MPSSE::configurePinDirection($ctx, $pin, $asOutput);
    }
}

if (! function_exists('mpsse_pin_high')) {

    function mpsse_pin_high(MPSSEContext $ctx, int $pin): int
    {
        return MPSSE::pinHigh($ctx, $pin);
    }
}

if (! function_exists('mpsse_pin_low')) {

    function mpsse_pin_low(MPSSEContext $ctx, int $pin): int
    {
        return MPSSE::pinLow($ctx, $pin);
    }
}

if (! function_exists('mpsse_pin_state')) {

    function mpsse_pin_state(MPSSEContext $ctx, int $pin, int $state = -1): int
    {
        return MPSSE::pinState($ctx, $pin, $state);
    }
}

if (! function_exists('mpsse_read_pins')) {

    function mpsse_read_pins(MPSSEContext $ctx): int
    {
        return MPSSE::readPins($ctx);
    }
}

if (! function_exists('mpsse_close')) {

    function mpsse_close(MPSSEContext $context): void
    {
        MPSSE::close($context);
    }
}
