<?php

namespace App\Http\Middleware;

use App\Exceptions\TokenClienteInvalidoException;
use App\Services\Auth\ValidarTokenClienteService;
use App\Support\SistemaContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentica o cliente final via o token assinado pelo sistema de origem
 * (docs/contratos/token-cliente.md). Diferente de `auth:sanctum`
 * (atendente) ou `admin.api-key` (endpoints administrativos), aqui não há
 * guard do framework: a verificação inteira — formato, assinatura RS256 via
 * JWKS, claims, cadastro do sistema — é `ValidarTokenClienteService`.
 *
 * No sucesso, resolve o contexto de `sistema_id` a partir do `iss` do token
 * (`SistemaContext::set()`), o mesmo mecanismo usado por
 * `ResolveAtendenteContext`, antes do controller rodar. Na rejeição,
 * devolve 401 com uma mensagem genérica — o motivo específico fica só no
 * log (`ValidarTokenClienteService`).
 */
class EnsureValidTokenCliente
{
    public function __construct(private readonly ValidarTokenClienteService $validarTokenCliente) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $tokenCliente = $this->validarTokenCliente->handle($request->bearerToken() ?? '');
        } catch (TokenClienteInvalidoException $e) {
            abort(401, $e->getMessage());
        }

        app(SistemaContext::class)->set($tokenCliente->iss);

        $request->attributes->set('token_cliente', $tokenCliente);

        return $next($request);
    }
}
