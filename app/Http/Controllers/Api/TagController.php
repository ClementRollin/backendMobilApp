<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tag\StoreTagRequest;
use App\Http\Requests\Tag\UpdateTagRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use App\Services\TagService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function __construct(private readonly TagService $tagService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Tag::class);

        $tags = $this->tagService->listForUser($request->user());

        return ApiResponse::success('Tags fetched successfully.', TagResource::collection($tags)->resolve());
    }

    public function store(StoreTagRequest $request): JsonResponse
    {
        $this->authorize('create', Tag::class);

        $tag = $this->tagService->create($request->user(), $request->validated());

        return ApiResponse::success('Tag created successfully.', TagResource::make($tag)->resolve(), 201);
    }

    public function update(UpdateTagRequest $request, Tag $tag): JsonResponse
    {
        $this->authorize('update', $tag);

        $updated = $this->tagService->update($tag, $request->validated());

        return ApiResponse::success('Tag updated successfully.', TagResource::make($updated)->resolve());
    }

    public function destroy(Request $request, Tag $tag): JsonResponse
    {
        $this->authorize('delete', $tag);

        $tag->delete();

        return ApiResponse::success('Tag deleted successfully.', null);
    }
}
