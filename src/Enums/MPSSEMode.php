<?php

namespace Microscrap\Bindings\MPSSE\Enums;

enum MPSSEMode: int
{
    case SPI0 = 1;
    case SPI1 = 2;
    case SPI2 = 3;
    case SPI3 = 4;
    case I2C = 5;
    case GPIO = 6;
    case BITBANG = 7;
}
