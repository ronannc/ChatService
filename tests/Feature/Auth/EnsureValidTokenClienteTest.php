<?php

use App\Enums\StatusSistema;
use App\Models\Sistema;
use App\Services\Auth\BuscarJwksSegurancaService;
use App\Services\Auth\RepositorioJwks;
use App\Services\Auth\ValidarTokenClienteService;
use App\Support\GuardaHostSeguro;
use App\Support\SistemaContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\Support\GeradorTokenTeste;

/**
 * Exercita `App\Http\Middleware\EnsureValidTokenCliente` (alias
 * `cliente.token`) ponta a ponta contra uma rota registrada só para o
 * teste: ainda não existe rota real de cliente (CHAT-007/008 é quem cria).
 */
beforeEach(function () {
    prepararTabelaSistemasParaTeste();
    Cache::flush();

    $this->sistema = Sistema::factory()->create([
        'codigo' => GeradorTokenTeste::SISTEMA_CODIGO,
        'jwks_url' => 'https://gestaodeoficinas.example.com/.well-known/jwks.json',
        'status' => StatusSistema::Ativo,
    ]);

    Http::fake([
        $this->sistema->jwks_url => Http::response(GeradorTokenTeste::jwks(), 200),
    ]);

    // Substitui a implementação real por uma que usa o resolvedor de host
    // falso, para não depender de DNS/rede real no teste.
    $this->app->bind(ValidarTokenClienteService::class, fn () => new ValidarTokenClienteService(
        new RepositorioJwks(
            new BuscarJwksSegurancaService(new GuardaHostSeguro(fn (string $host): array => ['8.8.8.8'])),
        ),
    ));

    Route::middleware('cliente.token')->get('/api/__teste/cliente', function (Request $request) {
        return response()->json([
            'sub' => $request->attributes->get('token_cliente')->sub,
        ]);
    });
});

test('token válido resolve o contexto de sistema_id via SistemaContext::set e chega ao controller', function () {
    // SistemaContext::set() propaga pro Postgres via `set_config` (RLS),
    // indisponível no sqlite usado fora do container Docker — aqui
    // verificamos que o middleware chama exatamente esse mecanismo com o
    // `iss` do token, sem depender de Postgres real.
    $this->partialMock(SistemaContext::class, function ($mock) {
        $mock->shouldReceive('set')->once()->with(GeradorTokenTeste::SISTEMA_CODIGO);
    });

    $resposta = $this->getJson('/api/__teste/cliente', [
        'Authorization' => 'Bearer '.GeradorTokenTeste::valido(),
    ]);

    $resposta->assertOk()->assertJson([
        'sub' => GeradorTokenTeste::SUB,
    ]);
});

test('token inválido é rejeitado com 401 genérico', function () {
    $resposta = $this->getJson('/api/__teste/cliente', [
        'Authorization' => 'Bearer '.GeradorTokenTeste::audienciaErrada(),
    ]);

    $resposta->assertStatus(401)->assertJson(['message' => 'Token inválido.']);
});

test('requisição sem authorization header é rejeitada com 401', function () {
    $resposta = $this->getJson('/api/__teste/cliente');

    $resposta->assertStatus(401);
});

test('sistema inativo é rejeitado com o mesmo 401 genérico', function () {
    $inativo = Sistema::factory()->create([
        'status' => StatusSistema::Inativo,
        'jwks_url' => $this->sistema->jwks_url,
    ]);

    $resposta = $this->getJson('/api/__teste/cliente', [
        'Authorization' => 'Bearer '.GeradorTokenTeste::issSistemaInativo($inativo->codigo),
    ]);

    $resposta->assertStatus(401)->assertJson(['message' => 'Token inválido.']);
});
