<?php

namespace Database\Seeders;

use App\Models\ResourceType;
use Illuminate\Database\Seeder;

class ResourceTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $names = ['Lecture Notes', 'Solved Question Papers', 'Technical Diagrams', 'Formula Sheets', 'Study Guides', 'Practice Materials', 'PDF Notes', 'Technical Documents'];
        foreach ($names as $index => $name) {
            ResourceType::query()->firstOrCreate(['slug' => str($name)->slug()], ['name' => $name, 'sort_order' => $index + 1, 'is_active' => true]);
        }
    }
}
