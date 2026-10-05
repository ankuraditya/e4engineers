<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InternshipCertificate extends Model
{
    protected $guarded = [];

    protected $hidden = ['lookup_hash', 'file_path'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public static function lookupHash(string $mobile, string $dateOfBirth): string
    {
        return hash_hmac('sha256', $mobile.'|'.$dateOfBirth, (string) config('app.key'));
    }
}
