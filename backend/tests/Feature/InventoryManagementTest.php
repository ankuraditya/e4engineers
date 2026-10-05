<?php

namespace Tests\Feature;

use App\Enums\InventoryMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Book;
use App\Models\InventoryMovement;
use App\Models\User;
use App\Services\InventoryService;
use Database\Seeders\AuthorizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class InventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthorizationSeeder::class);
    }

    private function user(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u;
    }

    private function book(): Book
    {
        return Book::factory()->create();
    }

    public function test_inventory_defaults_and_status_formula(): void
    {
        $book = $this->book();
        $i = app(InventoryService::class)->initialize($book);
        $this->assertSame(0, $i->stock_quantity);
        $this->assertSame(0, $i->reserved_quantity);
        $this->assertSame('OUT_OF_STOCK', $i->status);
        app(InventoryService::class)->increase($book, 6, InventoryMovementType::InitialStock, 'Test');
        $this->assertSame('IN_STOCK', $i->refresh()->status);
        app(InventoryService::class)->decrease($book, 1, InventoryMovementType::ManualDecrease, 'Test');
        $this->assertSame('LOW_STOCK', $i->refresh()->status);
    }

    public function test_increase_decrease_adjustment_and_audit_actor(): void
    {
        $book = $this->book();
        $admin = $this->user('book-manager');
        $this->actingAs($admin, 'web')->postJson("/api/v1/admin/books/{$book->id}/inventory/increase", ['quantity' => 10, 'reason' => 'Receipt'])->assertOk()->assertJsonPath('data.stock_quantity', 10);
        $this->postJson("/api/v1/admin/books/{$book->id}/inventory/decrease", ['quantity' => 3, 'reason' => 'Damaged'])->assertOk()->assertJsonPath('data.stock_quantity', 7);
        $this->postJson("/api/v1/admin/books/{$book->id}/inventory/adjust", ['target_quantity' => 6, 'reason' => 'Count correction'])->assertOk()->assertJsonPath('data.stock_quantity', 6);
        $this->assertDatabaseHas('inventory_movements', ['book_id' => $book->id, 'quantity' => -1, 'quantity_before' => 7, 'quantity_after' => 6, 'created_by' => $admin->id]);
        $this->assertCount(3, InventoryMovement::all());
    }

    public function test_insufficient_stock_rolls_back_and_competing_deductions_never_go_negative(): void
    {
        $book = $this->book();
        $service = app(InventoryService::class);
        $service->initialize($book);
        $service->increase($book, 1, InventoryMovementType::InitialStock, 'Test');
        $service->decrease($book, 1, InventoryMovementType::ManualDecrease, 'First');
        try {
            $service->decrease($book, 1, InventoryMovementType::ManualDecrease, 'Second');
            $this->fail('Expected insufficient stock');
        } catch (InsufficientStockException) {
        }$this->assertSame(0, $book->inventory->fresh()->stock_quantity);
        $this->assertCount(2, InventoryMovement::all());
    }

    public function test_public_availability_filter_and_safe_shape_follow_cache_invalidation(): void
    {
        $book = $this->book();
        $service = app(InventoryService::class);
        $service->initialize($book);
        $this->getJson('/api/v1/books?availability=out-of-stock')->assertOk()->assertJsonCount(1, 'data')->assertJsonMissingPath('data.0.inventory.available_quantity')->assertJsonPath('data.0.inventory.status', 'OUT_OF_STOCK');
        $service->increase($book, 10, InventoryMovementType::InitialStock, 'Test');
        $this->getJson('/api/v1/books?availability=in-stock')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.inventory.status', 'IN_STOCK');
        $this->getJson('/api/v1/books?availability=out-of-stock')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_inventory_authorization_is_conservative(): void
    {
        $book = $this->book();
        $this->getJson('/api/v1/admin/inventory')->assertUnauthorized();
        $this->actingAs($this->user('customer'), 'web')->getJson('/api/v1/admin/inventory')->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->actingAs($this->user('order-manager'), 'web')->getJson('/api/v1/admin/inventory')->assertOk();
        $this->postJson("/api/v1/admin/books/{$book->id}/inventory/increase", ['quantity' => 1, 'reason' => 'No'])->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->actingAs($this->user('content-manager'), 'web')->postJson("/api/v1/admin/books/{$book->id}/inventory/increase", ['quantity' => 1, 'reason' => 'No'])->assertForbidden();
    }

    public function test_reserved_quantity_invariant_and_no_public_reservation_endpoint(): void
    {
        $book = $this->book();
        $service = app(InventoryService::class);
        $service->initialize($book);
        $service->increase($book, 2, InventoryMovementType::InitialStock, 'Test');
        $service->reserve($book, 2, 'future-order', '1');
        $this->assertSame(0, $book->inventory->fresh()->available_quantity);
        $this->expectException(InsufficientStockException::class);
        $service->reserve($book, 1);
    }
}
