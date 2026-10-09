<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ReferralWithdrawalController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $status = $request->validate(['status' => ['nullable', 'in:pending,paid,rejected']])['status'] ?? null;
        $requests = DB::table('referral_withdrawals as w')->join('users as u', 'u.id', '=', 'w.user_id')
            ->when($status, fn ($query) => $query->where('w.status', $status))
            ->orderByDesc('w.created_at')->limit(100)->get(['w.*', 'u.name as customer_name', 'u.email as customer_email']);

        return $this->successResponse($requests);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:paid,rejected'],
            'payment_reference' => ['required_if:status,paid', 'nullable', 'string', 'max:120'],
            'admin_note' => ['required_if:status,rejected', 'nullable', 'string', 'max:1000'],
        ]);
        $withdrawal = DB::transaction(function () use ($request, $id, $data): object {
            $item = DB::table('referral_withdrawals')->where('id', $id)->lockForUpdate()->first();
            if (! $item || $item->status !== 'pending') {
                throw ValidationException::withMessages(['status' => 'Only pending withdrawals can be reviewed.']);
            }
            DB::table('referral_withdrawals')->where('id', $id)->update([
                'status' => $data['status'], 'payment_reference' => $data['payment_reference'] ?? null,
                'admin_note' => $data['admin_note'] ?? null, 'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(), 'updated_at' => now(),
            ]);
            if ($data['status'] === 'rejected') {
                DB::table('referral_wallet_entries')->insert([
                    'user_id' => $item->user_id, 'withdrawal_id' => null, 'amount_paise' => $item->amount_paise,
                    'reason' => 'withdrawal_rejected', 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            return DB::table('referral_withdrawals')->where('id', $id)->first();
        });

        return $this->successResponse($withdrawal, 'Withdrawal reviewed.');
    }
}
