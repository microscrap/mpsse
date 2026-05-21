<?php

namespace Microscrap\Bindings\MPSSE\Enums;

enum MPSSEPin: int
{
    case SK = 1;
    case DO = 2;
    case DI = 4;
    case CS = 8;
    case GPIO0 = 16;
    case GPIO1 = 32;
    case GPIO2 = 64;
    case GPIO3 = 128;
}
