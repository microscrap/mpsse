<?php

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
    ): ?MPSSEContext {
        $context = MPSSE::open($vid, $pid, $mode, $freq, $endianness, $iface, $description, $serial);

        return $context->open ? $context : null;
    }
}

if (! function_exists('mpsse_close')) {

    function mpsse_close(MPSSEContext $context): void
    {
        MPSSE::close($context);
    }
}
