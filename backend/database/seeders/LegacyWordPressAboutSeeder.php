<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class LegacyWordPressAboutSeeder extends Seeder
{
    public function run(): void
    {
        $source = json_decode(file_get_contents(__DIR__.'/legacy-wordpress-about.json'), true, 512, JSON_THROW_ON_ERROR);
        $paragraphs = array_map(
            fn (string $text): string => '<p>'.htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8').'</p>',
            $source['paragraphs'],
        );

        if ($paragraphs) {
            Page::where('slug', 'about')->whereNull('content')->update([
                'content' => implode('', $paragraphs),
                'excerpt' => 'Engineering education with a focus on strong fundamentals and practical understanding.',
            ]);
        }
    }
}
