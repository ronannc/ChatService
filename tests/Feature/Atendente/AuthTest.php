<?php

use App\Enums\StatusAtendente;
use App\Models\Atendente;
use App\Models\AtendenteSistema;
use App\Models\Sistema;
use App\Support\SistemaContext;
use Database\Seeders\AtendenteSeeder;
use Database\Seeders\SistemaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);

// O cache aqui é Redis real (não in-memory), então o throttle:10,1 do login
// e o rate limiting do guard persistem entre testes — sem isso, os testes
// deste arquivo se acumulam e passam a bater 429 em vez do status esperado.
beforeEach(fn () => Cache::flush());

test('login com credenciais válidas retorna token', function () {
    $atendente = criarAtendente(['email' => 'ana@chatservice.test']);

    $response = $this->postJson('/api/atendentes/login', [
        'email' => 'ana@chatservice.test',
        'senha' => 'password',
    ]);

    $response->assertOk();
    $response->assertJsonStructure(['atendente' => ['id', 'email'], 'token']);
    $response->assertJsonMissingPath('atendente.senha');
});

test('login com senha errada é rejeitado', function () {
    criarAtendente(['email' => 'ana@chatservice.test']);

    $response = $this->postJson('/api/atendentes/login', [
        'email' => 'ana@chatservice.test',
        'senha' => 'senha-errada',
    ]);

    $response->assertUnauthorized();
});

test('login de email inexistente é rejeitado', function () {
    $response = $this->postJson('/api/atendentes/login', [
        'email' => 'nao-existe@chatservice.test',
        'senha' => 'password',
    ]);

    $response->assertUnauthorized();
});

test('login de atendente inativo é rejeitado', function () {
    criarAtendente(['email' => 'ana@chatservice.test', 'status' => StatusAtendente::Inativo]);

    $response = $this->postJson('/api/atendentes/login', [
        'email' => 'ana@chatservice.test',
        'senha' => 'password',
    ]);

    $response->assertUnauthorized();
});

test('rota protegida sem token é rejeitada', function () {
    $response = $this->getJson('/api/atendentes/me');

    $response->assertUnauthorized();
});

test('rota protegida com token válido resolve o atendente e seus sistemas', function () {
    $atendente = criarAtendente(['email' => 'ana@chatservice.test']);
    $outroSistema = Sistema::factory()->create();

    AtendenteSistema::factory()->create([
        'atendente_id' => $atendente->id,
        'sistema_id' => $outroSistema->codigo,
    ]);

    $login = $this->postJson('/api/atendentes/login', [
        'email' => 'ana@chatservice.test',
        'senha' => 'password',
    ])->json();

    $response = $this->withHeader('Authorization', "Bearer {$login['token']}")
        ->getJson('/api/atendentes/me');

    $response->assertOk();
    $response->assertJsonPath('atendente.id', $atendente->id);
    $response->assertJsonPath('atendente.email', 'ana@chatservice.test');
    $response->assertJsonMissingPath('atendente.senha');

    $sistemas = $response->json('sistemas_permitidos');
    expect($sistemas)->toContain($atendente->sistema_id, $outroSistema->codigo);
});

test('o bypass de resolução do atendente é desligado depois de autenticar com sucesso', function () {
    $atendente = criarAtendente(['email' => 'ana@chatservice.test']);

    $token = $this->postJson('/api/atendentes/login', [
        'email' => 'ana@chatservice.test',
        'senha' => 'password',
    ])->json('token');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/atendentes/me')
        ->assertOk();

    expect(app(SistemaContext::class)->bypassAtivo())->toBeFalse();
});

test('o bypass de resolução do atendente é desligado mesmo quando a autenticação falha', function () {
    $this->getJson('/api/atendentes/me')->assertUnauthorized();

    expect(app(SistemaContext::class)->bypassAtivo())->toBeFalse();
});

test('token expirado é rejeitado', function () {
    $atendente = criarAtendente(['email' => 'ana@chatservice.test']);

    $token = $this->postJson('/api/atendentes/login', [
        'email' => 'ana@chatservice.test',
        'senha' => 'password',
    ])->json('token');

    PersonalAccessToken::query()->first()->update(['expires_at' => now()->subMinute()]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/atendentes/me')
        ->assertUnauthorized();
});

test('token válido cujo atendente foi apagado é rejeitado, não gera 500', function () {
    $atendente = criarAtendente(['email' => 'ana@chatservice.test']);

    $token = $this->postJson('/api/atendentes/login', [
        'email' => 'ana@chatservice.test',
        'senha' => 'password',
    ])->json('token');

    app(SistemaContext::class)->ativarBypassParaResolucaoDeAtendente();
    Atendente::withoutGlobalScopes()->find($atendente->id)->delete();
    app(SistemaContext::class)->desativarBypassParaResolucaoDeAtendente();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/atendentes/me')
        ->assertUnauthorized();
});

test('dois atendentes autenticados não se confundem', function () {
    $atendenteA = criarAtendente(['email' => 'a@chatservice.test']);
    $atendenteB = criarAtendente(['email' => 'b@chatservice.test']);

    $tokenA = $this->postJson('/api/atendentes/login', [
        'email' => 'a@chatservice.test',
        'senha' => 'password',
    ])->json('token');

    $tokenB = $this->postJson('/api/atendentes/login', [
        'email' => 'b@chatservice.test',
        'senha' => 'password',
    ])->json('token');

    $this->withHeader('Authorization', "Bearer {$tokenA}")
        ->getJson('/api/atendentes/me')
        ->assertJsonPath('atendente.id', $atendenteA->id);

    // O guard 'sanctum' fica cacheado no AuthManager pra a duração do
    // teste — sem isso, a segunda request "autenticada" reaproveitaria o
    // usuário já resolvido na primeira, mesmo com um Bearer diferente.
    $this->app['auth']->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$tokenB}")
        ->getJson('/api/atendentes/me')
        ->assertJsonPath('atendente.id', $atendenteB->id);
});

test('seeder cria atendente vinculado a gestão de oficinas e a um sistema fictício', function () {
    $this->seed(SistemaSeeder::class);
    $this->seed(AtendenteSeeder::class);

    $atendente = Atendente::withoutGlobalScopes()->where('email', 'atendente@chatservice.test')->first();

    expect($atendente)->not->toBeNull();

    $sistemas = AtendenteSistema::query()->where('atendente_id', $atendente->id)->pluck('sistema_id');
    expect($sistemas)->toContain('gestao-oficinas', 'sistema-ficticio');
});
