<?php

declare(strict_types=1);

namespace Modules\Procurement\Models;

use App\Support\PublicId\HasPublicId;
use App\Support\Vector\VectorCast;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Company extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $fillable = [
        'eik',
        'name',
        'address',
        'owner_name',
        'phone',
        'source_url',
    ];

    protected function casts(): array
    {
        return [
            // Filled by `search:embed` (Google); read by the vector search.
            'name_embedding' => VectorCast::class,
        ];
    }

    /** Tenders this company won (winner→authority graph, CLAUDE.md §1.1.3). @return HasMany<Tender> */
    public function wonTenders(): HasMany
    {
        return $this->hasMany(Tender::class, 'winner_company_id');
    }

    protected static function newFactory(): Factory
    {
        return CompanyFactory::new();
    }
}
