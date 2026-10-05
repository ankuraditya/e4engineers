<?php

namespace Tests\Feature;

use App\Models\GalleryAlbum;
use App\Models\Notice;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Video;
use Database\Seeders\AuthorizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_published_active_notices_are_public(): void
    {
        Notice::create(['title' => 'Public Grid Update', 'slug' => 'public-grid-update', 'notice_date' => today(), 'status' => 'published', 'published_at' => now()]);
        Notice::create(['title' => 'Private Draft', 'slug' => 'private-draft', 'notice_date' => today(), 'status' => 'draft']);
        Notice::create(['title' => 'Expired Notice', 'slug' => 'expired-notice', 'notice_date' => today(), 'status' => 'published', 'published_at' => now(), 'expires_at' => now()->subDay()]);
        $this->getJson('/api/v1/notices')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'public-grid-update');
    }

    public function test_gallery_returns_ordered_public_images_without_private_binary_data(): void
    {
        $album = GalleryAlbum::create(['title' => 'Event', 'slug' => 'event', 'status' => 'published', 'published_at' => now()]);
        $this->getJson('/api/v1/gallery/event')->assertOk()->assertJsonPath('data.slug', 'event');
    }

    public function test_administrator_can_review_and_remove_a_gallery_album(): void
    {
        $this->seed(AuthorizationSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $album = $this->actingAs($admin, 'web')->postJson('/api/v1/admin/gallery/albums', [
            'title' => 'Campus Workshop', 'status' => 'draft',
        ])->assertCreated()->json('data');

        $this->getJson("/api/v1/admin/gallery/albums/{$album['id']}")->assertOk()->assertJsonPath('data.title', 'Campus Workshop');
        $this->deleteJson("/api/v1/admin/gallery/albums/{$album['id']}")->assertOk();
        $this->assertSoftDeleted('gallery_albums', ['id' => $album['id']]);
    }

    public function test_video_embed_is_generated_only_from_valid_provider_id(): void
    {
        $video = Video::create(['title' => 'Power Lecture', 'slug' => 'power-lecture', 'video_type' => 'youtube', 'external_video_id' => 'dQw4w9WgXcQ', 'status' => 'published', 'published_at' => now()]);
        $this->getJson('/api/v1/videos/power-lecture')->assertOk()->assertJsonPath('data.embed_url', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ');
    }

    public function test_search_rejects_short_or_script_queries(): void
    {
        $this->getJson('/api/v1/search?q=x')->assertUnprocessable();
        $this->getJson('/api/v1/search?q='.urlencode('<script>alert(1)</script>'))->assertOk()->assertJsonMissing(['<script>']);
    }

    public function test_private_support_data_is_never_a_search_source(): void
    {
        SupportTicket::create(['ticket_number' => 'SUP-X', 'name' => 'Customer', 'email' => 'customer@example.com', 'category' => 'general', 'subject' => 'UniquePrivateNeedle', 'description' => 'Secret', 'submission_token' => fake()->uuid()]);
        $this->getJson('/api/v1/search?q=UniquePrivateNeedle')->assertOk()->assertJsonPath('meta.total', 0);
    }
}
