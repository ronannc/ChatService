<?php

use App\Enums\StatusChamado;
use App\Models\Chamado;
use App\Models\FluxoDefinicao;
use App\Models\Sistema;
use App\Services\Auth\BuscarJwksSegurancaService;
use App\Services\Auth\RepositorioJwks;
use App\Services\Auth\ValidarTokenClienteService;
use App\Support\GuardaHostSeguro;
use Database\Seeders\FluxoDefinicaoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Support\GeradorTokenTeste;

/**
 * Exercita POST /api/chamados/{chamado}/fluxo/avancar (CHAT-023):
 * `AvancarFluxoService` valida a opção contra o nó atual do fixture
 * (ver database/seeders/FluxoDefinicaoSeeder), acumula respostas e resolve
 * o chamado sempre confrontando sistema_id/cliente_ref do token.
 *
 * Depende de Postgres real (RLS/global scope de Chamado) — roda via
 * `make test`, mesmo racional de StoreChamadoTest.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();

    (new FluxoDefinicaoSeeder)->run();

    $this->sistemaCliente = Sistema::factory()->create();

    Http::fake([
        $this->sistemaCliente->jwks_url => Http::response(GeradorTokenTeste::jwks(), 200),
    ]);

    $this->app->bind(ValidarTokenClienteService::class, fn () => new ValidarTokenClienteService(
        new RepositorioJwks(
            new BuscarJwksSegurancaService(new GuardaHostSeguro(fn (string $host): array => ['8.8.8.8'])),
        ),
    ));
});

/**
 * Abre um chamado via endpoint real (já nasce em fluxo_em_andamento,
 * CHAT-023) e devolve o id — evita duplicar a regra de fixture aqui.
 */
function abrirChamadoEmFluxo(string $token): int
{
    $resposta = test()->postJson('/api/chamados', [], [
        'Authorization' => "Bearer {$token}",
    ]);

    $resposta->assertCreated();

    return $resposta->json('id');
}

test('avançar com opção válida atualiza no_atual e acumula respostas_coletadas', function () {
    $token = GeradorTokenTeste::valido(['iss' => $this->sistemaCliente->codigo]);
    $chamadoId = abrirChamadoEmFluxo($token);

    $resposta = $this->postJson("/api/chamados/{$chamadoId}/fluxo/avancar", [
        'opcao' => 'duvida_simples',
    ], [
        'Authorization' => "Bearer {$token}",
    ]);

    $resposta->assertOk()
        ->assertJsonPath('no_atual', 'detalhar')
        ->assertJsonPath('status', StatusChamado::FluxoEmAndamento->value);

    sistemaContext()->set($this->sistemaCliente->codigo);
    $chamado = Chamado::findOrFail($chamadoId);

    expect($chamado->no_atual)->toBe('detalhar');
    expect($chamado->respostas_coletadas)->toBe(['inicio' => ['opcao' => 'duvida_simples']]);
});

test('avançar com opção inexistente no nó atual recebe 422', function () {
    $token = GeradorTokenTeste::valido(['iss' => $this->sistemaCliente->codigo]);
    $chamadoId = abrirChamadoEmFluxo($token);

    $resposta = $this->postJson("/api/chamados/{$chamadoId}/fluxo/avancar", [
        'opcao' => 'opcao_que_nao_existe',
    ], [
        'Authorization' => "Bearer {$token}",
    ]);

    $resposta->assertStatus(422);

    sistemaContext()->set($this->sistemaCliente->codigo);
    $chamado = Chamado::findOrFail($chamadoId);
    expect($chamado->no_atual)->toBe('inicio');
    expect($chamado->respostas_coletadas)->toBe([]);
});

