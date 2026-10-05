<?php

namespace Database\Seeders;

use App\Models\WebsiteSetting;
use Illuminate\Database\Seeder;

class WebsiteSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            'site_name' => 'E4ENGINEERS',
            'site_tagline' => 'Engineering knowledge, connected.',
            'publications_enabled' => '1',
            'default_currency' => 'INR',
            'default_timezone' => 'Asia/Kolkata',
            'footer_tagline' => 'Engineering knowledge for a brighter tomorrow.',
            'copyright_text' => '© 2026 E4ENGINEERS. All rights reserved.',
            'default_robots' => 'index,follow',
        ];
        foreach ($settings as $key => $value) {
            $definition = config("cms.setting_keys.{$key}");
            WebsiteSetting::query()->firstOrCreate(['key' => $key], ['value' => $value, 'group' => $definition['group'], 'type' => $definition['type'], 'is_public' => $definition['public']]);
        }
    }
}
