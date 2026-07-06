<?php

declare(strict_types=1);

namespace Microscrap\Bindings\MPSSE\Enums;

use Microscrap\Bindings\FTDI\Enums\FtdiProductId;
use Microscrap\Bindings\FTDI\Enums\FtdiVendorId;
use Microscrap\Bindings\MPSSE\MPSSE;

/**
 * USB devices supported by libmpsse ({@see MPSSE::openSupported()}).
 */
enum MpsseSupportedDevice: string
{
    case FT2232HL_A = 'ft2232hl-a';
    case FT2232HL_B = 'ft2232hl-b';

    case FT232H = 'ft232h';

    public function vendorId(): int
    {
        return match ($this) {
            default => FtdiVendorId::FTDI->value,
        };
    }

    public function productId(): int
    {
        return match ($this) {
            self::FT2232HL_A, self::FT2232HL_B => FtdiProductId::FT2232HL->value,
            self::FT232H => FtdiProductId::FT232H->value,
        };
    }

    public function ftdiVendor(): ?FtdiVendorId
    {
        return $this->vendorId() === FtdiVendorId::FTDI->value
            ? FtdiVendorId::FTDI
            : null;
    }

    public function ftdiProduct(): ?FtdiProductId
    {
        return match ($this) {
            self::FT2232HL_A, self::FT2232HL_B  => FtdiProductId::FT2232HL,
            self::FT232H => FtdiProductId::FT232H,
            default => null,
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::FT2232HL_A, self::FT2232HL_B => 'FT2232 Future Technology Devices International, Ltd',
            self::FT232H => 'FT232H Future Technology Devices International, Ltd'
        };
    }

    /** GPIO lines exposed in {@see MPSSEMode::GPIO} (GPIOL + GPIOH). */
    public function gpioLineCount(): int
    {
        return 12;
    }

    public static function tryFromVidPid(int $vid, int $pid): ?self
    {
        foreach (self::cases() as $device) {
            if ($device->vendorId() === $vid && $device->productId() === $pid) {
                return $device;
            }
        }

        return null;
    }

    /**
     * @return list<array{int, int, string}>
     */
    public static function toSupportedDevicesTable(): array
    {
        $rows = [];
        foreach (self::cases() as $device) {
            $rows[] = [$device->vendorId(), $device->productId(), $device->description()];
        }

        return $rows;
    }

    public function interface(): MPSSEInterface
    {
        return match ($this) {
            self::FT2232HL_A, self::FT232H => MPSSEInterface::IFACE_A,
            self::FT2232HL_B => MPSSEInterface::IFACE_B,
            default => MPSSEInterface::IFACE_ANY,
        };
    }
}
