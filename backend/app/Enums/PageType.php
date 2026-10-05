<?php

namespace App\Enums;

enum PageType: string
{
    case Standard = 'standard';
    case Legal = 'legal';
    case System = 'system';
    case Landing = 'landing';
}
