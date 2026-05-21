<?php

declare(strict_types=1);

namespace Microscrap\Bindings\MPSSE\Enums;

use Fabrication\CWrappers\FTDI\Enums\FtdiProductId;
use Fabrication\CWrappers\FTDI\Enums\FtdiVendorId;

/**
 * USB devices supported by libmpsse ({@see \ScrapyardIO\Libraries\MPSSE\MPSSE::openSupported()}).
 */
enum MpsseSupportedDevice
{
    case FT2232;

    case FT4232;

    case FT232H;

    case BusBlasterV2ChannelA;

    case BusBlasterV2ChannelB;

    case TurtelizerJtagRs232AdapterA;

    case AmontecJtagKey;

    case TiaoMultiProtocolAdapter;

    case OlimexOpenOcdJtag;

    case OlimexOpenOcdJtagTiny;

    /** USB vendor ID (Olimex adapters use a non-FTDI VID). */
    private const OLIMEX_VENDOR_ID = 0x15BA;

    public function vendorId(): int
    {
        return match ($this) {
            self::OlimexOpenOcdJtag,
            self::OlimexOpenOcdJtagTiny => self::OLIMEX_VENDOR_ID,
            default => FtdiVendorId::FTDI->value,
        };
    }

    public function productId(): int
    {
        return match ($this) {
            self::FT2232 => FtdiProductId::FT2232->value,
            self::FT4232 => FtdiProductId::FT4232->value,
            self::FT232H => FtdiProductId::FT232H->value,
            self::BusBlasterV2ChannelA => FtdiProductId::BusBlasterV2ChannelA->value,
            self::BusBlasterV2ChannelB => FtdiProductId::BusBlasterV2ChannelB->value,
            self::TurtelizerJtagRs232AdapterA => FtdiProductId::TurtelizerJtagRs232AdapterA->value,
            self::AmontecJtagKey => FtdiProductId::AmontecJtagKey->value,
            self::TiaoMultiProtocolAdapter => FtdiProductId::TiaoMultiProtocolAdapter->value,
            self::OlimexOpenOcdJtag => 0x0003,
            self::OlimexOpenOcdJtagTiny => 0x0004,
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
            self::FT2232 => FtdiProductId::FT2232,
            self::FT4232 => FtdiProductId::FT4232,
            self::FT232H => FtdiProductId::FT232H,
            self::BusBlasterV2ChannelA => FtdiProductId::BusBlasterV2ChannelA,
            self::BusBlasterV2ChannelB => FtdiProductId::BusBlasterV2ChannelB,
            self::TurtelizerJtagRs232AdapterA => FtdiProductId::TurtelizerJtagRs232AdapterA,
            self::AmontecJtagKey => FtdiProductId::AmontecJtagKey,
            self::TiaoMultiProtocolAdapter => FtdiProductId::TiaoMultiProtocolAdapter,
            default => null,
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::FT2232 => 'FT2232 Future Technology Devices International, Ltd',
            self::FT4232 => 'FT4232 Future Technology Devices International, Ltd',
            self::FT232H => 'FT232H Future Technology Devices International, Ltd',
            self::BusBlasterV2ChannelA => 'Bus Blaster v2 (channel A)',
            self::BusBlasterV2ChannelB => 'Bus Blaster v2 (channel B)',
            self::TurtelizerJtagRs232AdapterA => 'Turtelizer JTAG/RS232 Adapter A',
            self::AmontecJtagKey => 'Amontec JTAGkey',
            self::TiaoMultiProtocolAdapter => 'TIAO Multi Protocol Adapter',
            self::OlimexOpenOcdJtag => 'Olimex Ltd. OpenOCD JTAG',
            self::OlimexOpenOcdJtagTiny => 'Olimex Ltd. OpenOCD JTAG TINY',
        };
    }

    /** GPIO lines exposed in {@see \ScrapyardIO\Libraries\MPSSE\Enums\MPSSEMode::GPIO} (GPIOL + GPIOH). */
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
}
