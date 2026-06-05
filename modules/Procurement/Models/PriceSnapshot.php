<?php

declare(strict_types=1);

namespace Modules\Procurement\Models;

use App\Support\PublicId\HasPublicId;
use Database\Factories\PriceSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class PriceSnapshot extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $fillable = [
        'tender_item_id',
        'product_key',
        'description',
        'price',
        'currency',
        'captured_at',
        'source_url',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'captured_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<TenderItem, PriceSnapshot> */
    public function tenderItem(): BelongsTo
    {
        return $this->belongsTo(TenderItem::class);
    }

    protected static function newFactory(): Factory
    {
        return PriceSnapshotFactory::new();
    }
}