test('avançar chamado que não está em fluxo_em_andamento recebe 409', function () {
    $token = GeradorTokenTeste::valido(['iss' => $this->sistemaCliente->codigo]);
    $chamadoId = abrirChamadoEmFluxo($token);

    // Escalona para tirar o chamado do fluxo (opção com escalonamento: true).
    $this->postJson("/api/chamados/{$chamadoId}/fluxo/avancar", [
        'opcao' => 'falar_com_atendente',
    ], [
        'Authorization' => "Bearer {$token}",
    ])->assertOk()->assertJsonPath('status', StatusChamado::AguardandoFila->value);

    $resposta = $this->postJson("/api/chamados/{$chamadoId}/fluxo/avancar", [
        'opcao' => 'duvida_simples',
    ], [
        'Authorization' => "Bearer {$token}",
    ]);

    $resposta->assertStatus(409);
});

test('mudar a definição current não afeta chamado já preso à versão anterior', function () {
    $token = GeradorTokenTeste::valido(['iss' => $this->sistemaCliente->codigo]);
    $chamadoId = abrirChamadoEmFluxo($token);

    sistemaContext()->set($this->sistemaCliente->codigo);
    $chamado = Chamado::findOrFail($chamadoId);
    $versaoOriginal = $chamado->fluxo_definicao_id;

    // Nova versão da mesma chave, com um nó inicial diferente.
    FluxoDefinicao::create([
        'chave' => FluxoDefinicao::CHAVE_FIXTURE_INICIAL,
        'versao' => 2,
        'definicao' => [
            'no_inicial' => 'outro_no',
            'nos' => [
                'outro_no' => [
                    'tipo' => 'pergunta',
                    'opcoes' => [
                        ['valor' => 'x', 'proximo_no' => 'outro_no'],
                    ],
                ],
            ],
        ],
    ]);

    $resposta = $this->postJson("/api/chamados/{$chamadoId}/fluxo/avancar", [
        'opcao' => 'duvida_simples',
    ], [
        'Authorization' => "Bearer {$token}",
    ]);

    $resposta->assertOk()->assertJsonPath('no_atual', 'detalhar');

    sistemaContext()->set($this->sistemaCliente->codigo);
    expect(Chamado::findOrFail($chamadoId)->fluxo_definicao_id)->toBe($versaoOriginal);
});

test('nó fim com proximo_fluxo encadeia sem criar um novo chamado', function () {
    FluxoDefinicao::create([
        'chave' => 'fluxo-seguinte-teste',
        'versao' => 1,
        'definicao' => [
            'no_inicial' => 'inicio_seguinte',
            'nos' => [
                'inicio_seguinte' => [
                    'tipo' => 'pergunta',
                    'opcoes' => [
                        ['valor' => 'ok', 'proximo_no' => 'fim_seguinte'],
                    ],
                ],
                'fim_seguinte' => [
                    'tipo' => 'fim',
                    'proximo_fluxo' => null,
                ],
            ],
        ],
    ]);

    FluxoDefinicao::where('chave', FluxoDefinicao::CHAVE_FIXTURE_INICIAL)
        ->where('versao', 1)
        ->update(['definicao' => [
            'no_inicial' => 'inicio',
            'nos' => [
                'inicio' => [
                    'tipo' => 'pergunta',
                    'opcoes' => [
                        ['valor' => 'duvida_simples', 'proximo_no' => 'detalhar'],
                        ['valor' => 'falar_com_atendente', 'escalonamento' => true],
                    ],
                ],
                'detalhar' => [
                    'tipo' => 'pergunta',
                    'opcoes' => [
                        ['valor' => 'resolvido', 'proximo_no' => 'fim_duvida'],
                        ['valor' => 'falar_com_atendente', 'escalonamento' => true],
                    ],
                ],
                'fim_duvida' => [
                    'tipo' => 'fim',
                    'proximo_fluxo' => 'fluxo-seguinte-teste',
                ],
            ],
        ]]);

    $token = GeradorTokenTeste::valido(['iss' => $this->sistemaCliente->codigo]);
    $chamadoId = abrirChamadoEmFluxo($token);

    sistemaContext()->set($this->sistemaCliente->codigo);
    $totalChamadosAntes = Chamado::count();

    $this->postJson("/api/chamados/{$chamadoId}/fluxo/avancar", [
        'opcao' => 'duvida_simples',
    ], ['Authorization' => "Bearer {$token}"])->assertOk();

    $resposta = $this->postJson("/api/chamados/{$chamadoId}/fluxo/avancar", [
        'opcao' => 'resolvido',
    ], ['Authorization' => "Bearer {$token}"]);

    $resposta->assertOk()
        ->assertJsonPath('id', $chamadoId)
        ->assertJsonPath('no_atual', 'inicio_seguinte')
        ->assertJsonPath('status', StatusChamado::FluxoEmAndamento->value);

    sistemaContext()->set($this->sistemaCliente->codigo);
    expect(Chamado::count())->toBe($totalChamadosAntes);

    $chamado = Chamado::findOrFail($chamadoId);
    $novaDefinicao = FluxoDefinicao::where('chave', 'fluxo-seguinte-teste')->firstOrFail();
    expect($chamado->fluxo_definicao_id)->toBe($novaDefinicao->id);
});

