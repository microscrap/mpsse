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
    case FT232H = 'ft232h';
    case FT2232H_A = 'ft2232hl-a';
    case FT2232H_B = 'ft2232hl-b';
    case FT4232H_A = 'ft4232hl-a';
    case FT4232H_B = 'ft4232hl-b';
    case FT4232H_C = 'ft4232hl-c';
    case FT4232H_D = 'ft4232hl-d';


    public function vendorId(): int
    {
        return match ($this) {
            default => FtdiVendorId::FTDI->value,
        };
    }

    public function productId(): int
    {
        return match ($this) {
            self::FT2232H_A, self::FT2232H_B => FtdiProductId::FT2232H->value,
            self::FT4232H_A, self::FT4232H_B, self::FT4232H_C, self::FT4232H_D => FtdiProductId::FT4232H->value,
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
            self::FT2232H_A, self::FT2232H_B  => FtdiProductId::FT2232H,
            self::FT232H => FtdiProductId::FT232H,
            default => null,
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::FT4232H_A, self::FT4232H_B, self::FT4232H_C, self::FT4232H_D => 'FT4232 Future Technology Devices International, Ltd',
            self::FT2232H_A, self::FT2232H_B => 'FT2232 Future Technology Devices International, Ltd',
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
            self::FT4232H_A, self::FT2232H_A, self::FT232H => MPSSEInterface::IFACE_A,
            self::FT4232H_B, self::FT2232H_B => MPSSEInterface::IFACE_B,
            self::FT4232H_C => MPSSEInterface::IFACE_C,
            self::FT4232H_D => MPSSEInterface::IFACE_D,

            default => MPSSEInterface::IFACE_ANY,
        };
    }
}
