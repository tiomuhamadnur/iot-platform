<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Parameter;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $plan = Plan::updateOrCreate(
            ['name' => 'Starter'],
            [
                'device_limit' => 10,
                'telemetry_rate_limit' => 60,
                'retention_days' => 7,
                'price' => 0,
            ]
        );

        $tenant = Tenant::updateOrCreate(
            ['slug' => 'demo-tenant'],
            [
                'name' => 'Demo Tenant',
                'api_key' => 'tenant-demo-key',
                'status' => 'active',
                'settings' => [],
                'plan_id' => $plan->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@demo.local'],
            [
                'name' => 'Demo Admin',
                'password' => Hash::make('password'),
                'tenant_id' => $tenant->id,
                'role' => 'tenant_admin',
            ]
        );

        $gateway = Device::updateOrCreate(
            ['device_id' => 'gateway-001'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Gateway 001',
                'secret' => 'gateway-secret',
                'status' => 'active',
            ]
        );

        $device = Device::updateOrCreate(
            ['device_id' => 'device-001'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Pump Sensor 001',
                'secret' => 'device-secret',
                'hardware_binding' => 'HW-DEVICE-001',
                'status' => 'active',
            ]
        );

        Parameter::updateOrCreate(
            ['device_id' => $device->id, 'identifier' => 'temperature'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Temperature',
                'unit' => 'C',
                'type' => 'numeric',
                'thresholds' => ['high' => 80, 'low' => 10],
            ]
        );

        Parameter::updateOrCreate(
            ['device_id' => $device->id, 'identifier' => 'pressure'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Pressure',
                'unit' => 'bar',
                'type' => 'numeric',
                'thresholds' => ['high' => 120, 'low' => 20],
            ]
        );
    }
}
