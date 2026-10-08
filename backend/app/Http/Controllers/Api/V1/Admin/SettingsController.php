<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Cms\SettingsRequest;
use App\Models\Media;
use App\Models\WebsiteSetting;
use App\Services\CmsCache;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    use ApiResponse;

    public function __construct(private CmsCache $cache) {}

    public function index(): JsonResponse
    {
        Gate::authorize('settings.view');

        return $this->successResponse(WebsiteSetting::query()->orderBy('group')->orderBy('key')->get());
    }

    public function update(SettingsRequest $request): JsonResponse
    {
        Gate::authorize('settings.update');
        DB::transaction(function () use ($request) {
            foreach ($request->validated('settings') as $item) {
                $def = config("cms.setting_keys.{$item['key']}");
                $value = $item['value'] ?? null;
                if ($def['type'] === 'boolean' && ! in_array($value, ['0', '1'], true)) {
                    throw ValidationException::withMessages(["settings.{$item['key']}" => 'Must be on or off.']);
                }
                if ($def['type'] === 'integer' && (! ctype_digit((string) $value) || (int) $value > 10000)) {
                    throw ValidationException::withMessages(["settings.{$item['key']}" => 'Enter a whole rupee amount from 0 to 10000.']);
                }
                if ($def['type'] === 'email' && $value && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw ValidationException::withMessages(["settings.{$item['key']}" => 'Must be a valid email address.']);
                }if ($def['type'] === 'url' && $value && ! filter_var($value, FILTER_VALIDATE_URL)) {
                    throw ValidationException::withMessages(["settings.{$item['key']}" => 'Must be a valid URL.']);
                }if ($def['type'] === 'media' && $value && ! Media::query()->whereKey($value)->exists()) {
                    throw ValidationException::withMessages(["settings.{$item['key']}" => 'Selected media does not exist.']);
                }WebsiteSetting::updateOrCreate(['key' => $item['key']], ['group' => $def['group'], 'type' => $def['type'], 'is_public' => $def['public'], 'value' => $value]);
            }
        });
        $this->cache->forget('settings');

        return $this->successResponse(WebsiteSetting::query()->orderBy('group')->orderBy('key')->get(), 'Settings updated.');
    }
}
