<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\RegisterCtoRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return ApiResponse::success('Registration successful.', [
            'token' => $result['token'],
            'user' => UserResource::make($result['user'])->resolve(),
        ], 201);
    }

    public function registerCto(RegisterCtoRequest $request): JsonResponse
    {
        $result = $this->authService->registerCto($request->validated());

        return ApiResponse::success('CTO registration successful.', [
            'token' => $result['token'],
            'user' => UserResource::make($result['user'])->resolve(),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return ApiResponse::success('Login successful.', [
            'token' => $result['token'],
            'user' => UserResource::make($result['user'])->resolve(),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(
            'Profile fetched successfully.',
            UserResource::make($request->user())->resolve()
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return ApiResponse::success('Logout successful.', null);
    }
}
