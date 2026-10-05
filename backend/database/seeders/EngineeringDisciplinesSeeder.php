<?php

namespace Database\Seeders;

use App\Models\EngineeringDiscipline;
use Illuminate\Database\Seeder;

class EngineeringDisciplinesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $names = ['Electrical Engineering', 'Mechanical Engineering', 'Civil Engineering', 'Mining Engineering', 'Electronics Engineering', 'Computer Science Engineering', 'Power Systems Engineering', 'Engineering Mathematics', 'Metallurgy Engineering'];
        foreach ($names as $index => $name) {
            EngineeringDiscipline::query()->firstOrCreate(['slug' => str($name)->slug()], ['name' => $name, 'sort_order' => $index + 1, 'is_active' => true]);
        }
    }
}
