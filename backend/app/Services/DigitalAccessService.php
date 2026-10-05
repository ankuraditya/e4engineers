<?php

namespace App\Services;

use App\Models\DigitalEntitlement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final class DigitalAccessService
{
    public function decision(?User $user, Model $content): array
    {
        $type = $content->access_type->value;
        $published = $content->status->value === 'published' && ! $content->trashed();
        $file = (bool) $content->file_media_id;
        if (! $published) {
            return ['allowed' => false, 'reason' => 'unavailable', 'requires_login' => false, 'requires_purchase' => false, 'entitlement' => null, 'has_file' => $file];
        }if ($type === 'free') {
            return ['allowed' => $file, 'reason' => $file ? 'allowed' : 'file_unavailable', 'requires_login' => false, 'requires_purchase' => false, 'entitlement' => null, 'has_file' => $file];
        }if (! $user) {
            return ['allowed' => false, 'reason' => 'authentication_required', 'requires_login' => true, 'requires_purchase' => $type === 'paid', 'entitlement' => null, 'has_file' => $file];
        }if ($type === 'login_required') {
            return ['allowed' => $file, 'reason' => $file ? 'allowed' : 'file_unavailable', 'requires_login' => true, 'requires_purchase' => false, 'entitlement' => null, 'has_file' => $file];
        }$entitlement = DigitalEntitlement::query()->forUser($user)->forEntitleable($content)->valid()->first();

        return ['allowed' => (bool) $entitlement && $file, 'reason' => ! $entitlement ? 'entitlement_required' : ($file ? 'allowed' : 'file_unavailable'), 'requires_login' => true, 'requires_purchase' => true, 'entitlement' => $entitlement, 'has_file' => $file];
    }
}
