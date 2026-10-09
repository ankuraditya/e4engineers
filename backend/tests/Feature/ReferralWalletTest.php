<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\Book;
use App\Models\Order;
use App\Models\User;
use App\Services\ReferralService;
use Database\Seeders\AuthorizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ReferralWalletTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->withHeader('Origin', 'http://localhost:4177');
        $this->seed(AuthorizationSeeder::class);
    }

    public function test_only_a_paid_book_buyer_can_share_a_referral_link(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web')->getJson('/api/v1/account/dashboard')->assertOk()->assertJsonPath('data.referral.eligible', false)->assertJsonPath('data.referral.code', null);

        $order = Order::factory()->create(['user_id' => $user->id, 'payment_status' => PaymentStatus::Paid]);
        $book = Book::factory()->create();
        $order->items()->create(['book_id' => $book->id, 'title' => $book->title, 'quantity' => 1, 'unit_price' => 499, 'line_total' => 499, 'product_snapshot' => []]);
        $this->actingAs($user, 'web')->getJson('/api/v1/account/dashboard')->assertOk()->assertJsonPath('data.referral.eligible', true);
        $this->assertNotNull($user->fresh()->referral_code);
    }

    public function test_withdrawal_reserves_balance_and_admin_rejection_restores_it_once(): void
    {
        $buyer = User::factory()->create();
        $this->actingAs($buyer, 'web')->postJson('/api/v1/account/referral-withdrawals', ['amount_rupees' => 100, 'upi_id' => 'student@upi'])->assertUnprocessable();
        DB::table('referral_wallet_entries')->insert(['user_id' => $buyer->id, 'amount_paise' => 10000, 'reason' => 'referral_reward', 'created_at' => now(), 'updated_at' => now()]);
        $id = $this->actingAs($buyer, 'web')->postJson('/api/v1/account/referral-withdrawals', ['amount_rupees' => 100, 'upi_id' => 'student@upi'])->assertOk()->json('data.id');
        $this->actingAs($buyer, 'web')->postJson('/api/v1/account/referral-withdrawals', ['amount_rupees' => 100, 'upi_id' => 'student@upi'])->assertUnprocessable();

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->app['auth']->forgetGuards();
        $this->actingAs($admin, 'web')->patchJson("/api/v1/admin/referral-withdrawals/{$id}", ['status' => 'rejected', 'admin_note' => 'Invalid UPI ID'])->assertOk();
        $this->actingAs($admin, 'web')->patchJson("/api/v1/admin/referral-withdrawals/{$id}", ['status' => 'rejected', 'admin_note' => 'Again'])->assertUnprocessable();
        $this->assertSame(10000, (int) DB::table('referral_wallet_entries')->where('user_id', $buyer->id)->sum('amount_paise'));
    }
}
