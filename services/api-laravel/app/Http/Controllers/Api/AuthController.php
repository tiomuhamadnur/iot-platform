<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::with('tenant.plan')->where('email', $validated['email'])->first();
        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return ApiResponse::error('Invalid credentials', 'INVALID_CREDENTIALS', 401);
        }

        $token = $user->createToken('api')->plainTextToken;

        return ApiResponse::success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ], 'Login successful');
    }

    public function me(Request $request)
    {
        $user = $request->user()?->load('tenant.plan');

        return ApiResponse::success($user);
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return ApiResponse::success(null, 'Logout successful');
    }
}
