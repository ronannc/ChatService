<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSistema;
use Database\Factories\AtendenteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Atendente extends Model
{
    /** @use HasFactory<AtendenteFactory> */
    use BelongsToSistema, HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'sistema_id',
    ];

    public function sistemasComPermissao(): HasMany
    {
        return $this->hasMany(AtendenteSistema::class);
    }
}
