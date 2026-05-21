<?php

declare(strict_types=1);

namespace Microscrap\Bindings\MPSSE\Enums;

/**
 * Every command opcode that can be sent to the MPSSE engine.
 *
 * Values are taken verbatim from FTDI AN_108 (MPSSE Basics) and the
 * libmpsse {@code enum mpsse_commands} / support.h macros.
 *
 * {@see https://github.com/devttys0/libmpsse}
 */
enum MPSSECommand: int
{
    /** Drive ADBUS[7:0] (low byte: SCK/DO/DI/CS/GPIO0–3) to the given state */
    case SET_BITS_LOW = 0x80;

    /** Read current state of ADBUS[7:0] into the USB read buffer */
    case GET_BITS_LOW = 0x81;

    /** Drive ACBUS[7:0] (high byte: GPIOH0–7) to the given state */
    case SET_BITS_HIGH = 0x82;

    /** Read current state of ACBUS[7:0] into the USB read buffer */
    case GET_BITS_HIGH = 0x83;

    /** Enable internal loopback (TDI → TDO inside the chip) */
    case LOOPBACK_START = 0x84;

    /** Disable internal loopback */
    case LOOPBACK_END = 0x85;

    /** Set TCK/SK divisor — followed by two-byte little-endian divisor value */
    case TCK_DIVISOR = 0x86;

    /** Flush the USB TX buffer immediately */
    case SEND_IMMEDIATE = 0x87;

    /** Enable 60 MHz base clock (÷5 divider OFF — FT232H / FT2232H only) */
    case TCK_X5 = 0x8A;

    /** Enable 12 MHz base clock (÷5 divider ON — compatible mode) */
    case TCK_D5 = 0x8B;

    /** Enable 3-phase data clocking (required for I²C) */
    case ENABLE_3_PHASE_CLOCK = 0x8C;

    /** Disable 3-phase data clocking */
    case DISABLE_3_PHASE_CLOCK = 0x8D;

    /** Clock out N bits with no data transfer (TDI/TDO ignored) */
    case CLOCK_N_CYCLES = 0x8E;

    /** Clock out N × 8 bits with no data transfer */
    case CLOCK_N8_CYCLES = 0x8F;

    /** Wait until GPIOL1 (JTAG RTCK) goes high, then continue */
    case PULSE_CLOCK_IO_HIGH = 0x94;

    /** Wait until GPIOL1 (JTAG RTCK) goes low, then continue */
    case PULSE_CLOCK_IO_LOW = 0x95;

    /** Enable adaptive clocking (uses RTCK feedback) */
    case ENABLE_ADAPTIVE_CLOCK = 0x96;

    /** Disable adaptive clocking */
    case DISABLE_ADAPTIVE_CLOCK = 0x97;

    /** Clock N × 8 bits while waiting for GPIOL1 high */
    case CLOCK_N8_CYCLES_IO_HIGH = 0x9C;

    /** Clock N × 8 bits while waiting for GPIOL1 low */
    case CLOCK_N8_CYCLES_IO_LOW = 0x9D;

    /** Set all I/O pins to tristate (FT232H only) — followed by 0xFF 0xFF */
    case TRISTATE_IO = 0x9E;

    /** Sent back by the chip when it does not recognise a command */
    case INVALID_COMMAND = 0xAB;
}
