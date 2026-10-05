<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\UniversalSearchService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    use ApiResponse;

    public function __construct(private UniversalSearchService $search) {}

    public function index(Request $r): JsonResponse
    {
        $d = $r->validate(['q' => 'required|string|min:2|max:100', 'type' => 'nullable|in:article,course,book,publication,resource,contributor,notice,workshop,career,video', 'discipline' => 'nullable|string|max:100', 'sort' => 'nullable|in:relevance,latest', 'page' => 'nullable|integer|min:1', 'per_page' => 'nullable|integer|min:1|max:50']);
        $all = $this->search->search(trim($d['q']), $d['type'] ?? null, $d['discipline'] ?? null, $d['sort'] ?? 'relevance');
        $page = $d['page'] ?? 1;
        $per = $d['per_page'] ?? 12;
        $facets = $all->countBy('type');

        return $this->successResponse($all->forPage($page, $per)->values(), 'Search results.', 200, ['current_page' => $page, 'last_page' => (int) ceil($all->count() / $per), 'total' => $all->count(), 'facets' => $facets]);
    }

    public function suggestions(Request $r): JsonResponse
    {
        $d = $r->validate(['q' => 'required|string|min:2|max:100']);

        return $this->successResponse($this->search->search(trim($d['q']))->take(8)->map(fn ($x) => collect($x)->only(['type', 'title', 'url']))->values());
    }
}
