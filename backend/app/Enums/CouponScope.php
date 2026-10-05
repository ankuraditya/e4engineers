<?php

namespace App\Enums;

enum CouponScope: string
{
    case AllBooks = 'all_books';
    case SpecificBooks = 'specific_books';
    case BookCategories = 'book_categories';
    case EngineeringDisciplines = 'engineering_disciplines';
}
