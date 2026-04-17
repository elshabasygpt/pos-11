<?php

namespace App\Presentation\Controllers\API;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsController extends BaseController
{
    /**
     * GET /api/settings — Retrieve tenant settings.
     */
    public function index(Request $request): JsonResponse
    {
        $settings = DB::table('tenant_settings')->pluck('value', 'key');

        return $this->success([
            'company_name' => $settings['company_name'] ?? null,
            'phone'        => $settings['phone'] ?? null,
            'email'        => $settings['email'] ?? null,
            'website'      => $settings['website'] ?? null,
            'logo_url'     => $settings['logo_url'] ?? null,
            'address'      => $settings['address'] ?? null,
            'vat_number'   => $settings['vat_number'] ?? null,
            'cr_number'    => $settings['cr_number'] ?? null,
        ]);
    }

    /**
     * PUT /api/settings — Update tenant settings.
     */
    public function update(Request $request): JsonResponse
    {
        $allowedKeys = [
            'company_name', 'phone', 'email', 'website',
            'logo_url', 'address', 'vat_number', 'cr_number',
        ];

        $data = $request->only($allowedKeys);

        foreach ($data as $key => $value) {
            if ($value !== null) {
                DB::table('tenant_settings')->updateOrInsert(
                    ['key' => $key],
                    ['value' => $value, 'updated_at' => now()]
                );
            }
        }

        return $this->success(null, 'Settings updated successfully');
    }
}
