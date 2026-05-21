<?php

declare(strict_types=1);

namespace Microscrap\Bindings\MPSSE\Enums;

/**
 * Bit order for MPSSE data transfers, mirroring the {@code MSB}/{@code LSB}
 * defines in libmpsse {@code mpsse.h}.
 *
 * The value is the endianness byte that is OR-ed into the MPSSE transfer
 * command opcode — 0x00 for MSB-first, 0x08 for LSB-first.
 */
enum MPSSEEndianness: int
{
    /** Most-significant bit first (default for SPI modes 0–3) */
    case MSB = 0x00;

    /** Least-significant bit first */
    case LSB = 0x08;
}
