<?php

declare(strict_types=1);

namespace Modules\Notifications\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Notifications\Actions\SubscribeAction;
use Modules\Notifications\Contracts\SubscriberRepository;
use Modules\Notifications\Data\SubscribeData;

/** Public subscribe / one-click unsubscribe. Rate-limited at the route (security.md §2). */
final class SubscriptionController
{
    public function subscribe(SubscribeData $data, SubscribeAction $subscribe): JsonResponse
    {
        $subscribe->execute($data->email);

        return response()->json(['status' => 'subscribed'], 201);
    }

    public function unsubscribe(string $token, SubscriberRepository $subscribers): JsonResponse
    {
        $ok = $subscribers->unsubscribeByToken($token);

        return response()->json(['status' => $ok ? 'unsubscribed' : 'unknown'], $ok ? 200 : 404);
    }
}
