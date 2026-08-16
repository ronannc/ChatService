<?php

namespace App\Http\Middleware;

use App\Models\Atendente;
use App\Support\AtendenteContext;
use App\Support\SistemaContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contraparte de `IdentificarClienteMensagem` para o atendente interno: só
 * tenta resolver quando o bearer token NÃO tem formato de JWT (token
 * Sanctum é opaco). Resolve via `Auth::guard('sanctum')` diretamente (API do
 * framework, sem reimplementar a validação) — mantém a lógica de cliente
 * (JWT) totalmente fora desta classe, os dois mecanismos não se misturam
 * (.ai/rules/tokens.md).
 *
 * Atendente interno aqui é sempre de um único sistema (o próprio
 * `sistema_id`) — CHAT-009 não cobre atendente externo multi-sistema, então
 * não há necessidade da policy de RLS `chamados_sistemas_permitidos_atendente`
 * nem do GUC de sistemas permitidos para mensagens/chamados neste fluxo.
 */
class IdentificarAtendenteMensagem
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?? '';

        if ($token === '' || substr_count($token, '.') === 2) {
            return $next($request);
        }

        app(SistemaContext::class)->ativarBypassParaResolucaoDeAtendente();

        try {
            $atendente = Auth::guard('sanctum')->user();
        } finally {
            app(SistemaContext::class)->desativarBypassParaResolucaoDeAtendente();
        }

        if (! $atendente instanceof Atendente) {
            abort(401, 'Não autenticado.');
        }

        Auth::setUser($atendente);
        app(SistemaContext::class)->set($atendente->sistema_id);
        app(AtendenteContext::class)->set($atendente);

        return $next($request);
    }
}
