<?php

namespace App\Http\Middleware;

use App\Services\Auth\TokenClienteValidado;
use App\Support\ContratoTokenCliente;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Roda depois de `cliente.token` (que já validou assinatura/claims e deixou
 * `token_cliente` nos attributes da request). Isolado da validação de
 * token: aqui só se decide se o escopo concedido pelo emissor permite a
 * ação de escrita, não se o token é autêntico — os dois mecanismos não se
 * misturam (.ai/rules/tokens.md).
 */
class EnsureScopeEscreverCliente
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var TokenClienteValidado $tokenCliente */
        $tokenCliente = $request->attributes->get('token_cliente');

        $scopes = explode(' ', $tokenCliente->scope);

        if (! in_array(ContratoTokenCliente::SCOPE_ESCREVER, $scopes, true)) {
            abort(403, 'Token sem escopo para esta ação.');
        }

        return $next($request);
    }
}
