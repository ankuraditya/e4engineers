<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class ApiFoundationTest extends TestCase
{
    public function test_health_endpoint_returns_the_standard_success_contract(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'E4ENGINEERS API is operational.')
            ->assertJsonPath('data.application', 'E4ENGINEERS')
            ->assertJsonPath('data.environment', 'testing')
            ->assertJsonPath('data.api_version', 'v1')
            ->assertJsonPath('data.database', 'connected')
            ->assertJsonStructure(['success', 'message', 'data' => ['application', 'environment', 'api_version', 'database']]);
    }

    public function test_unknown_api_endpoint_returns_a_json_404_contract(): void
    {
        $this->getJson('/api/v1/does-not-exist')
            ->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => 'API endpoint not found.',
                'errors' => [],
            ]);
    }

    public function test_validation_errors_use_the_standard_json_contract(): void
    {
        Route::post('/api/v1/_foundation-validation-test', function () {
            request()->validate(['email' => ['required', 'email']]);

            return response()->noContent();
        });

        $this->postJson('/api/v1/_foundation-validation-test', [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonValidationErrors(['email']);
    }

    public function test_configured_frontend_origin_is_allowed_with_credentials(): void
    {
        $this->withHeaders([
            'Origin' => 'http://localhost:4177',
            'Access-Control-Request-Method' => 'GET',
        ])->options('/api/v1/health')
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:4177')
            ->assertHeader('Access-Control-Allow-Credentials', 'true');
    }

    public function test_test_database_is_isolated_in_memory_sqlite(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertSame('sqlite', DB::connection()->getDriverName());
    }
}
