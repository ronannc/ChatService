<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Definição de fluxo fixo (CHAT-023) — deliberadamente SEM `BelongsToSistema`:
 * o motor de estados é único e global, compartilhado por todos os sistemas
 * integrados, não isolado por `sistema_id`/RLS como `Chamado`/`Mensagem`.
 */
class FluxoDefinicao extends Model
{
    /**
     * Chave do fluxo de fixture usado por `StoreChamadoService` (CHAT-023)
     * enquanto não existe nenhum fluxo real — CHAT-024 substitui isto por
     * fluxos de conteúdo real.
     */
    public const CHAVE_FIXTURE_INICIAL = 'fixture-chat-023-inicial';

    /**
     * Pluralização padrão do Eloquent geraria `fluxo_definicaos`.
     */
    protected $table = 'fluxo_definicoes';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'chave',
        'versao',
        'definicao',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'definicao' => 'array',
        ];
    }

    public function chamados(): HasMany
    {
        return $this->hasMany(Chamado::class);
    }
}
