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

final class AdminDashboardController extends Controller
{
    use ApiResponse;

    public function __invoke(): JsonResponse
    {
        return $this->successResponse([
            'counts' => [
                'users' => User::count(), 'articles' => Article::count(), 'courses' => Course::count(),
                'publications' => Publication::count(), 'books' => Book::count(), 'orders' => Order::count(),
                'new_enquiries' => Enquiry::where('status', 'new')->count(), 'open_tickets' => SupportTicket::whereNotIn('status', ['resolved', 'closed'])->count(),
                'career_applications' => CareerApplication::count(), 'notices' => Notice::count(),
            ],
            'recent_orders' => Order::with('user')->latest('placed_at')->limit(5)->get(['id', 'order_number', 'user_id', 'guest_email', 'status', 'payment_status', 'grand_total', 'currency', 'placed_at']),
        ], 'Admin dashboard retrieved.');
    }
}
