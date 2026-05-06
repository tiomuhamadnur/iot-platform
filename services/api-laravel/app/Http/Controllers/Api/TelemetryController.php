<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Services\InfluxService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class TelemetryController extends Controller
{
    protected $influx;

    public function __construct(InfluxService $influx)
    {
        $this->influx = $influx;
    }

    public function index(Request $request, string $id)
    {
        $request->validate([
            'from' => 'required|string',
            'to' => 'nullable|string',
            'params' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:10000',
        ]);

        $deviceQuery = Device::query()->whereKey($id);
        if ($tenantId = $request->attributes->get('tenant_id')) {
            $deviceQuery->where('tenant_id', $tenantId);
        }

        $device = $deviceQuery->first();
        if (! $device) {
            return ApiResponse::error('Device not found', 'DEVICE_NOT_FOUND', 404);
        }

        $from = $this->escapeFluxString($request->string('from')->toString());
        $to = $request->filled('to')
            ? '"' . $this->escapeFluxString($request->string('to')->toString()) . '"'
            : 'now()';
        $params = collect(explode(',', (string) $request->input('params')))
            ->map(fn ($param) => trim($param))
            ->filter()
            ->values();
        $limit = $request->integer('limit', 1000);
        $bucket = $this->influx->bucket();
        $deviceKey = $this->escapeFluxString($device->device_id);

        $query = "from(bucket: \"{$bucket}\")
            |> range(start: time(v: \"{$from}\"), stop: {$to})
            |> filter(fn: (r) => r[\"_measurement\"] == \"telemetry\")
            |> filter(fn: (r) => r[\"device_id\"] == \"{$deviceKey}\")";

        if ($params->isNotEmpty()) {
            $paramFilters = $params
                ->map(fn (string $param) => 'r["param_key"] == "' . $this->escapeFluxString($param) . '"')
                ->implode(' or ');
            $query .= "\n            |> filter(fn: (r) => {$paramFilters})";
        }

        $query .= "\n            |> sort(columns: [\"_time\"], desc: false)
            |> limit(n: {$limit})";

        $results = $this->influx->query($query);

        $data = [];
        foreach ($results as $table) {
            foreach ($table->records as $record) {
                $values = $record->values;
                $data[] = [
                    'timestamp' => (string) ($values['_time'] ?? ''),
                    'param_key' => $values['param_key'] ?? null,
                    'value' => $values['_field'] === 'value' ? $values['_value'] : null,
                    'str_value' => $values['_field'] === 'str_value' ? $values['_value'] : null,
                    'server_time' => $values['_field'] === 'server_time' ? $values['_value'] : ($values['server_time'] ?? null),
                    'device_id' => $values['device_id'] ?? $device->device_id,
                    'tenant_id' => $values['tenant_id'] ?? $device->tenant_id,
                ];
            }
        }

        return ApiResponse::success($data);
    }

    private function escapeFluxString(string $value): string
    {
        return addcslashes($value, "\\\"");
    }
}
