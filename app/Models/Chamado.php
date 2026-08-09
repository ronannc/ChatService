<?php

namespace App\Models;

use App\Enums\EncerradoPor;
use App\Enums\StatusChamado;
use App\Models\Concerns\BelongsToSistema;
use Database\Factories\ChamadoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chamado extends Model
{
    /** @use HasFactory<ChamadoFactory> */
    use BelongsToSistema, HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'sistema_id',
        'status',
        'encerrado_por',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StatusChamado::class,
            'encerrado_por' => EncerradoPor::class,
        ];
    }

    public function mensagens(): HasMany
    {
        return $this->hasMany(Mensagem::class);
    }
}
