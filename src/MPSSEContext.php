<?php

namespace Microscrap\Bindings\MPSSE;

use Ftdi\FTDIContext;

/**
 * Mirrors `struct mpsse_context` from libmpsse (mpsse.h).
 */
class MPSSEContext
{
    public string $description = '';

    public ?FTDIContext $ftdi = null;

    public int $mode = 0;

    /** STARTED = 0, STOPPED = 1 */
    public int $status = 0;

    public bool $flushAfterRead = false;

    public int $vid = 0;

    public int $pid = 0;

    public int $clock = 0;

    public int $xsize = 0;

    public bool $open = false;

    /** MSB = 0x00, LSB = 0x08 in libmpsse */
    public int $endianess = 0;

    public int $tris = 0;

    public int $pstart = 0;

    public int $pstop = 0;

    public int $pidle = 0;

    public int $gpioh = 0;

    public int $trish = 0;

    public int $bitbang = 0;

    public int $tx = 0;

    public int $rx = 0;

    public int $txrx = 0;

    public int $tack = 0;

    public int $rack = 0;

    /**
     * BITBANG pin direction mask (1 = output, 0 = input).
     * Initialised to all-output to match the ftdi_set_bitmode(0xFF, 1) call in openIndex().
     */
    public int $bitbangDirection = 0xFF;
}
