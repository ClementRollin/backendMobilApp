<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::query()
            ->select(['id', 'name', 'email', 'created_at', 'updated_at'])
            ->orderBy('name')
            ->get();

        return ApiResponse::success('Users fetched successfully.', UserResource::collection($users)->resolve());
    }
}
