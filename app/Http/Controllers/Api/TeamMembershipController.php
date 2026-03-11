<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TeamMembership\StoreTeamMembershipRequest;
use App\Http\Resources\TeamMembershipResource;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Services\TeamMembershipService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamMembershipController extends Controller
{
    public function __construct(private readonly TeamMembershipService $teamMembershipService)
    {
    }

    public function index(Request $request, Team $team): JsonResponse
    {
        $this->authorize('viewAny', [TeamMembership::class, $team]);

        $memberships = $this->teamMembershipService->listForTeam($request->user(), $team);

        return ApiResponse::success(
            'Team memberships fetched successfully.',
            TeamMembershipResource::collection($memberships)->resolve()
        );
    }

    public function store(StoreTeamMembershipRequest $request, Team $team): JsonResponse
    {
        $this->authorize('create', [TeamMembership::class, $team]);

        $membership = $this->teamMembershipService->addMembership(
            $request->user(),
            $team,
            (int) $request->validated()['user_id']
        );

        return ApiResponse::success(
            'Team membership created successfully.',
            TeamMembershipResource::make($membership->load('user'))->resolve(),
            201
        );
    }

    public function destroy(Request $request, TeamMembership $teamMembership): JsonResponse
    {
        $this->authorize('delete', $teamMembership);

        $this->teamMembershipService->removeMembership($request->user(), $teamMembership);

        return ApiResponse::success('Team membership deleted successfully.', null);
    }
}

