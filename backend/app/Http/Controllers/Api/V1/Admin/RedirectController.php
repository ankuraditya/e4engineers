<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Cms\RedirectRequest;
use App\Models\Redirect;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class RedirectController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        Gate::authorize('redirects.view');

        return $this->successResponse(Redirect::orderBy('source_path')->get());
    }

    public function store(RedirectRequest $r): JsonResponse
    {
        Gate::authorize('redirects.create');

        return $this->successResponse(Redirect::create($r->validated()), 'Redirect created.', 201);
    }

    public function update(RedirectRequest $r, Redirect $redirect): JsonResponse
    {
        Gate::authorize('redirects.update');
        $redirect->update($r->validated());

        return $this->successResponse($redirect->refresh());
    }

    public function destroy(Redirect $redirect): JsonResponse
    {
        Gate::authorize('redirects.delete');
        $redirect->delete();

        return $this->successResponse();
    }
}
