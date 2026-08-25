<?php

namespace App\Events;

use App\Models\Chamado;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * `$afterCommit = true` pelo mesmo motivo de `MensagemEnviada`: garante que
 * o broadcast só saia depois que o UPDATE condicional (CHAT-011) tiver
 * commitado, evitando notificar um assumir que acabou sendo revertido.
 *
 * Reaproveita o canal privado `chamado.{chamadoId}` já existente (CHAT-006).
 */
class ChamadoAssumido implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $afterCommit = true;

    public function __construct(
        public readonly Chamado $chamado,
        public readonly string $nomeAtendente,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("chamado.{$this->chamado->id}"),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'chamado_id' => $this->chamado->id,
            'status' => $this->chamado->status->value,
            'atendente_atual_id' => $this->chamado->atendente_atual_id,
            'atendente_nome' => $this->nomeAtendente,
            'mensagem' => "{$this->nomeAtendente} assumiu seu chamado",
        ];
    }
}
