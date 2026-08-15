<?php

namespace App\Http\Middleware;

use App\Services\Atendente\ProvisionarAtendenteExternoService;
use App\Services\Auth\TokenClienteValidado;
use App\Support\AtendenteContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Roda depois de `EnsureValidTokenCliente` (CHAT-005B). Diferente de
 * `ResolveAtendenteContext` (Sanctum, atendente interno, CHAT-005A), este
 * middleware não liga bypass de RLS nenhum pra request inteira — o
 * `EnableAtendenteAuthRlsBypass` (bypass de request completa) não se aplica
 * aqui. A identidade do atendente externo é `sub_externo` sozinho (sem
 * escopo por sistema — ver .ai/rules/atendente-externo.md), então o lookup
 * cross-sistema que isso exige é feito dentro de
 * `ProvisionarAtendenteExternoService`, com um bypass escopado só àquela
 * consulta/criação (mesmo mecanismo de `LoginAtendenteService`, `SET LOCAL`
 * dentro de uma transaction) — não algo ligado por este middleware.
 *
 * Só age quando o token tem `role=atendente`; abortar com 403 caso
 * contrário evita que um token de cliente final acesse rotas pensadas para
 * atendente externo.
 */
class ResolveAtendenteExternoContext
{
    public function __construct(private readonly ProvisionarAtendenteExternoService $provisionarAtendenteExterno) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tokenCliente = $request->attributes->get('token_cliente');

        if (! $tokenCliente instanceof TokenClienteValidado || ! $tokenCliente->ehAtendente()) {
            abort(403, 'Token sem permissão de atendente.');
        }

        $atendente = $this->provisionarAtendenteExterno->handle($tokenCliente);

        app(AtendenteContext::class)->set($atendente);

        return $next($request);
    }
}
