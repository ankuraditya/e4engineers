<?php

namespace App\Enums;

enum AddressType: string
{
    case Home = 'home';
    case Office = 'office';
    case Other = 'other';
}
