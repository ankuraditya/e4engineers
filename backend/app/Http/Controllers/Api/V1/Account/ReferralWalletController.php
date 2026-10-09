<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetting;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ReferralWalletController extends Controller
{
    use ApiResponse;

    public function withdraw(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount_rupees' => ['required', 'integer', 'min:1', 'max:100000'],
            'upi_id' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9._-]{2,256}@[A-Za-z0-9.-]{2,64}$/'],
        ]);
        $withdrawal = DB::transaction(function () use ($request, $data): object {
            // Lock the account row so simultaneous requests cannot spend the same wallet balance.
            DB::table('users')->where('id', $request->user()->id)->lockForUpdate()->first();
            $amount = $data['amount_rupees'] * 100;
            $minimum = max(1, (int) (WebsiteSetting::query()->where('key', 'referral_min_withdrawal_rupees')->value('value') ?? 100)) * 100;
            $balance = (int) DB::table('referral_wallet_entries')->where('user_id', $request->user()->id)->sum('amount_paise');
            if ($amount < $minimum || $amount > $balance) {
                throw ValidationException::withMessages(['amount_rupees' => 'Enter an amount above the minimum and within your available wallet balance.']);
            }
            $id = DB::table('referral_withdrawals')->insertGetId([
                'user_id' => $request->user()->id, 'amount_paise' => $amount,
                'upi_id' => strtolower($data['upi_id']), 'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('referral_wallet_entries')->insert([
                'user_id' => $request->user()->id, 'amount_paise' => -$amount,
                'reason' => 'withdrawal_request', 'withdrawal_id' => $id, 'created_at' => now(), 'updated_at' => now(),
            ]);
            return DB::table('referral_withdrawals')->where('id', $id)->first();
        });

        return $this->successResponse($withdrawal, 'Withdrawal request submitted.');
    }
}
