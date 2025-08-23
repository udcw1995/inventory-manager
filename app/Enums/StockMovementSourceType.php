<?php

namespace App\Enums;

enum StockMovementSourceType: string
{
    case GRN = 'GRN';
    case INVOICE = 'INVOICE';
    case ADJUSTMENT = 'ADJUSTMENT';
}
