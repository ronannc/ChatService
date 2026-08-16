<?php

use App\Enums\StatusChamado;
use App\Models\AtendenteSistema;
use App\Models\Chamado;
use App\Models\Sistema;
use App\Support\AtendenteContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * Exercita GET /api/fila (CHAT-010): ListarFilaChamadosService lê chamados
 * `aguardando_fila` dos sistemas permitidos ao atendente autenticado,
 * bypassando o global scope de Chamado (.ai/rules/mensagem.md) e propagando
 * a lista pro GUC `app.sistemas_permitidos_atendente` lido pela policy RLS
 * de CHAT-006.
 *
 * Depende de Postgres real (RLS/global scope de Chamado) — roda via
 * `make test`, nunca fora do container.
 */
uses(RefreshDatabase::class);

test('atendente vê apenas chamados aguardando fila dos sistemas permitidos', function () {
    $atendente = criarAtendente(['email' => 'ana@chatservice.test']);
    $sistemaBase = $atendente->sistema_id;

    $sistemaConcedido = Sistema::factory()->create();
    AtendenteSistema::factory()->create([
        'atendente_id' => $atendente->id,
        'sistema_id' => $sistemaConcedido->codigo,
    ]);

    sistemaContext()->set($sistemaBase);
    $chamadoSistemaBase = Chamado::factory()->create([
        'sistema_id' => $sistemaBase,
        'status' => StatusChamado::AguardandoFila,
    ]);

    sistemaContext()->set($sistemaConcedido->codigo);
    $chamadoSistemaConcedido = Chamado::factory()->create([
        'sistema_id' => $sistemaConcedido->codigo,
        'status' => StatusChamado::AguardandoFila,
    ]);

    $token = tokenAtendente($atendente);

    $resposta = $this->getJson('/api/fila', ['Authorization' => "Bearer {$token}"]);

    $resposta->assertOk();
    $ids = collect($resposta->json('data'))->pluck('id');

    expect($ids)->toContain($chamadoSistemaBase->id, $chamadoSistemaConcedido->id);
    expect($ids)->toHaveCount(2);
});

test('chamados de sistemas não associados ao atendente não aparecem na fila', function () {
    $atendente = criarAtendente(['email' => 'ana@chatservice.test']);
    $sistemaNaoPermitido = Sistema::factory()->create();

    sistemaContext()->set($sistemaNaoPermitido->codigo);
    Chamado::factory()->create([
        'sistema_id' => $sistemaNaoPermitido->codigo,
        'status' => StatusChamado::AguardandoFila,
    ]);

    $token = tokenAtendente($atendente);

    $resposta = $this->getJson('/api/fila', ['Authorization' => "Bearer {$token}"]);

    $resposta->assertOk();
    expect($resposta->json('data'))->toBeEmpty();
});

test('chamados com status diferente de aguardando fila não aparecem na fila', function () {
    $atendente = criarAtendente(['email' => 'ana@chatservice.test']);
    $sistemaBase = $atendente->sistema_id;

    sistemaContext()->set($sistemaBase);

    $chamadoAguardando = Chamado::factory()->create([
        'sistema_id' => $sistemaBase,
        'status' => StatusChamado::AguardandoFila,
    ]);

    foreach ([
        StatusChamado::EmAtendimento,
        StatusChamado::AguardandoCliente,
        StatusChamado::Resolvido,
        StatusChamado::Finalizado,
    ] as $status) {
        Chamado::factory()->create([
            'sistema_id' => $sistemaBase,
            'status' => $status,
        ]);
    }

    $token = tokenAtendente($atendente);

    $resposta = $this->getJson('/api/fila', ['Authorization' => "Bearer {$token}"]);

    $resposta->assertOk();
    $ids = collect($resposta->json('data'))->pluck('id');

    expect($ids)->toEqual(collect([$chamadoAguardando->id]));
});

