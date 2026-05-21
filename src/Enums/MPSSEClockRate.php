<?php

namespace Microscrap\Bindings\MPSSE\Enums;

enum MPSSEClockRate: int
{
    case ONE_HUNDRED_KHZ = 100_000;
    case FOUR_HUNDRED_KHZ = 400_000;
    case ONE_MHZ = 1_000_000;
    case TWO_MHZ = 2_000_000;
    case FIVE_MHZ = 5_000_000;
    case SIX_MHZ = 6_000_000;
    case TEN_MHZ = 10_000_000;
    case TWELVE_MHZ = 12_000_000;
    case FIFTEEN_MHZ = 15_000_000;
    case THIRTY_MHZ = 30_000_000;
    case SIXTY_MHZ = 60_000_000;
}
