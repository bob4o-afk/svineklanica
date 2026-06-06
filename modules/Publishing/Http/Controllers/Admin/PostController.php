<?php

declare(strict_types=1);

namespace Modules\Publishing\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Publishing\Actions\CreatePostAction;
use Modules\Publishing\Actions\DeletePostAction;
use Modules\Publishing\Actions\UpdatePostAction;
use Modules\Publishing\Contracts\PostRepository;
use Modules\Publishing\Data\PostData;
use Modules\Publishing\Data\StorePostData;
use Modules\Publishing\Data\UpdatePostData;

/**
 * Admin post management. Guarded by auth:sanctum + admin middleware at the route,
 * and authorized again at the Data boundary (StorePostData/UpdatePostData::authorize).
 */
final class PostController
{
    public function __construct(private readonly PostRepository $posts) {}

    public function store(StorePostData $data, CreatePostAction $create, Request $request): JsonResponse
    {
        $post = $create->execute($data, $request->user());

        return response()->json(PostData::fromModel($post), 201);
    }

    public function update(string $post, UpdatePostData $data, UpdatePostAction $update): PostData
    {
        $model = $this->posts->find($post);
        abort_if($model === null, 404);

        return PostData::fromModel($update->execute($model, $data));
    }

    public function destroy(string $post, DeletePostAction $delete): Response
    {
        $model = $this->posts->find($post);
        abort_if($model === null, 404);

        $delete->execute($model);

        return response()->noContent();
    }
}
