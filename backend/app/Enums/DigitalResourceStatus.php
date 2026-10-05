<?php

namespace App\Enums;

enum DigitalResourceStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
