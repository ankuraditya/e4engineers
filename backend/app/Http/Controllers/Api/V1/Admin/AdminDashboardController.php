<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Book;
use App\Models\CareerApplication;
use App\Models\Course;
use App\Models\Enquiry;
use App\Models\Notice;
use App\Models\Order;
use App\Models\Publication;
use App\Models\SupportTicket;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class AdminDashboardController extends Controller
{
    use ApiResponse;

    public function __invoke(): JsonResponse
    {
        return $this->successResponse([
            'counts' => [
                'users' => User::count(), 'articles' => Article::count(), 'courses' => Course::count(),
                'publications' => Publication::count(), 'books' => Book::count(), 'orders' => Order::count(),
                'self_collect_pending' => Order::where('delivery_method', 'self_collect')->whereNull('picked_up_at')->where('status', '!=', 'cancelled')->count(),
                'new_enquiries' => Enquiry::where('status', 'new')->count(), 'open_tickets' => SupportTicket::whereNotIn('status', ['resolved', 'closed'])->count(),
                'career_applications' => CareerApplication::count(), 'notices' => Notice::count(),
                'referral_signups' => User::whereNotNull('referred_by_user_id')->count(),
                'referral_rewards' => DB::table('referral_rewards')->whereNull('revoked_at')->count(),
                'withdrawal_requests' => DB::table('referral_withdrawals')->where('status', 'pending')->count(),
            ],
            'recent_orders' => Order::with('user')->latest('placed_at')->limit(5)->get(['id', 'order_number', 'user_id', 'guest_email', 'status', 'payment_status', 'delivery_method', 'pickup_ready_at', 'grand_total', 'currency', 'placed_at']),
            'referral_credit_issued_rupees' => number_format(DB::table('referral_rewards')->whereNull('revoked_at')->sum('amount_paise') / 100, 2, '.', ''),
            'recent_referrals' => DB::table('referral_rewards')->join('users as referrers', 'referrers.id', '=', 'referral_rewards.referrer_user_id')->join('users as buyers', 'buyers.id', '=', 'referral_rewards.referred_user_id')->whereNull('referral_rewards.revoked_at')->orderByDesc('referral_rewards.credited_at')->limit(5)->get(['referrers.name as referrer', 'buyers.name as buyer', 'referral_rewards.amount_paise', 'referral_rewards.credited_at']),
        ], 'Admin dashboard retrieved.');
    }
}
