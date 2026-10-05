<?php

namespace App\Enums;

enum EntitlementSource: string
{
    case AdminGrant = 'admin_grant';
    case Migration = 'migration';
    case Purchase = 'purchase';
    case Promotion = 'promotion';
    case Bundle = 'bundle';
}
