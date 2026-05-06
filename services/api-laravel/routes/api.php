<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommandController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\TelemetryController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'tenant.scope'])->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::get('devices', [DeviceController::class, 'index']);
        Route::post('devices', [DeviceController::class, 'store'])->middleware('check.plan');
        Route::get('devices/{id}', [DeviceController::class, 'show']);
        Route::put('devices/{id}', [DeviceController::class, 'update']);
        Route::patch('devices/{id}', [DeviceController::class, 'update']);
        Route::delete('devices/{id}', [DeviceController::class, 'destroy']);

        Route::get('devices/{id}/telemetry', [TelemetryController::class, 'index']);

        Route::get('devices/{id}/commands', [CommandController::class, 'index']);
        Route::post('devices/{id}/commands', [CommandController::class, 'store']);
        Route::get('devices/{id}/commands/{commandId}', [CommandController::class, 'show']);
        Route::post('devices/{id}/commands/ack', [CommandController::class, 'ack']);
    });
});
