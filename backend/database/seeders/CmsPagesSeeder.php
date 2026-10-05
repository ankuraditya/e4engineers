<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class CmsPagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pages = [
            ['title' => 'Homepage', 'slug' => 'home', 'page_type' => 'system', 'template' => 'home'],
            ['title' => 'About E4ENGINEERS', 'slug' => 'about', 'page_type' => 'system', 'template' => 'about'],
            ['title' => 'Privacy Policy', 'slug' => 'privacy-policy', 'page_type' => 'legal', 'template' => 'legal'],
            ['title' => 'Terms of Use', 'slug' => 'terms', 'page_type' => 'legal', 'template' => 'legal'],
            ['title' => 'Shipping Policy', 'slug' => 'shipping-policy', 'page_type' => 'legal', 'template' => 'legal'],
            ['title' => 'Returns & Refunds', 'slug' => 'returns-refunds', 'page_type' => 'legal', 'template' => 'legal'],
        ];
        foreach ($pages as $page) {
            Page::query()->firstOrCreate(['slug' => $page['slug']], $page + ['status' => 'published', 'is_system' => true, 'published_at' => now()]);
        }
    }
}
