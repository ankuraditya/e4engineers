<?php

namespace Database\Factories;

use App\Models\DigitalEntitlement;
use App\Models\DigitalResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DigitalEntitlementFactory extends Factory
{
    protected $model = DigitalEntitlement::class;

    public function definition(): array
    {
        return ['user_id' => User::factory(), 'entitleable_type' => 'resource', 'entitleable_id' => DigitalResource::factory(), 'source_type' => 'admin_grant', 'granted_at' => now(), 'status' => 'active'];
    }

    public function active(): static
    {
        return $this->state(['status' => 'active', 'revoked_at' => null]);
    }

    public function revoked(): static
    {
        return $this->state(['status' => 'revoked', 'revoked_at' => now()]);
    }

    public function expired(): static
    {
        return $this->state(['status' => 'expired', 'expires_at' => now()->subDay()]);
    }
}
