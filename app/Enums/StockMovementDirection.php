<?php

namespace App\Enums;

enum StockMovementDirection: string
{
    case IN = 'IN';
    case OUT = 'OUT';
    case ADJUST = 'ADJUST';
}
