<?php

namespace App\Models;

use Database\Factories\PublicationTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['name', 'slug', 'description', 'sort_order', 'is_active'])]
class PublicationType extends MasterData
{
    /** @use HasFactory<PublicationTypeFactory> */
    use HasFactory;
}
