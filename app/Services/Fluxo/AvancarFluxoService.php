<?php

namespace App\Services\Fluxo;

use App\Enums\StatusChamado;
use App\Models\Chamado;
use App\Services\Auth\TokenClienteValidado;
use Illuminate\Support\Facades\DB;

class AvancarFluxoService
{
    public function __construct(
        private readonly IniciarFluxoService $iniciarFluxoService,
    ) {}

    /**
     * `$chamadoId` só é resolvido confrontado contra `sistema_id`/`cliente_ref`
     * do token já validado (nunca aceito "cru") — mesmo princípio de
     * `StoreChamadoService`/`EnsureAutorizadoEnviarMensagem`: um cliente não
     * pode avançar o fluxo de um chamado alheio, nem do mesmo sistema, nem
     * de outro. `lockForUpdate()` dentro da transação evita corrida com uma
     * segunda chamada concorrente sobre o mesmo chamado.
     *
     * Validação de `$resposta` é só de shape genérico (precisa trazer a
     * chave `opcao` batendo com uma das opções do nó atual) — nenhuma
     * validação semântica de conteúdo, isso é CHAT-024/026.
     */
    public function handle(int $chamadoId, TokenClienteValidado $tokenCliente, array $resposta): Chamado
    {
        return DB::transaction(function () use ($chamadoId, $tokenCliente, $resposta) {
            $chamado = Chamado::whereKey($chamadoId)
                ->where('sistema_id', $tokenCliente->iss)
                ->where('cliente_ref', $tokenCliente->sub)
                ->lockForUpdate()
                ->firstOrFail();

            if ($chamado->status !== StatusChamado::FluxoEmAndamento || ! $chamado->fluxo_definicao_id) {
                abort(409, 'Chamado não está em um fluxo em andamento.');
            }

            $definicao = $chamado->fluxoDefinicao()->firstOrFail()->definicao;
            $noAtual = $definicao['nos'][$chamado->no_atual] ?? null;

            if (! $noAtual || $noAtual['tipo'] !== 'pergunta') {
                abort(409, 'Nó atual do fluxo não aceita resposta.');
            }

            $opcaoEscolhida = collect($noAtual['opcoes'])
                ->first(fn (array $opcao) => ($opcao['valor'] ?? null) === ($resposta['opcao'] ?? null));

            if (! $opcaoEscolhida) {
                abort(422, 'Opção de resposta inválida para o nó atual do fluxo.');
            }

            $chamado->respostas_coletadas = [
                ...$chamado->respostas_coletadas,
                $chamado->no_atual => $resposta,
            ];

            if ($opcaoEscolhida['escalonamento'] ?? false) {
                $chamado->status = StatusChamado::AguardandoFila;
                $chamado->save();

                return $chamado;
            }

            $proximoNoChave = $opcaoEscolhida['proximo_no'];
            $proximoNo = $definicao['nos'][$proximoNoChave];

            if ($proximoNo['tipo'] === 'fim') {
                $chamado->save();

                if (! empty($proximoNo['proximo_fluxo'])) {
                    return $this->iniciarFluxoService->handle($chamado, $proximoNo['proximo_fluxo']);
                }

                $chamado->update([
                    'no_atual' => $proximoNoChave,
                    'status' => StatusChamado::AguardandoFila,
                ]);

                return $chamado;
            }

            $chamado->no_atual = $proximoNoChave;
            $chamado->save();

            return $chamado;
        });
    }
}
