<?php

namespace Database\Seeders;

use App\Models\Contributor;
use App\Models\EngineeringDiscipline;
use Illuminate\Database\Seeder;

class ContributorDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }
        $profiles = [
            ['Electrical Engineering', 'Prototype Electrical Contributor', 'Power systems and electrical machines'],
            ['Mechanical Engineering', 'Prototype Mechanical Contributor', 'Thermodynamics and rotating machinery'],
            ['Civil Engineering', 'Prototype Civil Contributor', 'Structural systems and resilient infrastructure'],
            ['Mining Engineering', 'Prototype Mining Contributor', 'Mine planning and equipment'],
            ['Electronics Engineering', 'Prototype Electronics Contributor', 'Embedded systems and instrumentation'],
            ['Computer Science Engineering', 'Prototype Computing Contributor', 'Computer architecture and distributed systems'],
            ['Power Systems Engineering', 'Prototype Energy Contributor', 'Renewable integration and protection'],
            ['Engineering Mathematics', 'Prototype Mathematics Contributor', 'Numerical methods and engineering modelling'],
        ];
        foreach ($profiles as $index => [$disciplineName, $name, $expertise]) {
            $discipline = EngineeringDiscipline::query()->where('name', $disciplineName)->first();
            if (! $discipline) {
                continue;
            }
            $contributor = Contributor::query()->firstOrCreate(['slug' => str($name)->slug()], ['name' => $name, 'designation' => 'Technical Contributor', 'qualification' => 'Prototype profile', 'short_bio' => 'Development-only contributor profile for validating the E4ENGINEERS directory.', 'biography' => '<p>Development-only profile. It does not represent a real person or affiliation.</p>', 'expertise_summary' => $expertise, 'sort_order' => $index + 1, 'is_featured' => $index < 3, 'is_active' => true, 'published_at' => now()]);
            if (! $contributor->disciplines()->whereKey($discipline->id)->exists()) {
                $contributor->disciplines()->attach($discipline->id, ['is_primary' => true, 'sort_order' => 0]);
            }
        }
    }
}
