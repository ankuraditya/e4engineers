<?php

namespace App\Models;

use Database\Factories\ResourceTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['name', 'slug', 'description', 'sort_order', 'is_active'])]
class ResourceType extends MasterData
{
    /** @use HasFactory<ResourceTypeFactory> */
    use HasFactory;
}
