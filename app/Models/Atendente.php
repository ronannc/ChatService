<?php

namespace App\Models;

use App\Enums\OrigemAtendente;
use App\Enums\StatusAtendente;
use App\Models\Concerns\BelongsToSistema;
use Database\Factories\AtendenteFactory;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class Atendente extends Model implements AuthenticatableContract
{
    /** @use HasFactory<AtendenteFactory> */
    use Authenticatable, BelongsToSistema, HasApiTokens, HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'sistema_id',
        'nome',
        'email',
        'senha',
        'status',
        'sub_externo',
        'origem',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'senha',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'senha' => 'hashed',
            'status' => StatusAtendente::class,
            'origem' => OrigemAtendente::class,
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->senha;
    }

    public function sistemasComPermissao(): HasMany
    {
        return $this->hasMany(AtendenteSistema::class);
    }
}
