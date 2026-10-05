<?php

namespace App\Enums;

enum ShippingStatus: string
{
    case NotCreated = 'not_created';
    case ReadyToShip = 'ready_to_ship';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
}