test('fila é ordenada por created_at ascendente, com id como desempate em caso de created_at idêntico', function () {
    $atendente = criarAtendente(['email' => 'ana@chatservice.test']);
    $sistemaBase = $atendente->sistema_id;

    sistemaContext()->set($sistemaBase);

    $agora = now();

    $chamadoMaisAntigo = Chamado::factory()->create([
        'sistema_id' => $sistemaBase,
        'status' => StatusChamado::AguardandoFila,
        'created_at' => $agora->copy()->subMinutes(10),
    ]);

    // Mesmo created_at que $chamadoEmpate2 — força o tie-breaker por id
    // (ordem de criação garante id crescente).
    $chamadoEmpate1 = Chamado::factory()->create([
        'sistema_id' => $sistemaBase,
        'status' => StatusChamado::AguardandoFila,
        'created_at' => $agora->copy()->subMinutes(5),
    ]);

    $chamadoEmpate2 = Chamado::factory()->create([
        'sistema_id' => $sistemaBase,
        'status' => StatusChamado::AguardandoFila,
        'created_at' => $agora->copy()->subMinutes(5),
    ]);

    $chamadoMaisRecente = Chamado::factory()->create([
        'sistema_id' => $sistemaBase,
        'status' => StatusChamado::AguardandoFila,
        'created_at' => $agora,
    ]);

    $token = tokenAtendente($atendente);

    $resposta = $this->getJson('/api/fila', ['Authorization' => "Bearer {$token}"]);

    $resposta->assertOk();
    $ids = collect($resposta->json('data'))->pluck('id');

    expect($ids)->toEqual(collect([
        $chamadoMaisAntigo->id,
        $chamadoEmpate1->id,
        $chamadoEmpate2->id,
        $chamadoMaisRecente->id,
    ]));
});

test('fila fica vazia sem tocar o banco quando sistemasPermitidos() retorna vazio', function () {
    // Cenário defensivo: hoje todo atendente autenticado sempre tem ao menos
    // o sistema-base em sistemasPermitidos() (AtendenteContext::sistemasPermitidos
    // sempre inclui $atendente->sistema_id). Não há caminho real de produção
    // que produza uma coleção vazia — por isso simulamos via stub do
    // AtendenteContext no container, em vez de tentar montar esse estado a
    // partir do banco.
    $atendente = criarAtendente(['email' => 'ana@chatservice.test']);
    $token = tokenAtendente($atendente);

    $this->mock(AtendenteContext::class, function ($mock) {
        // `set()` ainda é chamado por ResolveAtendenteContext (middleware
        // do grupo Sanctum) antes do controller rodar — só o resultado de
        // sistemasPermitidos() é forçado a vazio aqui.
        $mock->shouldReceive('set')->withAnyArgs();
        $mock->shouldReceive('sistemasPermitidos')->andReturn(collect());
    });

    $resposta = $this->getJson('/api/fila', ['Authorization' => "Bearer {$token}"]);

    $resposta->assertOk();
    expect($resposta->json('data'))->toBeEmpty();
});

test('GUC de current_sistema_id sujo de uma conexão reaproveitada não vaza chamados de sistema não permitido pra fila', function () {
    $atendente = criarAtendente(['email' => 'ana@chatservice.test']);
    $sistemaBase = $atendente->sistema_id;

    sistemaContext()->set($sistemaBase);
    $chamadoPermitido = Chamado::factory()->create([
        'sistema_id' => $sistemaBase,
        'status' => StatusChamado::AguardandoFila,
    ]);

    // Sistema "sujo": nem sistema-base nem concedido via atendente_sistema —
    // não deveria aparecer na fila deste atendente em hipótese alguma.
    $sistemaSujo = Sistema::factory()->create();
    sistemaContext()->set($sistemaSujo->codigo);
    $chamadoSujo = Chamado::factory()->create([
        'sistema_id' => $sistemaSujo->codigo,
        'status' => StatusChamado::AguardandoFila,
    ]);

    // Imita uma conexão de pool reaproveitada de uma request anterior que
    // deixou app.current_sistema_id apontando pro sistema sujo — achado real
    // documentado em .ai/rules/mensagem.md (CHAT-008): a policy RLS de
    // isolamento (`chamados_isolamento_sistema`) se combina via OR com
    // `chamados_sistemas_permitidos_atendente`, então um current_sistema_id
    // sujo poderia, em tese, liberar linhas que o atendente não deveria ver.
    DB::statement('SELECT set_config(?, ?, false)', ['app.current_sistema_id', $sistemaSujo->codigo]);

    $token = tokenAtendente($atendente);

    $resposta = $this->getJson('/api/fila', ['Authorization' => "Bearer {$token}"]);

    $resposta->assertOk();
    $ids = collect($resposta->json('data'))->pluck('id');

    expect($ids)->toContain($chamadoPermitido->id);
    expect($ids)->not->toContain($chamadoSujo->id);
});
