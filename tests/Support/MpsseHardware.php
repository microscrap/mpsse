<?php

namespace DeptOfScrapyardRobotics\Tests\Support;

use Microscrap\Bindings\MPSSE\Enums\MPSSEClockRate;
use Microscrap\Bindings\MPSSE\Enums\MPSSEEndianness;
use Microscrap\Bindings\MPSSE\Enums\MPSSEInterface;
use Microscrap\Bindings\MPSSE\Enums\MPSSEMode;
use Microscrap\Bindings\MPSSE\Enums\MpsseSupportedDevice;
use Microscrap\Bindings\MPSSE\MPSSE;
use Microscrap\Bindings\MPSSE\MPSSEContext;

final class MpsseHardware
{
    public static function openFt232h(
        MPSSEMode $mode = MPSSEMode::SPI0,
        int $freq = MPSSEClockRate::ONE_MHZ->value,
        MPSSEEndianness $endianness = MPSSEEndianness::MSB,
    ): MPSSEContext {
        $context = MPSSE::openDevice(
            MpsseSupportedDevice::FT232H,
            $mode,
            $freq,
            $endianness,
            MPSSEInterface::IFACE_A,
        );

        if (! $context->open) {
            throw new \PHPUnit\Framework\SkippedWithMessageException(
                'FT232H not detected: '.MPSSE::errorString($context),
            );
        }

        return $context;
    }

    public static function close(MPSSEContext $context): void
    {
        if ($context->open) {
            MPSSE::close($context);
        }
    }

    /**
     * @template TReturn
     *
     * @param  callable(MPSSEContext): TReturn  $workflow
     * @return TReturn
     */
    public static function withFt232h(
        callable $workflow,
        MPSSEMode $mode = MPSSEMode::SPI0,
        int $freq = MPSSEClockRate::ONE_MHZ->value,
        MPSSEEndianness $endianness = MPSSEEndianness::MSB,
    ): mixed {
        $context = self::openFt232h($mode, $freq, $endianness);

        try {
            return $workflow($context);
        } finally {
            self::close($context);
        }
    }
}
