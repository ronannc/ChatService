<?php

use App\Enums\StatusChamado;
use App\Models\Chamado;
use App\Models\Sistema;
use App\Services\Auth\BuscarJwksSegurancaService;
use App\Services\Auth\RepositorioJwks;
use App\Services\Auth\ValidarTokenClienteService;
use App\Support\ContratoTokenCliente;
use App\Support\GuardaHostSeguro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Support\GeradorTokenTeste;

/**
 * Exercita POST /api/chamados (CHAT-008): `cliente.token` resolve o
 * contexto, `cliente.scope-escrever` autoriza a escrita e
 * StoreChamadoService cria o chamado a partir do token, nunca do body.
 *
 * Depende de Postgres real (RLS/global scope de Chamado) — roda via
 * `make test`, nunca fora do container (mesmo racional de
 * AutorizarCanalChamadoTest).
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();

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

test('cliente com escopo de escrita consegue abrir um chamado', function () {
    $token = GeradorTokenTeste::valido(['iss' => $this->sistemaCliente->codigo]);

    $resposta = $this->postJson('/api/chamados', [], [
        'Authorization' => "Bearer {$token}",
    ]);

    $resposta->assertCreated()
        ->assertJsonPath('sistema_id', $this->sistemaCliente->codigo)
        ->assertJsonPath('cliente_ref', GeradorTokenTeste::SUB)
        ->assertJsonPath('status', StatusChamado::AguardandoFila->value);

    sistemaContext()->set($this->sistemaCliente->codigo);

    $chamado = Chamado::first();
    expect($chamado)->not->toBeNull();
    expect($chamado->sistema_id)->toBe($this->sistemaCliente->codigo);
    expect($chamado->cliente_ref)->toBe(GeradorTokenTeste::SUB);
    expect($chamado->status)->toBe(StatusChamado::AguardandoFila);
});

test('cliente sem escopo de escrita recebe 403', function () {
    $token = GeradorTokenTeste::valido([
        'iss' => $this->sistemaCliente->codigo,
        'scope' => ContratoTokenCliente::SCOPE_LER,
    ]);

    $resposta = $this->postJson('/api/chamados', [], [
        'Authorization' => "Bearer {$token}",
    ]);

    $resposta->assertForbidden();

    sistemaContext()->set($this->sistemaCliente->codigo);
    expect(Chamado::count())->toBe(0);
});

test('sistema_id enviado no corpo da requisição é ignorado — vale sempre o iss do token', function () {
    $token = GeradorTokenTeste::valido(['iss' => $this->sistemaCliente->codigo]);

    $resposta = $this->postJson('/api/chamados', [
        'sistema_id' => 'outro-sistema-malicioso',
    ], [
        'Authorization' => "Bearer {$token}",
    ]);

    $resposta->assertCreated()
        ->assertJsonPath('sistema_id', $this->sistemaCliente->codigo);

    sistemaContext()->set($this->sistemaCliente->codigo);
    expect(Chamado::first()->sistema_id)->toBe($this->sistemaCliente->codigo);
});

test('chamado criado sob um sistema não é visível no contexto de outro sistema', function () {
    $token = GeradorTokenTeste::valido(['iss' => $this->sistemaCliente->codigo]);

    $resposta = $this->postJson('/api/chamados', [], [
        'Authorization' => "Bearer {$token}",
    ]);
    $resposta->assertCreated();

    $outroSistema = Sistema::factory()->create();
    sistemaContext()->set($outroSistema->codigo);

    // Global scope de Chamado (SistemaScope) filtra por sistema_id do
    // contexto atual — o chamado criado sob $this->sistemaCliente não pode
    // aparecer aqui, mesmo consultando sem nenhum filtro explícito.
    expect(Chamado::count())->toBe(0);

    sistemaContext()->set($this->sistemaCliente->codigo);
    expect(Chamado::count())->toBe(1);
});

test('requisição sem token é rejeitada com 401', function () {
    $resposta = $this->postJson('/api/chamados');

    $resposta->assertStatus(401);

    sistemaContext()->set($this->sistemaCliente->codigo);
    expect(Chamado::count())->toBe(0);
});
