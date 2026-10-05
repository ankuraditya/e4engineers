<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PublisherResource;
use App\Models\Publisher;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class PublisherController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        return $this->successResponse(PublisherResource::collection(Publisher::where('is_active', true)->with('logo')->orderBy('sort_order')->get()));
    }

    public function show(string $slug): JsonResponse
    {
        return $this->successResponse(new PublisherResource(Publisher::where('is_active', true)->with('logo')->where('slug', $slug)->firstOrFail()));
    }
}
