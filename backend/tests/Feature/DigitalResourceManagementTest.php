<?php

namespace Tests\Feature;

use App\Models\DigitalResource;
use App\Models\EngineeringDiscipline;
use App\Models\Media;
use App\Models\ResourceType;
use App\Models\User;
use Database\Seeders\AuthorizationSeeder;
use Database\Seeders\EngineeringDisciplinesSeeder;
use Database\Seeders\ResourceTypesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class DigitalResourceManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([AuthorizationSeeder::class, EngineeringDisciplinesSeeder::class, ResourceTypesSeeder::class]);
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function data(array $overrides = []): array
    {
        return array_merge(['title' => 'Engineering Mathematics Formula Sheet', 'description' => '<p>Safe formulas</p><script>alert(1)</script>', 'resource_type_id' => ResourceType::first()->id, 'engineering_discipline_id' => EngineeringDiscipline::first()->id, 'access_type' => 'free', 'preview_type' => 'text', 'preview_content' => '<p>Sample</p>', 'status' => 'published', 'is_featured' => true], $overrides);
    }

    public function test_publication_manager_can_manage_and_public_api_hides_non_published_resources(): void
    {
        $manager = $this->user('publication-manager');
        $id = $this->actingAs($manager, 'web')->postJson('/api/v1/admin/resources', $this->data())->assertCreated()->assertJsonPath('data.slug', 'engineering-mathematics-formula-sheet')->json('data.id');
        $this->postJson('/api/v1/admin/resources', $this->data(['title' => 'Draft Notes', 'status' => 'draft']))->assertCreated();
        $this->getJson('/api/v1/resources')->assertOk()->assertJsonCount(1, 'data')->assertJsonMissing(['title' => 'Draft Notes']);
        $this->patchJson("/api/v1/admin/resources/{$id}/featured", ['is_featured' => false])->assertOk()->assertJsonPath('data.is_featured', false);
        $this->patchJson("/api/v1/admin/resources/{$id}/status", ['status' => 'archived'])->assertOk();
        $this->getJson('/api/v1/resources/engineering-mathematics-formula-sheet')->assertNotFound();
    }

    public function test_filters_detail_and_safe_access_metadata(): void
    {
        DigitalResource::factory()->published()->create(['title' => 'Electrical Notes', 'slug' => 'electrical-notes', 'resource_type_id' => ResourceType::first()->id, 'engineering_discipline_id' => EngineeringDiscipline::first()->id]);
        $slug = EngineeringDiscipline::first()->slug;
        $type = ResourceType::first()->slug;
        $this->getJson("/api/v1/resources?search=Electrical&discipline={$slug}&type={$type}&access=free")->assertOk()->assertJsonCount(1, 'data');
        $response = $this->getJson('/api/v1/resources/electrical-notes')->assertOk()->assertJsonPath('data.resource.access.download_available', false)->assertJsonPath('data.resource.type.slug', $type);
        $json = $response->getContent();
        $this->assertStringNotContainsString('storage/app/private', $json);
        $this->assertStringNotContainsString('file_media.path', $json);
        $this->assertStringNotContainsString('"disk"', $json);
    }

    public function test_paid_price_and_role_permissions_are_enforced(): void
    {
        $manager = $this->user('publication-manager');
        $this->actingAs($manager, 'web')->postJson('/api/v1/admin/resources', $this->data(['access_type' => 'paid', 'price' => 0]))->assertUnprocessable()->assertJsonValidationErrors('price');
        $this->postJson('/api/v1/admin/resources', $this->data(['access_type' => 'paid', 'price' => 499]))->assertUnprocessable()->assertJsonValidationErrors('file_media_id');
        $this->app['auth']->forgetGuards();
        $content = $this->user('content-manager');
        $this->actingAs($content, 'web')->getJson('/api/v1/admin/resources')->assertOk();
        $this->postJson('/api/v1/admin/resources', $this->data())->assertForbidden();
        $this->app['auth']->forgetGuards();
        $customer = $this->user('customer');
        $this->actingAs($customer, 'web')->getJson('/api/v1/admin/resources')->assertForbidden();
    }

    public function test_documented_login_required_filter_spelling_is_accepted(): void
    {
        DigitalResource::factory()->published()->loginRequired()->create();
        $this->getJson('/api/v1/resources?access=login-required')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_private_upload_never_returns_a_url_and_dangerous_files_are_rejected(): void
    {
        Storage::fake('private');
        $manager = $this->user('publication-manager');
        $upload = $this->actingAs($manager, 'web')->post('/api/v1/admin/media', ['storage' => 'private', 'file' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf')], ['Accept' => 'application/json'])->assertCreated()->assertJsonPath('data.url', null);
        $media = Media::findOrFail($upload->json('data.id'));
        $this->assertSame('private', $media->disk);
        Storage::disk('private')->assertExists($media->path);
        foreach (['php', 'exe', 'sh', 'js'] as $extension) {
            $this->post('/api/v1/admin/media', ['storage' => 'private', 'file' => UploadedFile::fake()->create("payload.{$extension}", 10)], ['Accept' => 'application/json'])->assertUnprocessable();
        }
    }
}
