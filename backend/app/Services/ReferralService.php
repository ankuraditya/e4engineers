<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ReferralService
{
    public function codeFor(User $user): string
    {
        if ($user->referral_code) {
            return $user->referral_code;
        }

        return DB::transaction(function () use ($user): string {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            if (! $locked->referral_code) {
                do {
                    $code = Str::upper(Str::random(10));
                } while (User::query()->where('referral_code', $code)->exists());
                $locked->forceFill(['referral_code' => $code])->save();
            }

            return $locked->referral_code;
        });
    }

    public function awardForPaidOrder(Order $order): void
    {
        if (! $order->user_id || $order->currency !== 'INR') {
            return;
        }

        DB::transaction(function () use ($order): void {
            $buyer = User::query()->whereKey($order->user_id)->lockForUpdate()->first();
            if (! $buyer?->referred_by_user_id || $buyer->referred_by_user_id === $buyer->id) {
                return;
            }

            if (DB::table('referral_rewards')->where('referred_user_id', $buyer->id)->whereNull('revoked_at')->exists()) {
                return;
            }

            $firstPaid = Order::query()->where('user_id', $buyer->id)->where('payment_status', 'paid')->where('status', '!=', 'cancelled')->orderBy('placed_at')->orderBy('id')->value('id');
            if ((int) $firstPaid !== (int) $order->id) {
                return;
            }

            $rupees = (int) (WebsiteSetting::query()->where('key', 'referral_reward_rupees')->value('value') ?? 100);
            if ($rupees <= 0 || $rupees > 10000) {
                return;
            }

            $rewardId = DB::table('referral_rewards')->insertGetId([
                'referrer_user_id' => $buyer->referred_by_user_id,
                'referred_user_id' => $buyer->id,
                'qualifying_order_id' => $order->id,
                'amount_paise' => $rupees * 100,
                'credited_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('store_credit_entries')->insert([
                'user_id' => $buyer->referred_by_user_id,
                'referral_reward_id' => $rewardId,
                'amount_paise' => $rupees * 100,
                'reason' => 'referral_reward',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }
}
