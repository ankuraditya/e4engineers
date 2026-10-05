<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enquiry extends Model
{
    protected $guarded = [];

    public function notes(): HasMany
    {
        return $this->hasMany(EnquiryNote::class);
    }
}
