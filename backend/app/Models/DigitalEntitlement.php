<?php

namespace App\Models;

use App\Enums\EntitlementSource;
use App\Enums\EntitlementStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DigitalEntitlement extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['status' => EntitlementStatus::class, 'source_type' => EntitlementSource::class, 'granted_at' => 'datetime', 'expires_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entitleable(): MorphTo
    {
        return $this->morphTo();
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', 'active')->whereNull('revoked_at');
    }

    public function scopeValid(Builder $q): Builder
    {
        return $q->active()->where(fn ($x) => $x->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function scopeForUser(Builder $q, User|int $u): Builder
    {
        return $q->where('user_id', $u instanceof User ? $u->id : $u);
    }

    public function scopeForEntitleable(Builder $q, Model $m): Builder
    {
        return $q->whereMorphedTo('entitleable', $m);
    }
}
