<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Team\StoreTeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use App\Services\TeamService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function __construct(private readonly TeamService $teamService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $teams = $this->teamService->listForUser($request->user());

        return ApiResponse::success('Teams fetched successfully.', TeamResource::collection($teams)->resolve());
    }

    public function store(StoreTeamRequest $request): JsonResponse
    {
        $this->authorize('create', Team::class);

        $team = $this->teamService->create($request->user(), $request->validated());

        return ApiResponse::success('Team created successfully.', TeamResource::make($team)->resolve(), 201);
    }
}

