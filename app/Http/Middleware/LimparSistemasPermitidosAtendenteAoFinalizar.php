<?php

namespace App\Http\Middleware;

use App\Support\SistemaContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garante que o GUC `app.sistemas_permitidos_atendente`
 * (`SistemaContext::definirSistemasPermitidosAtendente()`, setado por
 * `App\Services\Broadcasting\AutorizarCanalChamadoService` quando quem
 * autoriza o canal é um atendente) não sobrevive além desta request.
 *
 * `set_config(..., false)` é escopo de sessão, não de transação — hoje é
 * inofensivo porque cada request HTTP abre uma conexão Postgres nova (sem
 * connection pooling persistente), mas sem essa limpeza seria uma
 * bomba-relógio assim que uma conexão passar a persistir entre requests/jobs
 * (Horizon workers de CHAT-010, ou Octane): um atendente autenticado numa
 * autorização de canal anterior "vazaria" a lista de sistemas permitidos
 * pra qualquer query seguinte na mesma conexão, ampliando a visibilidade da
 * policy de RLS além do previsto.
 *
 * Mesmo padrão de `EnableAtendenteAuthRlsBypass` pro GUC irmão de bypass de
 * resolução: `terminate()` roda mesmo quando a pipeline foi interrompida por
 * uma exceção antes de chegar no controller (ex.: token inválido), então a
 * limpeza acontece de qualquer jeito — mesmo em requests onde o GUC nunca
 * chegou a ser setado (limpar um GUC vazio é inofensivo/idempotente).
 */
class LimparSistemasPermitidosAtendenteAoFinalizar
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        app(SistemaContext::class)->limparSistemasPermitidosAtendente();
    }
}
