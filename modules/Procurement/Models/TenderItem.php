<?php

declare(strict_types=1);

namespace Modules\Procurement\Models;

use App\Support\PublicId\HasPublicId;
use Database\Factories\TenderItemFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TenderItem extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $fillable = [
        'tender_id',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'currency',
        'vat_included',
        'source_url',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'vat_included' => 'boolean',
        ];
    }

    /** @return BelongsTo<Tender, TenderItem> */
    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    /** @return HasMany<PriceSnapshot> */
    public function priceSnapshots(): HasMany
    {
        return $this->hasMany(PriceSnapshot::class);
    }

    protected static function newFactory(): Factory
    {
        return TenderItemFactory::new();
    }
}
