<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function register(array $payload): array
    {
        $user = User::query()->create([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'password' => $payload['password'],
        ]);

        $token = $user->createToken(env('MOBILE_APP_NAME', 'TaskCollabMobile'))->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function login(array $payload): array
    {
        $user = User::query()->where('email', $payload['email'])->first();

        if (! $user || ! Hash::check($payload['password'], $user->password)) {
            throw new AuthenticationException('Invalid credentials.');
        }

        $token = $user->createToken(env('MOBILE_APP_NAME', 'TaskCollabMobile'))->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}
