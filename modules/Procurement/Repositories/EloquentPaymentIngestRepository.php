<?php

declare(strict_types=1);

namespace Modules\Procurement\Repositories;

use Modules\Procurement\Contracts\PaymentIngestRepository;
use Modules\Procurement\Models\Payment;

final class EloquentPaymentIngestRepository implements PaymentIngestRepository
{
    public function upsertPayment(string $source, string $naturalKey, array $attributes): Payment
    {
        return Payment::updateOrCreate(
            ['source' => $source, 'natural_key' => $naturalKey],
            $attributes,
        );
    }
}
