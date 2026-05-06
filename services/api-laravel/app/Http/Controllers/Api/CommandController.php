<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Command;
use App\Models\Device;
use App\Services\MqttService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class CommandController extends Controller
{
    protected $mqtt;

    public function __construct(MqttService $mqtt)
    {
        $this->mqtt = $mqtt;
    }

    public function index(Request $request, string $id)
    {
        $device = $this->resolveDevice($request, $id);
        if (! $device) {
            return ApiResponse::error('Device not found', 'DEVICE_NOT_FOUND', 404);
        }

        return ApiResponse::success(
            $device->commands()->latest('id')->get()
        );
    }

    public function show(Request $request, string $id, string $commandId)
    {
        $device = $this->resolveDevice($request, $id);
        if (! $device) {
            return ApiResponse::error('Device not found', 'DEVICE_NOT_FOUND', 404);
        }

        $command = $device->commands()->find($commandId);
        if (! $command) {
            return ApiResponse::error('Command not found', 'COMMAND_NOT_FOUND', 404);
        }

        return ApiResponse::success($command);
    }

    public function store(Request $request, string $id)
    {
        $request->validate([
            'action' => 'required|string|max:255',
            'params' => 'nullable|array',
        ]);

        $device = $this->resolveDevice($request, $id);
        if (! $device) {
            return ApiResponse::error('Device not found', 'DEVICE_NOT_FOUND', 404);
        }

        $command = Command::create([
            'tenant_id' => $device->tenant_id,
            'device_id' => $device->id,
            'command' => $request->string('action')->toString(),
            'payload' => $request->input('params', []),
            'status' => 'pending',
        ]);

        $topic = "tenant/{$device->tenant_id}/device/{$device->device_id}/command";
        $message = json_encode([
            'command_id' => (string) $command->id,
            'action' => $command->command,
            'params' => $command->payload ?? [],
            'issued_at' => now()->toISOString(),
        ]);

        try {
            $this->mqtt->publish($topic, $message);
            $command->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $command->update(['status' => 'failed']);

            return ApiResponse::error('Failed to send command via MQTT', 'MQTT_PUBLISH_FAILED', 500);
        }

        return ApiResponse::success($command->fresh(), 'Command sent', 201);
    }

    public function ack(Request $request, string $id)
    {
        $request->validate([
            'command_id' => 'required|integer',
            'status' => 'required|string|in:acked,failed',
        ]);

        $device = $this->resolveDevice($request, $id);
        if (! $device) {
            return ApiResponse::error('Device not found', 'DEVICE_NOT_FOUND', 404);
        }

        $command = $device->commands()->find($request->integer('command_id'));
        if (! $command) {
            return ApiResponse::error('Command not found', 'COMMAND_NOT_FOUND', 404);
        }

        $command->update([
            'status' => $request->string('status')->toString(),
            'ack_at' => now(),
        ]);

        return ApiResponse::success($command->fresh(), 'Command acknowledged');
    }

    private function resolveDevice(Request $request, string $id): ?Device
    {
        $query = Device::query()->whereKey($id);

        if ($tenantId = $request->attributes->get('tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->first();
    }
}
