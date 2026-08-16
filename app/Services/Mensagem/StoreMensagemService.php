<?php

namespace App\Services\Mensagem;

use App\Enums\RemetenteMensagem;
use App\Enums\StatusChamado;
use App\Events\MensagemEnviada;
use App\Models\Chamado;
use App\Models\Mensagem;
use App\Support\SistemaContext;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StoreMensagemService
{
    /**
     * Persiste a mensagem e aplica a alternância de status do chamado como
     * efeito colateral atômico do envio — mesma transação, nunca uma ação
     * HTTP separada. O evento de broadcast (`MensagemEnviada`, que
     * implementa `ShouldBroadcast` com `$afterCommit = true`) só dispara
     * depois do commit.
     *
     * `$chamadoId` é relocado com `lockForUpdate()` dentro da transação (em
     * vez de reaproveitar o `Chamado` já carregado pelo middleware de
     * guard) para não perder uma alternância de status concorrente entre a
     * checagem de autorização e este INSERT.
     */
    public function handle(
        int $chamadoId,
        RemetenteMensagem $remetenteTipo,
        string $remetenteRef,
        string $texto,
    ): Mensagem {
        return DB::transaction(function () use ($chamadoId, $remetenteTipo, $remetenteRef, $texto) {
            $chamado = Chamado::whereKey($chamadoId)->lockForUpdate()->firstOrFail();

            $sistemaAtual = app(SistemaContext::class)->get();

            if ($sistemaAtual !== $chamado->sistema_id) {
                throw new RuntimeException('Contexto de sistema divergente do chamado ao gravar mensagem.');
            }

            $mensagem = Mensagem::create([
                'sistema_id' => $chamado->sistema_id,
                'chamado_id' => $chamado->id,
                'texto' => $texto,
                'remetente_tipo' => $remetenteTipo,
                'remetente_ref' => $remetenteRef,
            ]);

            $novoStatus = match ($remetenteTipo) {
                RemetenteMensagem::Cliente => StatusChamado::EmAtendimento,
                RemetenteMensagem::Atendente, RemetenteMensagem::Bot => StatusChamado::AguardandoCliente,
            };

            if ($chamado->status !== $novoStatus) {
                $chamado->update(['status' => $novoStatus]);
            }

            MensagemEnviada::dispatch($mensagem, $chamado);

            return $mensagem;
        });
    }
}
