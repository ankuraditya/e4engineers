<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\EngineeringDiscipline;
use App\Models\Publisher;
use App\Models\User;
use Database\Seeders\AuthorizationSeeder;
use Database\Seeders\EngineeringDisciplinesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BookCatalogueManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([AuthorizationSeeder::class, EngineeringDisciplinesSeeder::class]);
    }

    private function user(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u;
    }

    private function data(): array
    {
        $a = Author::firstOrCreate(['slug' => 'test-author'], ['name' => 'Test Author']);
        $p = Publisher::firstOrCreate(['slug' => 'test-publisher'], ['name' => 'Test Publisher']);

        return ['title' => 'Power System Design', 'description' => '<p>Complete reference</p>', 'engineering_discipline_id' => EngineeringDiscipline::first()->id, 'publisher_id' => $p->id, 'authors' => [['id' => $a->id, 'role' => 'author']], 'isbn' => '978-81-9400-123-4', 'mrp' => '599.00', 'selling_price' => '499.00', 'format' => 'paperback', 'status' => 'published', 'is_featured' => true, 'is_new_arrival' => true];
    }

    public function test_book_manager_creates_book_with_stable_identity_and_public_detail(): void
    {
        $admin = $this->user('book-manager');
        $created = $this->actingAs($admin, 'web')->postJson('/api/v1/admin/books', $this->data())->assertCreated()->assertJsonPath('data.sku', 'E4E-BOOK-0001')->assertJsonPath('data.isbn', '9788194001234');
        $id = $created->json('data.id');
        $this->patchJson("/api/v1/admin/books/$id", ['title' => 'Power System Design Revised'])->assertOk()->assertJsonPath('data.sku', 'E4E-BOOK-0001');
        $this->getJson('/api/v1/books/power-system-design')->assertOk()->assertJsonPath('data.book.saving_amount', '100.00')->assertJsonPath('data.book.inventory.status', 'OUT_OF_STOCK')->assertJsonPath('data.book.authors.0.name', 'Test Author');
    }

    public function test_admin_book_detail_contains_editable_fields_and_inventory(): void
    {
        $admin = $this->user('book-manager');
        $created = $this->actingAs($admin, 'web')->postJson('/api/v1/admin/books', $this->data())->assertCreated();
        $id = $created->json('data.id');

        $this->getJson("/api/v1/admin/books/$id")
            ->assertOk()
            ->assertJsonPath('data.description', '<p>Complete reference</p>')
            ->assertJsonPath('data.engineering_discipline_id', EngineeringDiscipline::first()->id)
            ->assertJsonPath('data.inventory.stock_quantity', 0)
            ->assertJsonPath('data.authors.0.name', 'Test Author');
    }

    public function test_public_list_filters_and_hides_drafts(): void
    {
        $admin = $this->user('book-manager');
        $this->actingAs($admin, 'web')->postJson('/api/v1/admin/books', $this->data());
        $draft = $this->data();
        $draft['title'] = 'Draft Book';
        $draft['isbn'] = null;
        $draft['status'] = 'draft';
        $this->postJson('/api/v1/admin/books', $draft)->assertCreated();
        $this->getJson('/api/v1/books?featured=1&min_price=400&max_price=500&sort=price-low-high')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Power System Design');
    }

    public function test_pricing_identity_and_permissions_are_validated(): void
    {
        $d = $this->data();
        $d['selling_price'] = 700;
        $this->actingAs($this->user('book-manager'), 'web')->postJson('/api/v1/admin/books', $d)->assertUnprocessable()->assertJsonValidationErrors('selling_price');
        $this->app['auth']->forgetGuards();
        $customer = $this->user('customer');
        $this->actingAs($customer, 'web')->getJson('/api/v1/admin/books')->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->actingAs($this->user('content-manager'), 'web')->getJson('/api/v1/admin/books')->assertOk();
    }

    public function test_duplicate_sku_and_isbn_are_rejected(): void
    {
        $admin = $this->user('book-manager');
        $d = $this->data();
        $d['sku'] = 'E4E-CUSTOM-1';
        $this->actingAs($admin, 'web')->postJson('/api/v1/admin/books', $d)->assertCreated();
        $d['title'] = 'Another';
        $this->postJson('/api/v1/admin/books', $d)->assertUnprocessable()->assertJsonValidationErrors(['sku', 'isbn']);
    }
}
