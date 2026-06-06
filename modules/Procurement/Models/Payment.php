<?php

declare(strict_types=1);

namespace Modules\Procurement\Models;

use App\Shared\Enums\CorruptionCategory;
use App\Shared\Enums\Sphere;
use App\Support\PublicId\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A budget payment (нерегламентирани плащания) — a public spender paying a
 * recipient (СЕБРА). A distinct record_type from a tender (contract.py v2); kept
 * in its own table so the corruption-tax calculator can SUM(amount) over flagged
 * vs all payments. Flags attach polymorphically from the Detection side
 * (subject_type = Payment); we don't import Detection here (backend.md §1).
 */
final class Payment extends Model
{
    use HasPublicId;

    protected $fillable = [
        'source',
        'natural_key',
        'source_url',
        'fetched_at',
        'spender_authority_id',
        'recipient_company_id',
        'title',
        'description',
        'sphere',
        'category',
        'amount',
        'currency',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'fetched_at' => 'datetime',
            'sphere' => Sphere::class,
            'category' => CorruptionCategory::class,
            'amount' => 'decimal:2',
            'paid_at' => 'date',
        ];
    }

    /** @return BelongsTo<ContractingAuthority, Payment> */
    public function spender(): BelongsTo
    {
        return $this->belongsTo(ContractingAuthority::class, 'spender_authority_id');
    }

    /** @return BelongsTo<Company, Payment> */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'recipient_company_id');
    }
}
