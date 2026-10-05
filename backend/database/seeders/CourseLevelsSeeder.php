<?php

namespace Database\Seeders;

use App\Models\CourseLevel;
use Illuminate\Database\Seeder;

class CourseLevelsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['Beginner', 'Intermediate', 'Advanced'] as $index => $name) {
            CourseLevel::query()->firstOrCreate(['slug' => str($name)->slug()], ['name' => $name, 'sort_order' => $index + 1, 'is_active' => true]);
        }
    }
}