test('nó fim sem proximo_fluxo muda status para aguardando_fila', function () {
    $token = GeradorTokenTeste::valido(['iss' => $this->sistemaCliente->codigo]);
    $chamadoId = abrirChamadoEmFluxo($token);

    $this->postJson("/api/chamados/{$chamadoId}/fluxo/avancar", [
        'opcao' => 'duvida_simples',
    ], ['Authorization' => "Bearer {$token}"])->assertOk();

    $resposta = $this->postJson("/api/chamados/{$chamadoId}/fluxo/avancar", [
        'opcao' => 'resolvido',
    ], ['Authorization' => "Bearer {$token}"]);

    $resposta->assertOk()
        ->assertJsonPath('status', StatusChamado::AguardandoFila->value)
        ->assertJsonPath('no_atual', 'fim_duvida');
});

test('opção marcada com escalonamento muda status para aguardando_fila', function () {
    $token = GeradorTokenTeste::valido(['iss' => $this->sistemaCliente->codigo]);
    $chamadoId = abrirChamadoEmFluxo($token);

    $resposta = $this->postJson("/api/chamados/{$chamadoId}/fluxo/avancar", [
        'opcao' => 'falar_com_atendente',
    ], ['Authorization' => "Bearer {$token}"]);

    $resposta->assertOk()
        ->assertJsonPath('status', StatusChamado::AguardandoFila->value)
        ->assertJsonPath('no_atual', 'inicio');
});

test('cliente de outro sistema não consegue avançar fluxo de chamado alheio', function () {
    $token = GeradorTokenTeste::valido(['iss' => $this->sistemaCliente->codigo]);
    $chamadoId = abrirChamadoEmFluxo($token);

    $outroSistema = Sistema::factory()->create();
    Http::fake([
        $outroSistema->jwks_url => Http::response(GeradorTokenTeste::jwks(), 200),
    ]);
    $tokenOutroSistema = GeradorTokenTeste::valido(['iss' => $outroSistema->codigo]);

    $resposta = $this->postJson("/api/chamados/{$chamadoId}/fluxo/avancar", [
        'opcao' => 'duvida_simples',
    ], ['Authorization' => "Bearer {$tokenOutroSistema}"]);

    $resposta->assertStatus(404);

    sistemaContext()->set($this->sistemaCliente->codigo);
    expect(Chamado::findOrFail($chamadoId)->no_atual)->toBe('inicio');
});

test('outro cliente do mesmo sistema não consegue avançar fluxo de chamado alheio', function () {
    $token = GeradorTokenTeste::valido(['iss' => $this->sistemaCliente->codigo]);
    $chamadoId = abrirChamadoEmFluxo($token);

    $tokenOutroCliente = GeradorTokenTeste::valido([
        'iss' => $this->sistemaCliente->codigo,
        'sub' => 'outro-cliente-ref',
    ]);

    $resposta = $this->postJson("/api/chamados/{$chamadoId}/fluxo/avancar", [
        'opcao' => 'duvida_simples',
    ], ['Authorization' => "Bearer {$tokenOutroCliente}"]);

    $resposta->assertStatus(404);

    sistemaContext()->set($this->sistemaCliente->codigo);
    expect(Chamado::findOrFail($chamadoId)->no_atual)->toBe('inicio');
});
