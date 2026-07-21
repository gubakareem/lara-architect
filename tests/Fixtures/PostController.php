<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Fixtures;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use KarimAshraf\LaraArchitect\Http\Concerns\RespondsWithJson;

class PostController extends Controller
{
    use RespondsWithJson;

    public function __construct(
        private readonly PostService $postService,
    ) {}

    public function store(StorePostRequest $request): JsonResponse
    {
        $post = $this->postService->create($request->validated());

        return $this->respondCreated($post->only(['id', 'title']));
    }

    public function destroy(Post $post): JsonResponse
    {
        $this->postService->delete($post);

        return $this->respondDeleted();
    }
}
