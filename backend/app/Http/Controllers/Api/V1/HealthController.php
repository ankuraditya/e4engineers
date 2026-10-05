<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

final class HealthController extends Controller
{
    use ApiResponse;

    public function __invoke(): JsonResponse
    {
        try {
            DB::connection()->getPdo();
            $database = 'connected';
        } catch (Throwable) {
            $database = 'unavailable';
        }

        return $this->successResponse([
            'application' => config('e4engineers.name'),
            'environment' => app()->environment(),
            'api_version' => 'v1',
            'database' => $database,
        ], 'E4ENGINEERS API is operational.');
    }
}
