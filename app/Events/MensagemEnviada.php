<?php

namespace App\Events;

use App\Models\Chamado;
use App\Models\Mensagem;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * `Illuminate\Contracts\Broadcasting\ShouldBroadcastAfterCommit` não existe
 * no Laravel 13.24 instalado — o "broadcast só após commit" desta versão é
 * `ShouldBroadcast` + a propriedade pública `$afterCommit = true`:
 * `Broadcasting\BroadcastEvent::__construct()` lê
 * `property_exists($event, 'afterCommit')`, não uma interface separada.
 * Sem isso, a mensagem nunca se perderia (ela já está persistida antes do
 * dispatch), mas um assinante poderia ver o broadcast de uma escrita que
 * acabou sendo revertida.
 *
 * Reaproveita o canal privado `chamado.{chamadoId}` já existente (CHAT-006,
 * `routes/channels.php`/`AutorizarCanalChamadoService`) — nenhum canal novo
 * criado para mensagens.
 */
class MensagemEnviada implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $afterCommit = true;

    public function __construct(
        public readonly Mensagem $mensagem,
        public readonly Chamado $chamado,
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
            'id' => $this->mensagem->id,
            'chamado_id' => $this->mensagem->chamado_id,
            'texto' => $this->mensagem->texto,
            'remetente_tipo' => $this->mensagem->remetente_tipo->value,
            'remetente_ref' => $this->mensagem->remetente_ref,
            'created_at' => $this->mensagem->created_at?->toJSON(),
            'chamado_status' => $this->chamado->status->value,
        ];
    }
}
