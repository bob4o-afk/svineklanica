<?php

declare(strict_types=1);

namespace Modules\Publishing\Actions;

use Modules\Publishing\Contracts\PostRepository;
use Modules\Publishing\Models\Post;

final class DeletePostAction
{
    public function __construct(private readonly PostRepository $posts) {}

    public function execute(Post $post): void
    {
        $this->posts->delete($post);
    }
}
