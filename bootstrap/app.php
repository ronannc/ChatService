<?php

use App\Http\Middleware\EnableAtendenteAuthRlsBypass;
use App\Http\Middleware\EnsureAdminApiKey;
use App\Http\Middleware\EnsureScopeEscreverCliente;
use App\Http\Middleware\EnsureValidTokenCliente;
use App\Http\Middleware\LimparSistemasPermitidosAtendenteAoFinalizar;
use App\Http\Middleware\ResolveAtendenteContext;
use App\Http\Middleware\ResolveAtendenteExternoContext;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.api-key' => EnsureAdminApiKey::class,
            'atendente.auth-bypass' => EnableAtendenteAuthRlsBypass::class,
            'atendente.context' => ResolveAtendenteContext::class,
            'atendente.externo.context' => ResolveAtendenteExternoContext::class,
            'cliente.token' => EnsureValidTokenCliente::class,
            'cliente.scope-escrever' => EnsureScopeEscreverCliente::class,
            'broadcasting.limpar-sistemas-permitidos' => LimparSistemasPermitidosAtendenteAoFinalizar::class,
        ]);

        // Authenticate tem prioridade fixa no framework e rodaria antes de
        // qualquer middleware de rota nosso, independente da ordem no
        // array de rotas — sem isso, a flag de bypass da RLS seria ligada
        // tarde demais (depois do auth:sanctum já ter tentado resolver o
        // atendente pelo token).
        $middleware->prependToPriorityList(
            before: AuthenticatesRequests::class,
            prepend: EnableAtendenteAuthRlsBypass::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
