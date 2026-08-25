<?php

use App\Enums\StatusChamado;
use App\Events\ChamadoAssumido;
use App\Models\Atendente;
use App\Models\AtendenteSistema;
use App\Models\Chamado;
use App\Models\Sistema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

/**
 * `criarAtendente()` sempre cria um Sistema novo e seta o SistemaContext pra
 * ele antes do insert (RLS de `atendentes` exige que `sistema_id` bata com o
 * GUC no momento da escrita) — não dá pra usar esse helper com um override
 * de `sistema_id` apontando pra um sistema diferente do que ele acabou de
 * criar. Este helper cria um segundo atendente no MESMO sistema já existente.
 */
function criarAtendenteNoSistema(string $sistemaId, array $overrides = []): Atendente
{
    sistemaContext()->set($sistemaId);

    return Atendente::factory()->create(array_merge([
        'sistema_id' => $sistemaId,
        'senha' => Hash::make('password'),
    ], $overrides));
}

/**
 * Exercita POST /api/chamados/{chamado}/assumir (CHAT-011):
 * AssumirChamadoService move um chamado `aguardando_fila` pra
 * `em_atendimento` via UPDATE condicional atômico, gravando o atendente que
 * assumiu e disparando ChamadoAssumido no canal `chamado.{id}` (CHAT-006).
 *
 * Depende de Postgres real (RLS/global scope de Chamado) — roda via
 * `make test`, nunca fora do container.
 */
uses(RefreshDatabase::class);

/**
 * `throttle:10,1` em `/api/atendentes/login` usa o cache store real (Redis
 * do container — `docker-compose.yml` define `CACHE_STORE: redis` como
 * variável de ambiente real, que tem precedência sobre o `CACHE_STORE=array`
 * do `phpunit.xml`), então o contador de tentativas persiste entre execuções
 * da suíte inteira, não só dentro de um único `php artisan test`. Este
 * arquivo faz vários logins por teste (cenário de dois atendentes
 * concorrendo pelo mesmo chamado) e passou a estourar o limite ao rodar a
 * suíte repetidamente em um curto intervalo — sintoma de infra, não do
 * comportamento sob teste. Desliga o throttle aqui pra manter os testes
 * determinísticos independentemente do estado do Redis compartilhado.
 */
beforeEach(function () {
    $this->withoutMiddleware(ThrottleRequests::class);
});

test('atendente com permissão assume chamado aguardando fila', function () {
    Event::fake([ChamadoAssumido::class]);

    $atendente = criarAtendente(['email' => 'ana@chatservice.test']);
    $sistemaBase = $atendente->sistema_id;

    sistemaContext()->set($sistemaBase);
    $chamado = Chamado::factory()->create([
        'sistema_id' => $sistemaBase,
        'status' => StatusChamado::AguardandoFila,
    ]);

    $token = tokenAtendente($atendente);

    $resposta = $this->postJson("/api/chamados/{$chamado->id}/assumir", [], [
        'Authorization' => "Bearer {$token}",
    ]);

    $resposta->assertOk();
    $resposta->assertJsonPath('status', StatusChamado::EmAtendimento->value);
    $resposta->assertJsonPath('atendente_atual_id', $atendente->id);

    sistemaContext()->set($sistemaBase);
    $chamadoAtualizado = Chamado::find($chamado->id);
    expect($chamadoAtualizado->status)->toBe(StatusChamado::EmAtendimento);
    expect($chamadoAtualizado->atendente_atual_id)->toBe($atendente->id);

    Event::assertDispatched(ChamadoAssumido::class, function (ChamadoAssumido $event) use ($chamado, $atendente) {
        return $event->chamado->is($chamado)
            && $event->nomeAtendente === $atendente->nome
            && $event->broadcastWith()['atendente_atual_id'] === $atendente->id
            && $event->broadcastWith()['status'] === StatusChamado::EmAtendimento->value;
    });
});

test('atendente com permissão via atendente_sistema (não o sistema-base) também consegue assumir', function () {
    $atendente = criarAtendente(['email' => 'ana@chatservice.test']);
    $sistemaConcedido = Sistema::factory()->create();
    AtendenteSistema::factory()->create([
        'atendente_id' => $atendente->id,
        'sistema_id' => $sistemaConcedido->codigo,
    ]);

    sistemaContext()->set($sistemaConcedido->codigo);
    $chamado = Chamado::factory()->create([
        'sistema_id' => $sistemaConcedido->codigo,
        'status' => StatusChamado::AguardandoFila,
    ]);

    $token = tokenAtendente($atendente);

    $resposta = $this->postJson("/api/chamados/{$chamado->id}/assumir", [], [
        'Authorization' => "Bearer {$token}",
    ]);

    $resposta->assertOk();
    $resposta->assertJsonPath('status', StatusChamado::EmAtendimento->value);
});

