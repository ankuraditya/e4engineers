<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use App\Enums\OrderStatus;
use App\Services\OrderStatusService;
use App\Services\ReferralService;
use Database\Seeders\AuthorizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ReferralProgramTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->withHeader('Origin', 'http://localhost:4177');
    }

    public function test_registration_attributes_a_valid_referral_and_rejects_unknown_codes(): void
    {
        $this->seed(AuthorizationSeeder::class);
        $referrer = User::factory()->create();
        $code = app(ReferralService::class)->codeFor($referrer);

        $payload = [
            'name' => 'Referred Student',
            'email' => 'student@example.com',
            'mobile' => '9876543210',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'referral_code' => strtolower($code),
        ];

        $this->postJson('/api/v1/auth/register', $payload)->assertCreated();
        $buyer = User::query()->where('email', 'student@example.com')->firstOrFail();
        $this->assertSame($referrer->id, $buyer->referred_by_user_id);
        $this->assertNotEmpty($buyer->referral_code);

        $this->postJson('/api/v1/auth/register', [...$payload, 'email' => 'other@example.com', 'mobile' => '9876543211', 'referral_code' => 'UNKNOWN'])->assertUnprocessable()->assertJsonValidationErrors('referral_code');
    }

    public function test_only_the_first_paid_order_credits_the_referrer_once(): void
    {
        $referrer = User::factory()->create();
        $buyer = User::factory()->create(['referred_by_user_id' => $referrer->id]);
        $unpaid = Order::factory()->create(['user_id' => $buyer->id]);
        $service = app(ReferralService::class);
        $service->awardForPaidOrder($unpaid);
        $this->assertSame(0, DB::table('referral_rewards')->count());

        $unpaid->update(['payment_status' => PaymentStatus::Paid]);
        $service->awardForPaidOrder($unpaid->refresh());
        $service->awardForPaidOrder($unpaid);
        $later = Order::factory()->create(['user_id' => $buyer->id, 'payment_status' => PaymentStatus::Paid, 'placed_at' => now()->addDay()]);
        $service->awardForPaidOrder($later);

        $this->assertSame(1, DB::table('referral_rewards')->count());
        $this->assertSame(10000, (int) DB::table('store_credit_entries')->where('user_id', $referrer->id)->sum('amount_paise'));
    }

    public function test_cancelling_a_qualifying_order_revokes_credit_and_allows_the_next_purchase(): void
    {
        $referrer = User::factory()->create();
        $buyer = User::factory()->create(['referred_by_user_id' => $referrer->id]);
        $first = Order::factory()->create(['user_id' => $buyer->id, 'payment_status' => PaymentStatus::Paid]);
        app(ReferralService::class)->awardForPaidOrder($first);

        app(OrderStatusService::class)->transition($first, OrderStatus::Cancelled, $referrer->id);
        $this->assertSame(0, (int) DB::table('store_credit_entries')->where('user_id', $referrer->id)->sum('amount_paise'));

        $second = Order::factory()->create(['user_id' => $buyer->id, 'payment_status' => PaymentStatus::Paid, 'placed_at' => now()->addDay()]);
        app(ReferralService::class)->awardForPaidOrder($second);
        $this->assertSame(2, DB::table('referral_rewards')->count());
        $this->assertSame(1, DB::table('referral_rewards')->whereNull('revoked_at')->count());
    }
}
