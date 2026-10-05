<?php

namespace Tests\Feature;

use App\Models\EngineeringDiscipline;
use App\Models\PublicationType;
use App\Models\User;
use Database\Seeders\AuthorizationSeeder;
use Database\Seeders\EngineeringDisciplinesSeeder;
use Database\Seeders\PublicationTypesSeeder;
use Database\Seeders\WebsiteSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PublicationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([AuthorizationSeeder::class, EngineeringDisciplinesSeeder::class, PublicationTypesSeeder::class, WebsiteSettingsSeeder::class]);
    }

    private function user(string $r): User
    {
        $u = User::factory()->create();
        $u->assignRole($r);

        return $u;
    }

    private function data(): array
    {
        return ['title' => 'Test Engineering Journal', 'description' => '<p>Safe issue</p>', 'publication_type_id' => PublicationType::first()->id, 'engineering_discipline_id' => EngineeringDiscipline::first()->id, 'access_type' => 'free', 'preview_type' => 'text', 'preview_content' => '<p>Preview</p>', 'status' => 'published'];
    }

    public function test_publication_manager_creates_and_public_reads_preview(): void
    {
        $id = $this->actingAs($this->user('publication-manager'), 'web')->postJson('/api/v1/admin/publications', $this->data())->assertCreated()->assertJsonPath('data.slug', 'test-engineering-journal')->json('data.id');
        $this->getJson('/api/v1/publications/test-engineering-journal')->assertOk()->assertJsonPath('data.publication.download_ready', false)->assertJsonPath('data.publication.preview.type', 'text');
    }

    public function test_paid_publication_requires_positive_price(): void
    {
        $d = $this->data();
        $d['access_type'] = 'paid';
        $d['price'] = 0;
        $this->actingAs($this->user('publication-manager'), 'web')->postJson('/api/v1/admin/publications', $d)->assertUnprocessable()->assertJsonValidationErrors('price');
    }

    public function test_public_filters_and_permissions(): void
    {
        $this->actingAs($this->user('publication-manager'), 'web')->postJson('/api/v1/admin/publications', $this->data());
        $this->getJson('/api/v1/publications?access=free')->assertOk()->assertJsonCount(1, 'data');
        $this->app['auth']->forgetGuards();
        $this->actingAs($this->user('content-manager'), 'web')->getJson('/api/v1/admin/publications')->assertOk();
        $this->postJson('/api/v1/admin/publications', $this->data())->assertForbidden();
    }

    public function test_global_visibility_switch_hides_publications_but_keeps_admin_records(): void
    {
        $administrator = $this->user('administrator');
        $this->actingAs($administrator, 'web')->postJson('/api/v1/admin/publications', $this->data())->assertCreated();
        $this->getJson('/api/v1/publications')->assertOk()->assertJsonCount(1, 'data');
        $this->putJson('/api/v1/admin/settings', ['settings' => [['key' => 'publications_enabled', 'value' => '0']]])->assertOk();
        $this->getJson('/api/v1/settings/public')->assertJsonPath('data.publications_enabled', '0');
        $this->getJson('/api/v1/publications')->assertNotFound();
        $this->getJson('/api/v1/publications/test-engineering-journal')->assertNotFound();
        $this->getJson('/api/v1/admin/publications')->assertOk()->assertJsonCount(1, 'data');
        $this->putJson('/api/v1/admin/settings', ['settings' => [['key' => 'publications_enabled', 'value' => '1']]])->assertOk();
        $this->getJson('/api/v1/publications')->assertOk()->assertJsonCount(1, 'data');
    }
}
