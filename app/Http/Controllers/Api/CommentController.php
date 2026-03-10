<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Task;
use App\Services\CommentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function __construct(private readonly CommentService $commentService)
    {
    }

    public function index(Request $request, Task $task): JsonResponse
    {
        $this->authorize('comment', $task);

        $comments = $this->commentService->listForTask($task);

        return ApiResponse::success(
            'Comments fetched successfully.',
            CommentResource::collection($comments)->resolve()
        );
    }

    public function store(StoreCommentRequest $request, Task $task): JsonResponse
    {
        $this->authorize('comment', $task);

        $comment = $this->commentService->create(
            $task,
            $request->user(),
            $request->validated()['content']
        );

        return ApiResponse::success('Comment added successfully.', CommentResource::make($comment)->resolve(), 201);
    }
}
