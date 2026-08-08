<?php

use App\Enums\StatusSistema;
use App\Models\Sistema;
use Database\Seeders\SistemaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('cria um sistema com api key válida', function () {
    $response = $this->postJson('/api/admin/sistemas', [
        'codigo' => 'gestao-oficinas',
        'nome' => 'Gestão de Oficinas',
        'jwks_url' => 'https://gestaodeoficinas.example.com/.well-known/jwks.json',
    ], adminHeaders());

    $response->assertCreated();

    $this->assertDatabaseHas('sistemas', [
        'codigo' => 'gestao-oficinas',
        'status' => StatusSistema::Ativo->value,
    ]);
});

test('rejeita codigo duplicado', function () {
    Sistema::factory()->create(['codigo' => 'gestao-oficinas']);

    $response = $this->postJson('/api/admin/sistemas', [
        'codigo' => 'gestao-oficinas',
        'nome' => 'Outro Nome',
        'jwks_url' => 'https://outro.example.com/.well-known/jwks.json',
    ], adminHeaders());

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('codigo');
});

test('rejeita criação sem nome, sem jwks_url ou com jwks_url inválido', function (array $overrides, string $campoComErro) {
    $payload = array_merge([
        'codigo' => 'gestao-oficinas',
        'nome' => 'Gestão de Oficinas',
        'jwks_url' => 'https://gestaodeoficinas.example.com/.well-known/jwks.json',
    ], $overrides);

    $response = $this->postJson('/api/admin/sistemas', $payload, adminHeaders());

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors($campoComErro);
})->with([
    'sem nome' => [['nome' => null], 'nome'],
    'sem jwks_url' => [['jwks_url' => null], 'jwks_url'],
    'jwks_url não é url' => [['jwks_url' => 'não-é-url'], 'jwks_url'],
    'jwks_url não https' => [['jwks_url' => 'http://gestaodeoficinas.example.com/jwks.json'], 'jwks_url'],
]);

test('rejeita requisição sem api key', function () {
    $response = $this->postJson('/api/admin/sistemas', [
        'codigo' => 'gestao-oficinas',
        'nome' => 'Gestão de Oficinas',
        'jwks_url' => 'https://gestaodeoficinas.example.com/.well-known/jwks.json',
    ]);

    $response->assertUnauthorized();
});

test('rejeita requisição com api key errada', function () {
    $response = $this->postJson('/api/admin/sistemas', [
        'codigo' => 'gestao-oficinas',
        'nome' => 'Gestão de Oficinas',
        'jwks_url' => 'https://gestaodeoficinas.example.com/.well-known/jwks.json',
    ], ['X-Admin-Api-Key' => 'chave-errada']);

    $response->assertUnauthorized();
});

test('atualiza o status de um sistema para inativo mantendo os demais dados intactos', function () {
    $sistema = Sistema::factory()->create(['status' => StatusSistema::Ativo]);

    $response = $this->patchJson("/api/admin/sistemas/{$sistema->codigo}", [
        'status' => StatusSistema::Inativo->value,
    ], adminHeaders());

    $response->assertOk();

    $this->assertDatabaseHas('sistemas', [
        'id' => $sistema->id,
        'codigo' => $sistema->codigo,
        'nome' => $sistema->nome,
        'jwks_url' => $sistema->jwks_url,
        'status' => StatusSistema::Inativo->value,
    ]);
});

test('ignora codigo enviado no payload de atualização', function () {
    $sistema = Sistema::factory()->create();

    $this->patchJson("/api/admin/sistemas/{$sistema->codigo}", [
        'codigo' => 'outro-codigo',
        'status' => StatusSistema::Inativo->value,
    ], adminHeaders())->assertOk();

    $this->assertDatabaseHas('sistemas', [
        'id' => $sistema->id,
        'codigo' => $sistema->codigo,
    ]);
});

test('rejeita status inválido na atualização', function () {
    $sistema = Sistema::factory()->create();

    $response = $this->patchJson("/api/admin/sistemas/{$sistema->codigo}", [
        'status' => 'banido',
    ], adminHeaders());

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('status');
});

test('retorna 404 ao atualizar codigo inexistente', function () {
    $response = $this->patchJson('/api/admin/sistemas/nao-existe', [
        'status' => StatusSistema::Inativo->value,
    ], adminHeaders());

    $response->assertNotFound();
});

test('rejeita atualização sem api key ou com api key errada', function () {
    $sistema = Sistema::factory()->create();

    $this->patchJson("/api/admin/sistemas/{$sistema->codigo}", ['status' => StatusSistema::Inativo->value])
        ->assertUnauthorized();

    $this->patchJson(
        "/api/admin/sistemas/{$sistema->codigo}",
        ['status' => StatusSistema::Inativo->value],
        ['X-Admin-Api-Key' => 'chave-errada'],
    )->assertUnauthorized();
});

test('seeder cadastra o sistema gestão de oficinas ativo', function () {
    $this->seed(SistemaSeeder::class);

    $this->assertDatabaseHas('sistemas', [
        'codigo' => 'gestao-oficinas',
        'status' => StatusSistema::Ativo->value,
    ]);
});