test('segunda tentativa de assumir o mesmo chamado recebe 409 por causa do UPDATE condicional', function () {
    $atendenteA = criarAtendente(['email' => 'ana@chatservice.test']);
    $sistemaBase = $atendenteA->sistema_id;

    $atendenteB = criarAtendenteNoSistema($sistemaBase, ['email' => 'bia@chatservice.test']);

    sistemaContext()->set($sistemaBase);
    $chamado = Chamado::factory()->create([
        'sistema_id' => $sistemaBase,
        'status' => StatusChamado::AguardandoFila,
    ]);

    $tokenA = tokenAtendente($atendenteA);
    $tokenB = tokenAtendente($atendenteB);

    $primeiraResposta = $this->postJson("/api/chamados/{$chamado->id}/assumir", [], [
        'Authorization' => "Bearer {$tokenA}",
    ]);
    $primeiraResposta->assertOk();

    $segundaResposta = $this->postJson("/api/chamados/{$chamado->id}/assumir", [], [
        'Authorization' => "Bearer {$tokenB}",
    ]);
    $segundaResposta->assertStatus(409);

    // Prova que o UPDATE condicional (WHERE status = 'aguardando_fila') de
    // fato bloqueou a segunda escrita: o chamado continua com o atendente
    // da primeira tentativa, não foi sobrescrito pela segunda.
    sistemaContext()->set($sistemaBase);
    $chamadoFinal = Chamado::find($chamado->id);
    expect($chamadoFinal->atendente_atual_id)->toBe($atendenteA->id);
});

test('chamado inexistente retorna 404', function () {
    $atendente = criarAtendente(['email' => 'ana@chatservice.test']);
    $token = tokenAtendente($atendente);

    $resposta = $this->postJson('/api/chamados/999999/assumir', [], [
        'Authorization' => "Bearer {$token}",
    ]);

    $resposta->assertStatus(404);
});

test('chamado de sistema não permitido ao atendente retorna 404, não 403 (não vaza existência de outro tenant)', function () {
    $atendente = criarAtendente(['email' => 'ana@chatservice.test']);
    $sistemaNaoPermitido = Sistema::factory()->create();

    sistemaContext()->set($sistemaNaoPermitido->codigo);
    $chamado = Chamado::factory()->create([
        'sistema_id' => $sistemaNaoPermitido->codigo,
        'status' => StatusChamado::AguardandoFila,
    ]);

    sistemaContext()->clear();
    $token = tokenAtendente($atendente);

    $resposta = $this->postJson("/api/chamados/{$chamado->id}/assumir", [], [
        'Authorization' => "Bearer {$token}",
    ]);

    $resposta->assertStatus(404);

    sistemaContext()->set($sistemaNaoPermitido->codigo);
    $chamadoInalterado = Chamado::find($chamado->id);
    expect($chamadoInalterado->status)->toBe(StatusChamado::AguardandoFila);
    expect($chamadoInalterado->atendente_atual_id)->toBeNull();
});

/**
 * Regressão do gap de defesa em profundidade (reportado ao dev, corrigido
 * por dev-2 com ->whereIn('sistema_id', $sistemasPermitidos) nas 3 queries
 * de AssumirChamadoService — mesmo padrão de ListarFilaChamadosService,
 * .ai/rules/chamado-fila.md). Reproduz a mesma técnica do teste equivalente
 * em ListarFilaChamadosTest ("GUC de current_sistema_id sujo..."): deixa
 * `app.current_sistema_id` sujo na conexão (sem limpar) e confirma que o
 * whereIn() client-side barra o vazamento mesmo com a policy RLS OR enganada.
 */
test('GUC de current_sistema_id sujo não permite assumir chamado de sistema não permitido', function () {
    $atendente = criarAtendente(['email' => 'ana@chatservice.test']);
    $sistemaNaoPermitido = Sistema::factory()->create();

    sistemaContext()->set($sistemaNaoPermitido->codigo);
    $chamado = Chamado::factory()->create([
        'sistema_id' => $sistemaNaoPermitido->codigo,
        'status' => StatusChamado::AguardandoFila,
    ]);

    // Simula conexão de pool reaproveitada deixando app.current_sistema_id
    // sujo, sem limpar — cenário que ListarFilaChamadosTest já cobre
    // explicitamente para /fila via DB::statement(set_config(...)).
    $token = tokenAtendente($atendente);

    $resposta = $this->postJson("/api/chamados/{$chamado->id}/assumir", [], [
        'Authorization' => "Bearer {$token}",
    ]);

    $resposta->assertStatus(404);
});

test('chamado que não está aguardando fila retorna 409 e não altera o atendente responsável', function (StatusChamado $status) {
    $atendente = criarAtendente(['email' => 'ana@chatservice.test']);
    $sistemaBase = $atendente->sistema_id;
    $outroAtendente = criarAtendenteNoSistema($sistemaBase, ['email' => 'bia@chatservice.test']);

    sistemaContext()->set($sistemaBase);
    $chamado = Chamado::factory()->create([
        'sistema_id' => $sistemaBase,
        'status' => $status,
        'atendente_atual_id' => $outroAtendente->id,
    ]);

    $token = tokenAtendente($atendente);

    $resposta = $this->postJson("/api/chamados/{$chamado->id}/assumir", [], [
        'Authorization' => "Bearer {$token}",
    ]);

    $resposta->assertStatus(409);

    sistemaContext()->set($sistemaBase);
    $chamadoInalterado = Chamado::find($chamado->id);
    expect($chamadoInalterado->status)->toBe($status);
    expect($chamadoInalterado->atendente_atual_id)->toBe($outroAtendente->id);
})->with([
    StatusChamado::EmAtendimento,
    StatusChamado::AguardandoCliente,
    StatusChamado::Resolvido,
    StatusChamado::Finalizado,
]);

test('atendente sem token não consegue assumir chamado', function () {
    $atendente = criarAtendente(['email' => 'ana@chatservice.test']);
    $sistemaBase = $atendente->sistema_id;

    sistemaContext()->set($sistemaBase);
    $chamado = Chamado::factory()->create([
        'sistema_id' => $sistemaBase,
        'status' => StatusChamado::AguardandoFila,
    ]);

    $resposta = $this->postJson("/api/chamados/{$chamado->id}/assumir");

    $resposta->assertStatus(401);
});
