<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can login and fetch current user', function () {
    $this->seed();

    $login = $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@demo.local',
        'password' => 'password',
    ]);

    $login->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => ['token', 'token_type', 'user'],
        ]);

    $token = $login->json('data.token');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.email', 'admin@demo.local');
});

it('returns only scoped tenant devices', function () {
    $this->seed();

    $login = $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@demo.local',
        'password' => 'password',
    ]);

    $token = $login->json('data.token');

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/devices');

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($response->json('data'))->toHaveCount(2);
});
