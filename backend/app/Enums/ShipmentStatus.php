<?php

namespace App\Enums;

enum ShipmentStatus: string
{
    case Pending = 'pending';
    case Booking = 'booking';
    case Booked = 'booked';
    case AwbAssigned = 'awb_assigned';
    case PickupScheduled = 'pickup_scheduled';
    case PickedUp = 'picked_up';
    case InTransit = 'in_transit';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case DeliveryFailed = 'delivery_failed';
    case RtoInitiated = 'rto_initiated';
    case RtoInTransit = 'rto_in_transit';
    case RtoDelivered = 'rto_delivered';
    case Cancelled = 'cancelled';
    case BookingFailed = 'booking_failed';
    case Unknown = 'unknown';
}
