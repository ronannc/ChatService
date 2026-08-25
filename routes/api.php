<?php

use App\Http\Controllers\Admin\SistemaController;
use App\Http\Controllers\Atendente\AuthController;
use App\Http\Controllers\Atendente\MeController;
use App\Http\Controllers\ChamadoController;
use App\Http\Controllers\FluxoController;
use App\Http\Controllers\MensagemController;
use Illuminate\Broadcasting\BroadcastController;
use Illuminate\Support\Facades\Route;

Route::middleware(['admin.api-key', 'throttle:30,1'])->prefix('admin')->group(function () {
    Route::post('sistemas', [SistemaController::class, 'store']);
    Route::patch('sistemas/{sistema:codigo}', [SistemaController::class, 'update']);
});

Route::post('atendentes/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

/**
 * `broadcasting.limpar-sistemas-permitidos` (LimparSistemasPermitidosAtendenteAoFinalizar)
 * aplica-se a todo o grupo, não só a `/fila`: qualquer rota aqui pode, no
 * futuro, setar `app.sistemas_permitidos_atendente` (hoje é `/fila`, via
 * `ListarFilaChamadosService`), e o GUC não pode sobreviver além da request.
 */
Route::middleware(['atendente.auth-bypass', 'auth:sanctum', 'atendente.context', 'broadcasting.limpar-sistemas-permitidos'])->group(function () {
    Route::get('atendentes/me', [MeController::class, 'show']);
    Route::get('fila', [ChamadoController::class, 'index']);

    /**
     * Assumir chamado da fila (CHAT-011). `{chamado}` chega como int cru no
     * controller, sem Route Model Binding implícito — o binding aplicaria
     * `SistemaScope` (whereRaw(1=0) neste contexto de atendente Sanctum sem
     * `SistemaContext::set()`), gerando 404 falso antes mesmo do Service
     * checar permissão. Ver AssumirChamadoService e
     * .ai/rules/chamado-fila.md.
     */
    Route::post('chamados/{chamado}/assumir', [ChamadoController::class, 'assumir']);
});

/**
 * Atendente externo (CHAT-005B): mesmo JWT/JWKS do cliente final
 * (`cliente.token`), diferenciado pela claim `role=atendente`. Grupo de
 * rotas dedicado — nunca reaproveita as rotas acima, que só aceitam
 * Sanctum. `atendente.externo.context` provisiona just-in-time o atendente
 * (sem cadastro prévio) e roda depois de `cliente.token`, que já resolveu
 * `SistemaContext` a partir do `iss` do token.
 */
Route::middleware(['cliente.token', 'atendente.externo.context'])->prefix('atendente-externo')->group(function () {
    Route::get('me', [MeController::class, 'show']);
});

/**
 * Autorização de canal privado de broadcasting (CHAT-006). Path final:
 * /api/broadcasting/auth — o cliente Echo precisa apontar `authEndpoint`
 * pra cá, não para o default `/broadcasting/auth` do Laravel.
 *
 * `atendente.auth-bypass` liga o bypass de RLS de `atendentes` durante toda
 * a execução do controller: diferente das rotas normais de atendente, aqui
 * não há um middleware `auth:sanctum` separado que force a resolução antes
 * do controller rodar — quem resolve o guard `sanctum` é o próprio
 * `Broadcaster::retrieveUser()`, dentro do `BroadcastController::authenticate`
 * (ver routes/channels.php e a opção `guards` do canal). Sem o bypass
 * ativo nesse momento, o Sanctum não conseguiria ler a linha do atendente
 * (RLS de `atendentes` bloquearia antes de qualquer sistema_id existir no
 * contexto). Esse bypass é escopado à leitura da própria linha do atendente
 * (`SistemaScope`, só pro model `Atendente`, ver GUC_BYPASS_RESOLUCAO_ATENDENTE)
 * — ele não amplia, em nenhum momento, o acesso a `chamados`/`mensagens`:
 * quem decide quais chamados o atendente enxerga é a policy de RLS separada
 * `chamados_sistemas_permitidos_atendente`, controlada pelo GUC abaixo.
 *
 * `broadcasting.limpar-sistemas-permitidos` garante que o GUC
 * `app.sistemas_permitidos_atendente` (setado por
 * `AutorizarCanalChamadoService` quando quem autoriza é um atendente) não
 * sobrevive além desta request — ver
 * `LimparSistemasPermitidosAtendenteAoFinalizar`.
 */
Route::post('broadcasting/auth', [BroadcastController::class, 'authenticate'])
    ->middleware(['atendente.auth-bypass', 'broadcasting.limpar-sistemas-permitidos', 'throttle:60,1']);

/**
 * Abertura de chamado pelo cliente final (CHAT-008). `cliente.token` resolve
 * autenticação/assinatura e o `SistemaContext` a partir do `iss`;
 * `cliente.scope-escrever` é o gate de autorização de escopo, isolado do
 * anterior (.ai/rules/tokens.md — os dois mecanismos não se misturam).
 */
Route::post('chamados', [ChamadoController::class, 'store'])
    ->middleware(['cliente.token', 'cliente.scope-escrever']);

/**
 * Avançar o fluxo fixo do chamado (CHAT-023), sempre pelo cliente final
 * dono do chamado — `AvancarFluxoService` confronta `{chamado}` contra
 * `sistema_id`/`cliente_ref` do próprio token, nunca aceita o id "cru"
 * (mesmo princípio de `StoreChamadoService`/`EnsureAutorizadoEnviarMensagem`).
 */
Route::post('chamados/{chamado}/fluxo/avancar', [FluxoController::class, 'avancar'])
    ->middleware(['cliente.token', 'cliente.scope-escrever']);

/**
 * Troca de mensagens de um chamado (CHAT-009). O endpoint é compartilhado
 * por cliente final (JWT) e atendente interno (Sanctum) — mecanismos
 * mutuamente exclusivos, então não dá para reaproveitar `cliente.token`
 * nem `auth:sanctum` sozinhos aqui (cada um aborta se o mecanismo não for
 * o seu). `mensagem.identificar-cliente`/`mensagem.identificar-atendente`
 * são as contrapartes dual-auth: cada uma decide, só pelo formato do bearer
 * token, se deve tentar autenticar — a validação de cada mecanismo
 * continua isolada na própria classe (.ai/rules/tokens.md).
 *
 * Guard de escrita (`mensagem.autorizar-enviar`) e de leitura
 * (`mensagem.autorizar-ler`) são middlewares distintos: só o de escrita
 * bloqueia em chamado resolvido/finalizado.
 */
Route::middleware(['mensagem.identificar-cliente', 'mensagem.identificar-atendente'])
    ->prefix('chamados/{chamado}/mensagens')
    ->group(function () {
        Route::post('/', [MensagemController::class, 'store'])
            ->middleware('mensagem.autorizar-enviar');

        Route::get('/', [MensagemController::class, 'index'])
            ->middleware('mensagem.autorizar-ler');
    });
