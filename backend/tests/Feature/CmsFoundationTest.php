<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Media;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\User;
use App\Models\WebsiteSetting;
use Database\Seeders\AuthorizationSeeder;
use Database\Seeders\CmsPagesSeeder;
use Database\Seeders\WebsiteSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class CmsFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([AuthorizationSeeder::class, CmsPagesSeeder::class, WebsiteSettingsSeeder::class]);
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_public_pages_expose_published_content_and_hide_drafts(): void
    {
        $this->getJson('/api/v1/pages/about')->assertOk()->assertJsonPath('data.slug', 'about')->assertJsonMissingPath('data.status');
        Page::query()->where('slug', 'about')->update(['status' => 'draft']);
        cache()->forget('cms:v2:page:about');
        $this->getJson('/api/v1/pages/about')->assertNotFound();
    }

    public function test_content_manager_can_create_sanitized_page_and_slug_remains_stable(): void
    {
        $manager = $this->user('content-manager');
        $created = $this->actingAs($manager, 'web')->postJson('/api/v1/admin/pages', [
            'title' => 'Safety Information', 'page_type' => 'standard', 'status' => 'published',
            'content' => '<p onclick="bad()">Safe</p><script>alert(1)</script><a href="javascript:bad()">link</a>',
        ])->assertCreated()->assertJsonPath('data.slug', 'safety-information')->json('data');

        $this->patchJson("/api/v1/admin/pages/{$created['id']}", [
            'title' => 'Updated Safety Information', 'page_type' => 'standard', 'status' => 'published', 'content' => '<p>Updated</p>',
        ])->assertOk()->assertJsonPath('data.slug', 'safety-information');

        $stored = Page::findOrFail($created['id']);
        $this->assertStringNotContainsString('script', $stored->content);
        $this->assertStringNotContainsString('onclick', $stored->content);
        $this->assertStringNotContainsString('javascript:', $stored->content);
    }

    public function test_system_page_cannot_be_deleted_and_customer_cannot_manage_pages(): void
    {
        $administrator = $this->user('administrator');
        $about = Page::query()->where('slug', 'about')->firstOrFail();
        $this->actingAs($administrator, 'web')->deleteJson("/api/v1/admin/pages/{$about->id}")->assertStatus(409);

        $customer = $this->user('customer');
        $this->app['auth']->forgetGuards();
        $this->actingAs($customer, 'web')->getJson('/api/v1/admin/pages')->assertForbidden();
    }

    public function test_page_sections_are_ordered_filtered_and_invalidate_public_page_cache(): void
    {
        $manager = $this->user('content-manager');
        $about = Page::query()->where('slug', 'about')->firstOrFail();
        $first = $this->actingAs($manager, 'web')->postJson("/api/v1/admin/pages/{$about->id}/sections", ['section_key' => 'mission', 'heading' => 'Mission', 'sort_order' => 2])->assertCreated()->json('data');
        $second = $this->postJson("/api/v1/admin/pages/{$about->id}/sections", ['section_key' => 'introduction', 'heading' => 'Introduction', 'sort_order' => 1, 'is_active' => false])->assertCreated()->json('data');

        $this->getJson('/api/v1/pages/about')->assertJsonFragment(['section_key' => 'mission'])->assertJsonMissing(['section_key' => 'introduction']);
        $this->patchJson("/api/v1/admin/pages/{$about->id}/sections/reorder", ['items' => [['id' => $first['id'], 'sort_order' => 1], ['id' => $second['id'], 'sort_order' => 2]]])->assertOk();
        $this->assertSame(1, PageSection::findOrFail($first['id'])->sort_order);
    }

    public function test_public_settings_are_allowlisted_and_admin_update_invalidates_cache(): void
    {
        WebsiteSetting::create(['key' => 'internal_editor_note', 'group' => 'internal', 'type' => 'text', 'value' => 'secret note', 'is_public' => false]);
        $this->getJson('/api/v1/settings/public')->assertOk()->assertJsonPath('data.site_name', 'E4ENGINEERS')->assertJsonMissing(['internal_editor_note' => 'secret note']);

        $administrator = $this->user('administrator');
        $this->actingAs($administrator, 'web')->putJson('/api/v1/admin/settings', ['settings' => [['key' => 'site_name', 'value' => 'E4 Engineers Portal']]])->assertOk();
        $this->getJson('/api/v1/settings/public')->assertJsonPath('data.site_name', 'E4 Engineers Portal');
        $this->putJson('/api/v1/admin/settings', ['settings' => [['key' => 'unknown_key', 'value' => 'x']]])->assertUnprocessable();
    }

    public function test_social_links_validate_safe_urls_and_public_order_and_status(): void
    {
        $administrator = $this->user('administrator');
        $this->actingAs($administrator, 'web')->postJson('/api/v1/admin/social-links', ['platform' => 'linkedin', 'label' => 'LinkedIn', 'url' => 'javascript:alert(1)'])->assertUnprocessable();
        $active = $this->postJson('/api/v1/admin/social-links', ['platform' => 'youtube', 'label' => 'YouTube', 'url' => 'https://youtube.com/', 'sort_order' => 2])->assertCreated()->json('data');
        $inactive = $this->postJson('/api/v1/admin/social-links', ['platform' => 'rss', 'label' => 'RSS', 'url' => 'https://example.test/feed', 'sort_order' => 1, 'is_active' => false])->assertCreated()->json('data');
        $this->getJson('/api/v1/social-links')->assertJsonFragment(['id' => $active['id']])->assertJsonMissing(['id' => $inactive['id']]);
    }

    public function test_banner_public_api_enforces_active_date_window_and_order(): void
    {
        Banner::create(['name' => 'Future', 'placement' => 'homepage-hero', 'heading' => 'Future', 'sort_order' => 1, 'starts_at' => now()->addDay()]);
        Banner::create(['name' => 'Current', 'placement' => 'homepage-hero', 'heading' => 'Current', 'sort_order' => 2, 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay()]);
        Banner::create(['name' => 'Inactive', 'placement' => 'homepage-hero', 'is_active' => false]);
        $this->getJson('/api/v1/banners?placement=homepage-hero')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.heading', 'Current');
        $this->getJson('/api/v1/banners?placement=homepage-hero')->assertOk()->assertJsonPath('data.0.heading', 'Current');
    }

    public function test_media_upload_is_secure_portable_and_permission_protected(): void
    {
        Storage::fake('public');
        $manager = $this->user('content-manager');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        $response = $this->actingAs($manager, 'web')->post('/api/v1/admin/media', ['file' => UploadedFile::fake()->createWithContent('hero.png', $png), 'alt_text' => 'Engineering hero'], ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('data.width', 1);
        $media = Media::findOrFail($response->json('data.id'));
        Storage::disk('public')->assertExists($media->path);
        $this->assertStringContainsString('/storage/', $response->json('data.url'));

        $this->post('/api/v1/admin/media', ['file' => UploadedFile::fake()->create('malware.php', 10, 'application/x-php')], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->post('/api/v1/admin/media', ['file' => UploadedFile::fake()->create('large.pdf', 6000, 'application/pdf')], ['Accept' => 'application/json'])->assertUnprocessable();

        $customer = $this->user('customer');
        $this->app['auth']->forgetGuards();
        $this->actingAs($customer, 'web')->post('/api/v1/admin/media', ['file' => UploadedFile::fake()->createWithContent('x.png', $png)], ['Accept' => 'application/json'])->assertForbidden();
    }

    public function test_referenced_media_cannot_be_deleted(): void
    {
        Storage::fake('public');
        $administrator = $this->user('administrator');
        $media = Media::create(['disk' => 'public', 'path' => 'cms/test.jpg', 'filename' => 'test.jpg', 'original_name' => 'test.jpg', 'mime_type' => 'image/jpeg', 'extension' => 'jpg', 'size' => 10, 'uploaded_by' => $administrator->id]);
        Banner::create(['name' => 'Referenced', 'placement' => 'homepage-hero', 'desktop_media_id' => $media->id]);
        $this->actingAs($administrator, 'web')->deleteJson("/api/v1/admin/media/{$media->id}")->assertStatus(409);
    }

    public function test_page_seo_and_internal_redirect_validation_are_authorized(): void
    {
        $manager = $this->user('content-manager');
        $about = Page::query()->where('slug', 'about')->firstOrFail();
        $this->actingAs($manager, 'web')->putJson("/api/v1/admin/pages/{$about->id}/seo", ['meta_title' => 'About E4ENGINEERS', 'robots' => 'invalid'])->assertUnprocessable();
        $this->putJson("/api/v1/admin/pages/{$about->id}/seo", ['meta_title' => 'About E4ENGINEERS', 'canonical_url' => 'https://example.test/about', 'robots' => 'index,follow'])->assertOk();
        $this->getJson('/api/v1/pages/about')->assertJsonPath('data.seo.meta_title', 'About E4ENGINEERS');

        $administrator = $this->user('administrator');
        $this->app['auth']->forgetGuards();
        $this->actingAs($administrator, 'web')->postJson('/api/v1/admin/redirects', ['source_path' => '/old-about', 'target_path' => 'https://evil.test', 'status_code' => 301])->assertUnprocessable();
        $this->postJson('/api/v1/admin/redirects', ['source_path' => '/old-about', 'target_path' => '/about', 'status_code' => 301])->assertCreated();
    }
}
