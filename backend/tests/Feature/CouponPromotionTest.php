<?php

namespace Tests\Feature;

use App\Enums\InventoryMovementType;
use App\Models\Book;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\EngineeringDiscipline;
use App\Models\InventoryMovement;
use App\Models\User;
use App\Services\InventoryService;
use Database\Seeders\AuthorizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class CouponPromotionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthorizationSeeder::class);
    }

    private function book(int $price = 1000, array $attributes = []): Book
    {
        $book = Book::factory()->create($attributes + ['selling_price' => $price, 'mrp' => $price]);
        app(InventoryService::class)->increase($book, 20, InventoryMovementType::InitialStock, 'Fixture');

        return $book;
    }

    private function cart(Book $book, int $quantity = 1, ?string $token = null): array
    {
        $token ??= Str::random(64);
        $response = $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/cart/items', ['book_id' => $book->id, 'quantity' => $quantity]);

        return [$token, $response];
    }

    private function apply(string $token, string $code, array $extra = [])
    {
        return $this->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/cart/coupon', ['code' => $code] + $extra);
    }

    public function test_percentage_cap_case_normalization_and_client_tampering_are_server_authoritative(): void
    {
        $book = $this->book(1000);
        [$token] = $this->cart($book, 2);
        Coupon::factory()->create(['code' => 'SAVE10', 'discount_value' => 10, 'maximum_discount' => 150]);
        $before = InventoryMovement::count();
        $this->apply($token, ' save10 ', ['discount' => 9999])->assertOk()->assertJsonPath('data.summary.subtotal', '2000.00')->assertJsonPath('data.summary.coupon_discount', '150.00')->assertJsonPath('data.summary.discounted_subtotal', '1850.00')->assertJsonPath('data.coupon.code', 'SAVE10');
        $this->assertSame($before, InventoryMovement::count());
        $this->assertDatabaseCount('coupon_usages', 0);
    }

    public function test_fixed_discount_floor_and_minimum_subtotal(): void
    {
        $book = $this->book(600);
        [$token] = $this->cart($book);
        Coupon::factory()->create(['code' => 'TOOBIG', 'discount_type' => 'fixed', 'discount_value' => 1000]);
        $this->apply($token, 'TOOBIG')->assertJsonPath('data.summary.coupon_discount', '600.00')->assertJsonPath('data.summary.discounted_subtotal', '0.00');
        Coupon::factory()->create(['code' => 'MINIMUM', 'minimum_subtotal' => 999]);
        $this->apply($token, 'MINIMUM')->assertUnprocessable()->assertJsonPath('code', 'MINIMUM_SUBTOTAL_NOT_MET');
    }

    public function test_date_inactive_and_usage_limits_return_structured_codes(): void
    {
        $book = $this->book();
        [$token] = $this->cart($book);
        foreach ([['FUTURE', ['starts_at' => now()->addDay()], 'COUPON_NOT_STARTED'], ['EXPIRED', ['expires_at' => now()->subSecond()], 'COUPON_EXPIRED'], ['OFF', ['is_active' => false], 'COUPON_INACTIVE']] as [$code,$attrs,$reason]) {
            $coupon = Coupon::factory()->create(['code' => $code] + $attrs);
            $this->apply($token, $code)->assertUnprocessable()->assertJsonPath('code', $reason);
        }$limited = Coupon::factory()->create(['code' => 'USED', 'usage_limit' => 1]);
        CouponUsage::create(['coupon_id' => $limited->id, 'discount_amount' => 10, 'used_at' => now(), 'reference_type' => 'order', 'reference_id' => '1']);
        $this->apply($token, 'USED')->assertUnprocessable()->assertJsonPath('code', 'USAGE_LIMIT_REACHED');
    }

    public function test_book_category_and_discipline_scopes_discount_only_eligible_lines(): void
    {
        $category = Category::factory()->create(['context' => 'book']);
        $discipline = EngineeringDiscipline::factory()->create();
        $eligible = $this->book(500, ['category_id' => $category->id, 'engineering_discipline_id' => $discipline->id]);
        $other = $this->book(500);
        [$token] = $this->cart($eligible);
        $this->cart($other, 1, $token);
        $cases = [['BOOKONLY', 'specific_books', 'books', $eligible->id], ['CATEGORY', 'book_categories', 'categories', $category->id], ['DISCIPLINE', 'engineering_disciplines', 'disciplines', $discipline->id]];
        foreach ($cases as [$code,$scope,$relation,$id]) {
            $coupon = Coupon::factory()->create(['code' => $code, 'applies_to' => $scope]);
            $coupon->{$relation}()->attach($id);
            $this->apply($token, $code)->assertOk()->assertJsonPath('data.summary.eligible_coupon_subtotal', '500.00')->assertJsonPath('data.summary.coupon_discount', '50.00');
        }
    }

    public function test_coupon_auto_removes_after_cart_change_expiry_or_deactivation(): void
    {
        $book = $this->book(600);
        [$token,$added] = $this->cart($book, 2);
        $coupon = Coupon::factory()->create(['code' => 'MIN1000', 'minimum_subtotal' => 1000]);
        $this->apply($token, 'MIN1000')->assertOk();
        $item = $added->json('data.items.0.cart_item_id');
        $this->withHeader('X-Guest-Cart-Token', $token)->patchJson("/api/v1/cart/items/$item", ['quantity' => 1])->assertJsonPath('data.coupon', null)->assertJsonFragment(['code' => 'COUPON_REMOVED_MINIMUM_SUBTOTAL_NOT_MET']);
        $coupon->update(['minimum_subtotal' => null, 'expires_at' => now()->addMinute()]);
        $this->apply($token, 'MIN1000')->assertOk();
        $coupon->update(['is_active' => false]);
        $this->withHeader('X-Guest-Cart-Token', $token)->getJson('/api/v1/cart')->assertJsonPath('data.coupon', null)->assertJsonFragment(['code' => 'COUPON_REMOVED_COUPON_INACTIVE']);
    }

    public function test_customer_limit_guest_provisional_and_login_merge_revalidation(): void
    {
        $book = $this->book();
        [$token] = $this->cart($book);
        $coupon = Coupon::factory()->create(['code' => 'ONCE', 'per_customer_limit' => 1]);
        $this->apply($token, 'ONCE')->assertOk();
        $user = User::factory()->create();
        CouponUsage::create(['coupon_id' => $coupon->id, 'user_id' => $user->id, 'discount_amount' => 100, 'used_at' => now(), 'reference_type' => 'order', 'reference_id' => '2']);
        $this->actingAs($user)->withHeader('X-Guest-Cart-Token', $token)->postJson('/api/v1/cart/merge')->assertOk()->assertJsonPath('data.coupon', null)->assertJsonFragment(['code' => 'COUPON_REMOVED_CUSTOMER_LIMIT_REACHED']);
    }

    public function test_remove_coupon_and_idor_isolation(): void
    {
        $book = $this->book();
        [$owner] = $this->cart($book);
        Coupon::factory()->create(['code' => 'REMOVE']);
        $this->apply($owner, 'REMOVE')->assertOk();
        $other = Str::random(64);
        $this->withHeader('X-Guest-Cart-Token', $other)->deleteJson('/api/v1/cart/coupon')->assertOk();
        $this->withHeader('X-Guest-Cart-Token', $owner)->getJson('/api/v1/cart')->assertJsonPath('data.coupon.code', 'REMOVE');
        $this->withHeader('X-Guest-Cart-Token', $owner)->deleteJson('/api/v1/cart/coupon')->assertOk()->assertJsonPath('data.coupon', null);
    }

    public function test_admin_crud_validation_and_authorization(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $this->actingAs($customer)->getJson('/api/v1/admin/coupons')->assertForbidden();
        $admin = User::factory()->create();
        $admin->assignRole('administrator');
        $payload = ['code' => ' launch10 ', 'name' => 'Launch', 'discount_type' => 'percentage', 'discount_value' => 10, 'is_active' => true, 'applies_to' => 'all_books'];
        $created = $this->actingAs($admin)->postJson('/api/v1/admin/coupons', $payload)->assertCreated()->assertJsonPath('data.code', 'LAUNCH10');
        $id = $created->json('data.id');
        $this->patchJson("/api/v1/admin/coupons/$id/status", ['is_active' => false])->assertOk()->assertJsonPath('data.is_active',false);
        $this->deleteJson("/api/v1/admin/coupons/$id")->assertOk();
        $this->assertSoftDeleted('coupons',['id' => $id]);
    }
}
