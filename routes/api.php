<?php

use App\Http\Controllers\Admin\SistemaController;
use App\Http\Controllers\Atendente\AuthController;
use App\Http\Controllers\Atendente\MeController;
use Illuminate\Broadcasting\BroadcastController;
use Illuminate\Support\Facades\Route;

Route::middleware(['admin.api-key', 'throttle:30,1'])->prefix('admin')->group(function () {
    Route::post('sistemas', [SistemaController::class, 'store']);
    Route::patch('sistemas/{sistema:codigo}', [SistemaController::class, 'update']);
});

Route::post('atendentes/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware(['atendente.auth-bypass', 'auth:sanctum', 'atendente.context'])->group(function () {
    Route::get('atendentes/me', [MeController::class, 'show']);
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
