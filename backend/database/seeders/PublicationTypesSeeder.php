<?php

namespace Database\Seeders;

use App\Models\PublicationType;
use Illuminate\Database\Seeder;

class PublicationTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $names = ['Journal', 'Technical Publication', 'E-book', 'Study Guide', 'Engineering Notes', 'Technical Document'];
        foreach ($names as $index => $name) {
            PublicationType::query()->firstOrCreate(['slug' => str($name)->slug()], ['name' => $name, 'sort_order' => $index + 1, 'is_active' => true]);
        }
    }
}
