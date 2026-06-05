<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\PublicId\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasPublicId, Notifiable;

    /**
     * Mass-assignable attributes. SECURITY (security.md §5): `is_admin` and
     * `public_id` are DELIBERATELY excluded — privilege and identity can never
     * be set through `fill()` / `create([...])` / a DTO. `is_admin` is only ever
     * set by an explicit property assignment (seeder/admin action) or the DB
     * default (false); `public_id` is assigned by the HasPublicId trait.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }
}
