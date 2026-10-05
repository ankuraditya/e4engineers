<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use ApiResponse;

    public function index(Request $r): JsonResponse
    {
        $items = PaymentTransaction::whereHas('attempt.order', fn ($q) => $q->where('user_id', $r->user()->id))->with('attempt.order')->latest()->paginate(20);

        return $this->successResponse($items->items(), meta: ['pagination' => ['total' => $items->total(), 'last_page' => $items->lastPage()]]);
    }
}
