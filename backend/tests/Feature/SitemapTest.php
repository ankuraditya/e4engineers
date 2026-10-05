<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Book;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_tracks_published_content_and_respects_noindex(): void
    {
        $this->seed(DatabaseSeeder::class);
        $response = $this->get('/api/v1/sitemap.xml')->assertOk();
        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));
        $this->assertSame(14, substr_count($response->getContent(), '<url>'));
        $this->assertStringNotContainsString('/admin/', $response->getContent());
        $this->assertStringNotContainsString('/books/', $response->getContent());

        $book = Book::factory()->create();
        $book->seo()->create(['robots' => 'noindex,follow']);
        $this->assertStringNotContainsString('/books/'.$book->slug.'/', $this->get('/api/v1/sitemap.xml')->getContent());

        $book->seo->update(['robots' => 'index,follow']);
        $this->assertStringContainsString('/books/'.$book->slug.'/', $this->get('/api/v1/sitemap.xml')->getContent());

        $article = Article::firstOrFail();
        $article->seo()->create(['robots' => 'noindex,follow']);
        $this->assertStringNotContainsString('/articles/'.$article->slug.'/', $this->get('/api/v1/sitemap.xml')->getContent());
    }
}
