<?php

namespace App\Http\Middleware;

use App\Models\Atendente;
use App\Support\AtendenteContext;
use App\Support\SistemaContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Roda depois de `auth:sanctum`. Resolve o atendente autenticado pro
 * AtendenteContext e desliga o bypass ligado por `EnableAtendenteAuthRlsBypass`
 * antes do controller rodar — o resto da request volta a respeitar o
 * isolamento por sistema normalmente.
 */
class ResolveAtendenteContext
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $atendente = $request->user();

        if ($atendente instanceof Atendente) {
            app(AtendenteContext::class)->set($atendente);
        }

        app(SistemaContext::class)->desativarBypassParaResolucaoDeAtendente();

        return $next($request);
    }
}
