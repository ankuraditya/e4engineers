<?php

namespace App\Enums;

enum BookStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
    case Discontinued = 'discontinued';
}
