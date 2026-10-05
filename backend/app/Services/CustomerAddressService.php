<?php

namespace App\Services;

use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CustomerAddressService
{
    public function create(User $user, array $data): CustomerAddress
    {
        return DB::transaction(function () use ($user, $data) {
            $data['is_default'] = ($data['is_default'] ?? false) || ! $user->addresses()->exists();
            if ($data['is_default']) {
                $user->addresses()->update(['is_default' => false]);
            }

            return $user->addresses()->create($data);
        });
    }

    public function update(User $user, CustomerAddress $address, array $data): CustomerAddress
    {
        return DB::transaction(function () use ($user, $address, $data) {
            if ($data['is_default'] ?? false) {
                $user->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
            }$address->update($data);

            return $address->refresh();
        });
    }

    public function setDefault(User $user, CustomerAddress $address): CustomerAddress
    {
        return DB::transaction(function () use ($user, $address) {
            $user->addresses()->update(['is_default' => false]);
            $address->update(['is_default' => true]);

            return $address->refresh();
        });
    }

    public function delete(User $user, CustomerAddress $address): void
    {
        DB::transaction(function () use ($user, $address) {
            $wasDefault = $address->is_default;
            $address->delete();
            if ($wasDefault) {
                $user->addresses()->whereNull('deleted_at')->latest('updated_at')->first()?->update(['is_default' => true]);
            }
        });
    }
}
