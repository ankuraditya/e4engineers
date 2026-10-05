<?php

namespace Tests\Feature;

use App\Enums\InventoryMovementType;
use App\Models\Book;
use App\Models\Cart;
use App\Models\InventoryMovement;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class PersistentCartTest extends TestCase
{
    use RefreshDatabase;

    private function stocked(int $stock = 5, int $price = 499): Book
    {
        $book = Book::factory()->create(['selling_price' => $price]);
        app(InventoryService::class)->increase($book, $stock, InventoryMovementType::InitialStock, 'Fixture');

        return $book;
    }

    private function token(): string
    {
        return $this->getJson('/api/v1/cart')->json('data.meta.guest_cart_token');
    }

    public function test_empty_guest_cart_and_duplicate_add_use_server_price_without_inventory_mutation(): void
    {
        $book = $this->stocked();
        $token = $this->token();
        $before = InventoryMovement::count();
        $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/cart/items', ['book_id' => $book->id, 'quantity' => 1, 'price' => 1])->assertCreated()->assertJsonPath('data.summary.subtotal', '499.00');
        $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/cart/items', ['book_id' => $book->id, 'quantity' => 2])->assertCreated()->assertJsonPath('data.summary.quantity_count', 3)->assertJsonPath('data.items.0.current_price', '499.00');
        $this->assertSame($before, InventoryMovement::count());
        $this->assertSame(5, $book->inventory->fresh()->available_quantity);
    }

    public function test_stock_validation_update_remove_and_clear(): void
    {
        $book = $this->stocked(2);
        $token = $this->token();
        $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/cart/items', ['book_id' => $book->id, 'quantity' => 3])->assertUnprocessable();
        $added = $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/cart/items', ['book_id' => $book->id, 'quantity' => 1]);
        $item = $added->json('data.items.0.cart_item_id');
        $this->withHeader('X-Guest-Cart-Token', $token)->patchJson("/api/v1/cart/items/$item", ['quantity' => 0])->assertUnprocessable();
        $this->withHeader('X-Guest-Cart-Token', $token)->patchJson("/api/v1/cart/items/$item", ['quantity' => 2])->assertOk()->assertJsonPath('data.summary.quantity_count', 2);
        $this->withHeader('X-Guest-Cart-Token', $token)->deleteJson("/api/v1/cart/items/$item")->assertOk()->assertJsonPath('data.summary.item_count', 0);
        $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/cart/items', ['book_id' => $book->id, 'quantity' => 1]);
        $this->withHeader('X-Guest-Cart-Token', $token)->deleteJson('/api/v1/cart')->assertOk()->assertJsonPath('data.summary.item_count', 0);
    }

    public function test_revalidation_reports_price_stock_and_archived_issues(): void
    {
        $book = $this->stocked(2);
        $token = $this->token();
        $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/cart/items', ['book_id' => $book->id, 'quantity' => 2]);
        $book->update(['selling_price' => 450]);
        app(InventoryService::class)->adjust($book, 1, 'Changed');
        $this->withHeader('X-Guest-Cart-Token', $token)->getJson('/api/v1/cart')->assertOk()->assertJsonPath('data.checkout_allowed', false)->assertJsonPath('data.items.0.price_changed', true)->assertJsonPath('data.summary.subtotal', '900.00');
        $book->update(['status' => 'archived']);
        $this->withHeader('X-Guest-Cart-Token', $token)->getJson('/api/v1/cart')->assertJsonFragment(['code' => 'UNAVAILABLE_BOOK']);
    }

    public function test_guest_isolation_authenticated_idor_and_merge_cap_cleanup(): void
    {
        $book = $this->stocked(3);
        $token = $this->token();
        $item = $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/cart/items', ['book_id' => $book->id, 'quantity' => 2])->json('data.items.0.cart_item_id');
        $other = Str::random(64);
        $this->withHeader('X-Guest-Cart-Token', $other)->deleteJson("/api/v1/cart/items/$item")->assertNotFound();
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/api/v1/cart/items', ['book_id' => $book->id, 'quantity' => 2])->assertCreated();
        $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/cart/merge')->assertOk()->assertJsonPath('data.summary.quantity_count', 3)->assertJsonFragment(['code' => 'QUANTITY_CAPPED']);
        $this->assertDatabaseHas('carts', ['id' => Cart::whereNull('user_id')->first()->id, 'status' => 'converted', 'guest_token' => null]);
    }

    public function test_unique_constraints_and_locked_increment_prevent_duplicate_lines(): void
    {
        $book = $this->stocked(10);
        $token = $this->token();
        foreach ([1, 1, 1] as $quantity) {
            $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/cart/items', compact('quantity') + ['book_id' => $book->id])->assertCreated();
        }
        $this->assertDatabaseCount('cart_items', 1);
        $this->withHeader('X-Guest-Cart-Token', $token)->getJson('/api/v1/cart')->assertJsonPath('data.summary.quantity_count', 3);
    }
}
