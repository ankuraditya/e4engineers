<?php

namespace App\Actions\Account;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdateCustomerProfile
{
    public function execute(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            if ($user->email !== $data['email']) {
                $data['email_verified_at'] = null;
            }if ($user->mobile !== $data['mobile']) {
                $data['mobile_verified_at'] = null;
            }$user->forceFill($data)->save();

            return $user->refresh();
        });
    }
}
