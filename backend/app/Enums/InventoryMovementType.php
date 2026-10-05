<?php

namespace App\Enums;

enum InventoryMovementType: string
{
    case InitialStock = 'initial_stock';
    case PurchaseReceipt = 'purchase_receipt';
    case ManualIncrease = 'manual_increase';
    case ManualDecrease = 'manual_decrease';
    case Adjustment = 'adjustment';
    case Sale = 'sale';
    case CancellationRestore = 'cancellation_restore';
    case ReturnRestore = 'return_restore';
    case Damage = 'damage';
    case Loss = 'loss';
    case Reservation = 'reservation';
    case ReservationRelease = 'reservation_release';
}
