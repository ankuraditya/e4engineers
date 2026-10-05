<?php

namespace App\Services;

use App\Enums\EntitlementSource;
use App\Enums\EntitlementStatus;
use App\Models\DigitalEntitlement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class EntitlementService
{
    public function grant(User $user, Model $content, EntitlementSource $source, ?string $reference = null, ?\DateTimeInterface $expires = null, ?User $actor = null, ?string $notes = null): DigitalEntitlement
    {
        return DB::transaction(function () use ($user, $content, $source, $reference, $expires, $actor, $notes) {
            $existing = DigitalEntitlement::query()->lockForUpdate()->forUser($user)->forEntitleable($content)->valid()->first();
            if ($existing) {
                return $existing;
            }if ($reference && ($found = DigitalEntitlement::where('source_type', $source->value)->where('source_reference', $reference)->first())) {
                return $found;
            }

return DigitalEntitlement::create(['user_id' => $user->id, 'entitleable_type' => $content->getMorphClass(), 'entitleable_id' => $content->getKey(), 'source_type' => $source, 'source_reference' => $reference, 'granted_at' => now(), 'expires_at' => $expires, 'status' => EntitlementStatus::Active, 'granted_by' => $actor?->id, 'notes' => $notes]);
        });
    }

    public function grantPurchasedAccess(User $user, Model $content, string $verifiedReference): DigitalEntitlement
    {
        return $this->grant($user, $content, EntitlementSource::Purchase, $verifiedReference);
    }

    public function revoke(DigitalEntitlement $e, User $actor, ?string $reason = null): DigitalEntitlement
    {
        $e->update(['status' => EntitlementStatus::Revoked, 'revoked_at' => now(), 'notes' => trim(($e->notes ? $e->notes."\n" : '').($reason ? "Revoked by {$actor->id}: {$reason}" : "Revoked by {$actor->id}"))]);

        return $e->refresh();
    }

    public function revokeByPurchaseReference(string $reference, User $actor, ?string $reason = null): int
    {
        $items = DigitalEntitlement::where('source_type', 'purchase')->where('source_reference', $reference)->valid()->get();
        foreach ($items as $item) {
            $this->revoke($item,$actor,$reason);
        }

return $items->count();
    }
}
