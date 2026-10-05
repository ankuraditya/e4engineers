<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PaymentProviderSeeder::class,
            AuthorizationSeeder::class,
            EngineeringDisciplinesSeeder::class,
            CourseLevelsSeeder::class,
            ResourceTypesSeeder::class,
            PublicationTypesSeeder::class,
            CmsPagesSeeder::class,
            WebsiteSettingsSeeder::class,
            RemoveDemoContentSeeder::class,
            LegacyWordPressArticlesSeeder::class,
            LegacyWordPressCourseSeeder::class,
            LegacyWordPressAboutSeeder::class,
            ShippingProviderSeeder::class,
            NotificationTemplateSeeder::class,
        ]);
    }
}
