<?php

declare(strict_types=1);

namespace Modules\Notifications\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Modules\Notifications\Actions\NotifySubscribersAction;
use Modules\Notifications\Data\BroadcastData;

/** Admin: queue a broadcast to every active subscriber. Guarded at route + Data boundary. */
final class BroadcastController
{
    public function store(BroadcastData $data, NotifySubscribersAction $notify): JsonResponse
    {
        $recipients = $notify->execute($data->subject, $data->lines);

        return response()->json(['status' => 'queued', 'recipients' => $recipients], 202);
    }
}
