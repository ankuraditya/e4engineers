<?php

namespace Database\Factories;

use App\Models\DigitalDownloadLog;
use App\Models\DigitalResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DigitalDownloadLogFactory extends Factory
{
    protected $model = DigitalDownloadLog::class;

    public function definition(): array
    {
        return ['user_id' => User::factory(), 'downloadable_type' => 'resource', 'downloadable_id' => DigitalResource::factory(), 'ip_address' => '127.0.0.1', 'downloaded_at' => now()];
    }
}
