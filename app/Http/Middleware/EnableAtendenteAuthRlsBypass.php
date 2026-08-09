<?php

namespace App\Http\Middleware;

use App\Support\SistemaContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Roda ANTES de `auth:sanctum`. O Sanctum precisa ler a linha do atendente
 * (via a relação `tokenable`) pra resolver quem fez a request — mas isso
 * acontece antes de qualquer sistema_id estar no contexto, então tanto o
 * global scope do Eloquent quanto a RLS de `atendentes` bloqueariam essa
 * leitura. `ResolveAtendenteContext` desliga o bypass assim que o atendente
 * é resolvido, no caminho de sucesso.
 *
 * `terminate()` é a rede de segurança: quando a autenticação falha (token
 * ausente/inválido/expirado), `Authenticate` lança a exceção ANTES de
 * chamar `$next()` — `ResolveAtendenteContext` nunca roda nesse caso, e sem
 * isso a flag de bypass ficaria ligada pro resto da conexão. Middleware
 * terminável roda sempre, mesmo quando a pipeline foi interrompida por uma
 * exceção.
 */
class EnableAtendenteAuthRlsBypass
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        app(SistemaContext::class)->ativarBypassParaResolucaoDeAtendente();

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        app(SistemaContext::class)->desativarBypassParaResolucaoDeAtendente();
    }
}
