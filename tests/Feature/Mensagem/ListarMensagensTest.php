<?php

use App\Enums\StatusChamado;
use App\Models\Atendente;
use App\Models\Chamado;
use App\Models\Mensagem;
use App\Models\Sistema;
use App\Services\Auth\BuscarJwksSegurancaService;
use App\Services\Auth\RepositorioJwks;
use App\Services\Auth\ValidarTokenClienteService;
use App\Support\GuardaHostSeguro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\Support\GeradorTokenTeste;

/**
 * Exercita GET /api/chamados/{chamado}/mensagens (CHAT-009): guard de
 * LEITURA (`EnsureParticipanteChamado`), deliberadamente indiferente ao
 * status do chamado — diferente do guard de escrita.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();

    $this->sistema = Sistema::factory()->create();

    Http::fake([
        $this->sistema->jwks_url => Http::response(GeradorTokenTeste::jwks(), 200),
    ]);

    $this->app->bind(ValidarTokenClienteService::class, fn () => new ValidarTokenClienteService(
        new RepositorioJwks(
            new BuscarJwksSegurancaService(new GuardaHostSeguro(fn (string $host): array => ['8.8.8.8'])),
        ),
    ));

    sistemaContext()->set($this->sistema->codigo);

    $this->atendente = Atendente::factory()->create([
        'sistema_id' => $this->sistema->codigo,
        'email' => 'ana@chatservice.test',
        'senha' => Hash::make('password'),
    ]);

    $this->chamado = Chamado::factory()->create([
        'sistema_id' => $this->sistema->codigo,
        'cliente_ref' => GeradorTokenTeste::SUB,
        'atendente_atual_id' => $this->atendente->id,
        'status' => StatusChamado::EmAtendimento,
    ]);

    Mensagem::factory()->count(3)->create([
        'sistema_id' => $this->sistema->codigo,
        'chamado_id' => $this->chamado->id,
    ]);
});

test('cliente dono lê o histórico de mensagens mesmo com o chamado resolvido', function () {
    $this->chamado->update(['status' => StatusChamado::Resolvido]);

    $token = GeradorTokenTeste::valido(['iss' => $this->sistema->codigo]);

    $resposta = $this->getJson("/api/chamados/{$this->chamado->id}/mensagens", [
        'Authorization' => "Bearer {$token}",
    ]);

    $resposta->assertOk();
    $resposta->assertJsonCount(3, 'data');
});

test('atendente responsável lê o histórico de mensagens mesmo com o chamado finalizado', function () {
    $this->chamado->update(['status' => StatusChamado::Finalizado]);

    $token = tokenAtendente($this->atendente);

    $resposta = $this->getJson("/api/chamados/{$this->chamado->id}/mensagens", [
        'Authorization' => "Bearer {$token}",
    ]);

    $resposta->assertOk();
    $resposta->assertJsonCount(3, 'data');
});

test('cliente que não é dono do chamado recebe 403 na leitura', function () {
    $token = GeradorTokenTeste::valido([
        'iss' => $this->sistema->codigo,
        'sub' => 'outro-cliente',
    ]);

    $resposta = $this->getJson("/api/chamados/{$this->chamado->id}/mensagens", [
        'Authorization' => "Bearer {$token}",
    ]);

    $resposta->assertForbidden();
});

test('atendente que não é o responsável pelo chamado recebe 403 na leitura', function () {
    sistemaContext()->set($this->sistema->codigo);
    $outroAtendente = Atendente::factory()->create([
        'sistema_id' => $this->sistema->codigo,
        'email' => 'outro@chatservice.test',
        'senha' => Hash::make('password'),
    ]);

    $token = tokenAtendente($outroAtendente);

    $resposta = $this->getJson("/api/chamados/{$this->chamado->id}/mensagens", [
        'Authorization' => "Bearer {$token}",
    ]);

    $resposta->assertForbidden();
});

test('requisição sem autenticação recebe 403 na leitura', function () {
    $resposta = $this->getJson("/api/chamados/{$this->chamado->id}/mensagens");

    $resposta->assertForbidden();
});

test('histórico é paginado por cursor ordenado por created_at e id', function () {
    sistemaContext()->set($this->sistema->codigo);
    Mensagem::factory()->count(50)->create([
        'sistema_id' => $this->sistema->codigo,
        'chamado_id' => $this->chamado->id,
    ]);

    $token = GeradorTokenTeste::valido(['iss' => $this->sistema->codigo]);

    $resposta = $this->getJson("/api/chamados/{$this->chamado->id}/mensagens", [
        'Authorization' => "Bearer {$token}",
    ]);

    $resposta->assertOk();
    $resposta->assertJsonCount(50, 'data');
    expect($resposta->json('next_cursor'))->not->toBeNull();
    expect($resposta->json('path'))->toContain("/chamados/{$this->chamado->id}/mensagens");
});
