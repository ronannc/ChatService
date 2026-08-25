<?php

namespace App\Models;

use App\Enums\EncerradoPor;
use App\Enums\StatusChamado;
use App\Models\Concerns\BelongsToSistema;
use Database\Factories\ChamadoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'cliente_ref',
        'atendente_atual_id',
        'status',
        'encerrado_por',
        'fluxo_definicao_id',
        'no_atual',
        'respostas_coletadas',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StatusChamado::class,
            'encerrado_por' => EncerradoPor::class,
            'respostas_coletadas' => 'array',
        ];
    }

    public function mensagens(): HasMany
    {
        return $this->hasMany(Mensagem::class);
    }

    public function atendenteAtual(): BelongsTo
    {
        return $this->belongsTo(Atendente::class, 'atendente_atual_id');
    }

    public function fluxoDefinicao(): BelongsTo
    {
        return $this->belongsTo(FluxoDefinicao::class, 'fluxo_definicao_id');
    }
}
