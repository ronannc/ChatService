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
 * Exercita que POST /api/chamados (CHAT-023) já nasce dentro do fluxo fixo
 * de fixture, em vez de `aguardando_fila` puro (CHAT-008).
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

test('chamado novo nasce em fluxo_em_andamento com o fixture inicial aplicado', function () {
    $token = GeradorTokenTeste::valido(['iss' => $this->sistemaCliente->codigo]);

    $resposta = $this->postJson('/api/chamados', [], [
        'Authorization' => "Bearer {$token}",
    ]);

    $resposta->assertCreated()
        ->assertJsonPath('status', StatusChamado::FluxoEmAndamento->value)
        ->assertJsonPath('no_atual', 'inicio');

    sistemaContext()->set($this->sistemaCliente->codigo);

    $definicao = FluxoDefinicao::where('chave', FluxoDefinicao::CHAVE_FIXTURE_INICIAL)->firstOrFail();
    $chamado = Chamado::firstOrFail();

    expect($chamado->status)->toBe(StatusChamado::FluxoEmAndamento);
    expect($chamado->fluxo_definicao_id)->toBe($definicao->id);
    expect($chamado->no_atual)->toBe('inicio');
    expect($chamado->respostas_coletadas)->toBe([]);
});
