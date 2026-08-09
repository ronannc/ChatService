<?php

namespace App\Models;

use Database\Factories\AtendenteSistemaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Permissão de atendente por sistema. Deliberadamente sem BelongsToSistema
 * nem Row Level Security por sistema_id — é a própria tabela que lista a
 * quais outros sistemas um atendente tem acesso, então escopá-la pelo
 * "sistema atual" quebraria essa finalidade (ver migration de RLS).
 */
class AtendenteSistema extends Model
{
    /** @use HasFactory<AtendenteSistemaFactory> */
    use HasFactory;

    protected $table = 'atendente_sistema';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'atendente_id',
        'sistema_id',
    ];

    public function atendente(): BelongsTo
    {
        return $this->belongsTo(Atendente::class);
    }

    public function sistema(): BelongsTo
    {
        return $this->belongsTo(Sistema::class, 'sistema_id', 'codigo');
    }
}
