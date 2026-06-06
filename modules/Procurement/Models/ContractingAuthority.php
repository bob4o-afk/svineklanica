<?php

declare(strict_types=1);

namespace Modules\Procurement\Models;

use App\Support\PublicId\HasPublicId;
use Database\Factories\ContractingAuthorityFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ContractingAuthority extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $fillable = [
        'name',
        'eik',
        'region',
        'source_url',
    ];

    /** @return HasMany<Tender> */
    public function tenders(): HasMany
    {
        return $this->hasMany(Tender::class);
    }

    protected static function newFactory(): Factory
    {
        return ContractingAuthorityFactory::new();
    }
}
