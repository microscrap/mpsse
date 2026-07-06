<?php

namespace Microscrap\Bindings\MPSSE\Concerns;

use Microscrap\Bindings\MPSSE\Enums\MPSSEClockRate;
use Microscrap\Bindings\MPSSE\Enums\MPSSEEndianness;
use Microscrap\Bindings\MPSSE\Enums\MPSSEInterface;
use Microscrap\Bindings\MPSSE\Enums\MpsseSupportedDevice;

trait MPSSETrait
{
    protected ?MpsseSupportedDevice $device = null;
    protected ?MPSSEInterface $interface = null;

    protected MPSSEEndianness $endianness = MPSSEEndianness::MSB;

    protected MPSSEClockRate $clock_rate = MPSSEClockRate::ONE_MHZ;

    public function endianness(MPSSEEndianness $endianness): static
    {
        $this->endianness = $endianness;

        return $this;
    }

    public function clockRate(MPSSEClockRate $clock_rate): static
    {
        $this->clock_rate = $clock_rate;

        return $this;
    }
}
