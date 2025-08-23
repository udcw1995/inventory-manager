<?php

namespace App\Enums;

enum BottleMovementType: string
{
    case DELIVERED = 'DELIVERED';
    case RETURNED = 'RETURNED';
    case DAMAGED = 'DAMAGED';
    case LOST = 'LOST';
    case ADJUSTMENT = 'ADJUSTMENT';
}
