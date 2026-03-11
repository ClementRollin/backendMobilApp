<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invitation\StoreInvitationCodeRequest;
use App\Http\Resources\InvitationCodeResource;
use App\Models\InvitationCode;
use App\Services\InvitationCodeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvitationCodeController extends Controller
{
    public function __construct(private readonly InvitationCodeService $invitationCodeService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', InvitationCode::class);

        $codes = $this->invitationCodeService->listForUser($request->user());

        return ApiResponse::success(
            'Invitation codes fetched successfully.',
            InvitationCodeResource::collection($codes)->resolve()
        );
    }

    public function store(StoreInvitationCodeRequest $request): JsonResponse
    {
        $this->authorize('create', InvitationCode::class);

        $code = $this->invitationCodeService->create($request->user(), $request->validated());

        return ApiResponse::success('Invitation code created successfully.', InvitationCodeResource::make($code)->resolve(), 201);
    }

    public function revoke(Request $request, InvitationCode $invitationCode): JsonResponse
    {
        $this->authorize('revoke', $invitationCode);

        $updated = $this->invitationCodeService->revoke($request->user(), $invitationCode);

        return ApiResponse::success('Invitation code revoked successfully.', InvitationCodeResource::make($updated)->resolve());
    }
}

