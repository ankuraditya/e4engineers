<?php

namespace App\Enums;

enum BookFormat: string
{
    case Paperback = 'paperback';
    case Hardcover = 'hardcover';
    case Spiral = 'spiral';
    case Other = 'other';
}
