<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\TeamMembership;
use App\Models\User;
use App\Services\AccessService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private readonly AccessService $accessService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $actor = $request->user();

        $query = User::query()
            ->where('organization_id', $actor->organization_id)
            ->select(['id', 'organization_id', 'role', 'first_name', 'last_name', 'name', 'email', 'created_at', 'updated_at'])
            ->orderBy('name')
            ->distinct();

        if ($this->accessService->isLead($actor)) {
            $teamIds = $this->accessService->leadTeamIds($actor);
            $userIds = TeamMembership::query()
                ->where('organization_id', $actor->organization_id)
                ->whereIn('team_id', $teamIds)
                ->pluck('user_id')
                ->all();
            $query->whereIn('id', $userIds);
        } elseif ($this->accessService->isDeveloper($actor) || $this->accessService->isPo($actor)) {
            $query->where('id', $actor->id);
        }

        $users = $query->get();

        return ApiResponse::success('Users fetched successfully.', UserResource::collection($users)->resolve());
    }
}
