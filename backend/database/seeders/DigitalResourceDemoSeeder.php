<?php

namespace Database\Seeders;

use App\Models\DigitalResource;
use App\Models\EngineeringDiscipline;
use App\Models\ResourceType;
use Illuminate\Database\Seeder;

class DigitalResourceDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }
        $rows = [['Lecture Notes', 'Electrical Engineering', 'Electrical Engineering Lecture Notes', 'free'], ['Formula Sheets', 'Engineering Mathematics', 'Engineering Mathematics Formula Sheet', 'free'], ['Solved Question Papers', 'Civil Engineering', 'Structural Analysis Solved Question Paper', 'login_required'], ['Technical Diagrams', 'Power Systems Engineering', 'Power Systems Technical Diagrams', 'paid'], ['Study Guides', 'Mechanical Engineering', 'Mechanical Engineering Study Guide', 'free'], ['Practice Materials', 'Computer Science Engineering', 'Computer Systems Practice Materials', 'paid']];
        foreach ($rows as $index => [$type, $discipline, $title, $access]) {
            $resourceType = ResourceType::where('name', $type)->first();
            $engineering = EngineeringDiscipline::where('name', $discipline)->first();
            if (! $resourceType || ! $engineering) {
                continue;
            }
            DigitalResource::updateOrCreate(['slug' => str($title)->slug()], ['title' => $title, 'short_description' => 'Focused engineering study material for learning and revision.', 'description' => '<p>This development resource validates the study-resource workflow.</p>', 'resource_type_id' => $resourceType->id, 'engineering_discipline_id' => $engineering->id, 'access_type' => $access, 'price' => $access === 'paid' ? 299 : null, 'currency' => 'INR', 'file_format' => 'PDF', 'pages' => 24 + $index * 6, 'preview_type' => 'text', 'preview_content' => '<p>Safe sample preview content.</p>', 'status' => 'published', 'is_featured' => true, 'featured_order' => $index + 1, 'published_at' => now()->subDays($index)]);
        }
    }
}
