<?php

declare(strict_types=1);

namespace Modules\Procurement\Models;

use App\Shared\Enums\CorruptionCategory;
use App\Shared\Enums\Sphere;
use App\Support\PublicId\HasPublicId;
use App\Support\Vector\VectorCast;
use Database\Factories\TenderFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Procurement\Enums\TenderStatus;

final class Tender extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $fillable = [
        'source',
        'natural_key',
        'source_url',
        'fetched_at',
        'contracting_authority_id',
        'winner_company_id',
        'title',
        'description',
        'cpv_code',
        'sphere',
        'category',
        'value',
        'currency',
        'vat_included',
        'status',
        'announced_at',
        'deadline_at',
        'awarded_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'fetched_at' => 'datetime',
            'sphere' => Sphere::class,
            'category' => CorruptionCategory::class,
            'value' => 'decimal:2',
            'vat_included' => 'boolean',
            'status' => TenderStatus::class,
            'announced_at' => 'date',
            'deadline_at' => 'date',
            'awarded_at' => 'date',
            'cancelled_at' => 'date',
            // Filled by `search:embed` (Google), read by the vector search. Not
            // fillable on purpose — it is never set from ingest/user input.
            'description_embedding' => VectorCast::class,
        ];
    }

    /** @return BelongsTo<ContractingAuthority, Tender> */
    public function authority(): BelongsTo
    {
        return $this->belongsTo(ContractingAuthority::class, 'contracting_authority_id');
    }

    /** @return BelongsTo<Company, Tender> */
    public function winner(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'winner_company_id');
    }

    /** @return HasMany<TenderItem> */
    public function items(): HasMany
    {
        return $this->hasMany(TenderItem::class);
    }

    protected static function newFactory(): Factory
    {
        return TenderFactory::new();
    }
}
