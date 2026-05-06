<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceController extends Controller
{
    public function index(Request $request)
    {
        $query = Device::query()->with('parameters');

        $tenantId = $request->attributes->get('tenant_id');
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        } elseif ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->integer('tenant_id'));
        }

        $devices = $query->orderBy('id', 'desc')->paginate($request->integer('per_page', 20));

        return ApiResponse::success($devices->items(), 'OK', 200, [
            'current_page' => $devices->currentPage(),
            'per_page' => $devices->perPage(),
            'total' => $devices->total(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'device_id' => ['required', 'string', 'max:255', 'unique:devices,device_id'],
            'name' => ['required', 'string', 'max:255'],
            'secret' => ['required', 'string', 'max:255'],
            'certificate' => ['nullable', 'string'],
            'hardware_binding' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'disabled', 'pending'])],
        ]);

        $validated['tenant_id'] = $this->resolveTenantId($request);
        $device = Device::create($validated);

        return ApiResponse::success($device->fresh(), 'Device created', 201);
    }

    public function show(string $id)
    {
        $device = $this->resolveDevice(request(), $id, ['parameters', 'commands']);
        if (! $device) {
            return ApiResponse::error('Device not found', 'DEVICE_NOT_FOUND', 404);
        }

        return ApiResponse::success($device);
    }

    public function update(Request $request, string $id)
    {
        $device = $this->resolveDevice($request, $id);
        if (! $device) {
            return ApiResponse::error('Device not found', 'DEVICE_NOT_FOUND', 404);
        }

        $validated = $request->validate([
            'device_id' => ['sometimes', 'string', 'max:255', Rule::unique('devices', 'device_id')->ignore($device->id)],
            'name' => ['sometimes', 'string', 'max:255'],
            'secret' => ['sometimes', 'string', 'max:255'],
            'certificate' => ['nullable', 'string'],
            'hardware_binding' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(['active', 'disabled', 'pending'])],
            'last_seen_at' => ['nullable', 'date'],
        ]);

        $device->update($validated);

        return ApiResponse::success($device->fresh(), 'Device updated');
    }

    public function destroy(string $id)
    {
        $device = $this->resolveDevice(request(), $id);
        if (! $device) {
            return ApiResponse::error('Device not found', 'DEVICE_NOT_FOUND', 404);
        }

        $device->delete();

        return ApiResponse::success(null, 'Device deleted');
    }

    private function resolveTenantId(Request $request): int
    {
        $tenantId = $request->attributes->get('tenant_id');

        if ($tenantId) {
            return (int) $tenantId;
        }

        return $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
        ])['tenant_id'];
    }

    private function resolveDevice(Request $request, string $id, array $with = []): ?Device
    {
        $query = Device::query()->with($with)->whereKey($id);

        if ($tenantId = $request->attributes->get('tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->first();
    }
}
