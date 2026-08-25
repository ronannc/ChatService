<?php

namespace App\Services\Chamado;

use App\Enums\StatusChamado;
use App\Events\ChamadoAssumido;
use App\Models\Chamado;
use App\Models\Scopes\SistemaScope;
use App\Support\AtendenteContext;
use App\Support\SistemaContext;

class AssumirChamadoService
{
    public function __construct(
        private readonly AtendenteContext $atendenteContext,
        private readonly SistemaContext $sistemaContext,
    ) {}

    /**
     * Resolve o Chamado sem Route Model Binding implícito (aplicaria
     * `SistemaScope`, que retorna `whereRaw(1=0)` neste contexto de
     * atendente Sanctum sem `SistemaContext::set()` — CHAT-010,
     * .ai/rules/chamado-fila.md) e usa UPDATE condicional atômico para
     * resolver a concorrência: só afeta a linha se o status ainda for
     * `aguardando_fila` no momento exato da escrita. `find()+save()` não
     * serve aqui — reabriria a janela de corrida entre dois atendentes.
     *
     * O GUC de sistemas permitidos precisa estar setado ANTES do primeiro
     * SELECT: a RLS de `chamados` (policy `chamados_sistemas_permitidos_atendente`,
     * CHAT-006) bloqueia a leitura da linha enquanto o GUC não estiver
     * definido. Mesmo assim, toda query filtra também client-side por
     * `whereIn('sistema_id', $sistemasPermitidos)` — defesa em profundidade
     * (.ai/rules/chamado-fila.md, mesmo padrão de `ListarFilaChamadosService`):
     * a policy antiga `chamados_isolamento_sistema` combina por OR com a
     * nova e pode enxergar o chamado se `app.current_sistema_id` estiver
     * "sujo" de uma operação anterior na mesma conexão, então a RLS sozinha
     * não é suficiente. Não existe (nem este épico cria) uma policy de
     * bypass total de leitura em `chamados` como a que existe para
     * `atendentes`, então não dá para distinguir "chamado inexistente" de
     * "chamado existe mas fora dos sistemas permitidos" sem expandir a
     * superfície de leitura — os dois casos colapsam em 404
     * (`ModelNotFoundException`), nunca em 403.
     */
    public function handle(int $chamadoId): Chamado
    {
        $atendente = $this->atendenteContext->atendente();

        $sistemasPermitidos = $this->atendenteContext->sistemasPermitidos();

        $this->sistemaContext->definirSistemasPermitidosAtendente($sistemasPermitidos);

        try {
            Chamado::withoutGlobalScope(SistemaScope::class)
                ->whereIn('sistema_id', $sistemasPermitidos)
                ->findOrFail($chamadoId);

            $linhasAfetadas = Chamado::withoutGlobalScope(SistemaScope::class)
                ->whereIn('sistema_id', $sistemasPermitidos)
                ->where('id', $chamadoId)
                ->where('status', StatusChamado::AguardandoFila)
                ->update([
                    'status' => StatusChamado::EmAtendimento,
                    'atendente_atual_id' => $atendente->id,
                ]);

            if ($linhasAfetadas === 0) {
                abort(409, 'Chamado já foi assumido por outro atendente.');
            }

            $chamadoAtualizado = Chamado::withoutGlobalScope(SistemaScope::class)
                ->whereIn('sistema_id', $sistemasPermitidos)
                ->findOrFail($chamadoId);

            ChamadoAssumido::dispatch($chamadoAtualizado, $atendente->nome);

            return $chamadoAtualizado;
        } finally {
            $this->sistemaContext->limparSistemasPermitidosAtendente();
        }
    }
}
