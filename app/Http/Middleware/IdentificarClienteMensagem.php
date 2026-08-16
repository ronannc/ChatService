<?php

namespace App\Http\Middleware;

use App\Exceptions\TokenClienteInvalidoException;
use App\Services\Auth\ValidarTokenClienteService;
use App\Support\SistemaContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * As rotas de mensagens (CHAT-009) aceitam dois mecanismos de auth
 * mutuamente exclusivos no mesmo endpoint (cliente JWT ou atendente
 * Sanctum) — não dá para registrar a mesma URI duas vezes com middleware
 * diferente. Este middleware só decide, pelo formato do bearer token (3
 * segmentos separados por ponto = JWT), se DEVE tentar a autenticação de
 * cliente; a validação em si continua 100% em `ValidarTokenClienteService`,
 * a mesma usada por `EnsureValidTokenCliente`. Nunca lê nem decide nada
 * sobre Sanctum/atendente (isso é `IdentificarAtendenteMensagem`) — os dois
 * mecanismos continuam isolados (.ai/rules/tokens.md).
 */
class IdentificarClienteMensagem
{
    public function __construct(private readonly ValidarTokenClienteService $validarTokenCliente) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?? '';

        if (substr_count($token, '.') !== 2) {
            return $next($request);
        }

        try {
            $tokenCliente = $this->validarTokenCliente->handle($token);
        } catch (TokenClienteInvalidoException $e) {
            abort(401, $e->getMessage());
        }

        app(SistemaContext::class)->set($tokenCliente->iss);
        $request->attributes->set('token_cliente', $tokenCliente);

        return $next($request);
    }
}
