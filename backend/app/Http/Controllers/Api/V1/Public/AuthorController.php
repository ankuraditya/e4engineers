<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AuthorResource;
use App\Models\Author;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class AuthorController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        return $this->successResponse(AuthorResource::collection(Author::where('is_active', true)->with('photo')->orderBy('sort_order')->get()));
    }

    public function show(string $slug): JsonResponse
    {
        return $this->successResponse(new AuthorResource(Author::where('is_active', true)->with('photo')->where('slug', $slug)->firstOrFail()));
    }
}
