<?php

use App\Enums\ClaimTokenCliente;
use App\Enums\OrigemAtendente;
use App\Enums\StatusSistema;
use App\Models\Atendente;
use App\Models\AtendenteSistema;
use App\Models\Sistema;
use App\Services\Auth\BuscarJwksSegurancaService;
use App\Services\Auth\RepositorioJwks;
use App\Services\Auth\ValidarTokenClienteService;
use App\Support\GuardaHostSeguro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Support\GeradorTokenTeste;

/**
 * Exercita `GET /api/atendente-externo/me` ponta a ponta: `cliente.token`
 * (`EnsureValidTokenCliente`) + `atendente.externo.context`
 * (`ResolveAtendenteExternoContext` + `ProvisionarAtendenteExternoService`),
 * CHAT-005B. Precisa de `RefreshDatabase` (não do esquema mínimo de
 * `prepararTabelaSistemasParaTeste()`) porque toca `atendentes`/
 * `atendente_sistema`, que só migram com DDL exclusivo do Postgres (RLS) —
 * roda contra Postgres real via `make test`/Docker.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();

    $this->sistema = Sistema::factory()->create([
        'codigo' => GeradorTokenTeste::SISTEMA_CODIGO,
        'jwks_url' => 'https://gestaodeoficinas.example.com/.well-known/jwks.json',
        'status' => StatusSistema::Ativo,
    ]);

    Http::fake([
        $this->sistema->jwks_url => Http::response(GeradorTokenTeste::jwks(), 200),
    ]);

    // Mesmo truque de tests/Feature/Auth/EnsureValidTokenClienteTest.php:
    // substitui o resolvedor de host real por um falso, pra não depender de
    // DNS/rede de verdade no teste.
    $this->app->bind(ValidarTokenClienteService::class, fn () => new ValidarTokenClienteService(
        new RepositorioJwks(
            new BuscarJwksSegurancaService(new GuardaHostSeguro(fn (string $host): array => ['8.8.8.8'])),
        ),
    ));
});

test('primeiro login com role=atendente provisiona atendente externo e vínculo com o sistema', function () {
    $resposta = $this->getJson('/api/atendente-externo/me', [
        'Authorization' => 'Bearer '.GeradorTokenTeste::papelAtendente(),
    ]);

    $resposta->assertOk();

    $atendente = Atendente::withoutGlobalScopes()->where('sub_externo', GeradorTokenTeste::SUB)->first();

    expect($atendente)->not->toBeNull();
    expect($atendente->origem)->toBe(OrigemAtendente::Externo);
    expect($atendente->email)->toBeNull();
    expect($atendente->senha)->toBeNull();
    expect($atendente->sistema_id)->toBe($this->sistema->codigo);

    $resposta->assertJsonPath('atendente.id', $atendente->id);

    expect(AtendenteSistema::query()
        ->where('atendente_id', $atendente->id)
        ->where('sistema_id', $this->sistema->codigo)
        ->count())->toBe(1);
});

test('segundo login do mesmo iss/sub não duplica atendente nem vínculo', function () {
    $token = GeradorTokenTeste::papelAtendente();

    $this->getJson('/api/atendente-externo/me', ['Authorization' => "Bearer {$token}"])->assertOk();
    $this->getJson('/api/atendente-externo/me', ['Authorization' => "Bearer {$token}"])->assertOk();

    expect(Atendente::withoutGlobalScopes()->where('sub_externo', GeradorTokenTeste::SUB)->count())->toBe(1);
    expect(AtendenteSistema::query()->count())->toBe(1);
});

test('token com role=cliente é rejeitado com 403 e não provisiona atendente', function () {
    $resposta = $this->getJson('/api/atendente-externo/me', [
        'Authorization' => 'Bearer '.GeradorTokenTeste::valido(),
    ]);

    $resposta->assertStatus(403);

    expect(Atendente::withoutGlobalScopes()->count())->toBe(0);
});

test('token sem a claim role (fallback cliente) também é rejeitado com 403', function () {
    $resposta = $this->getJson('/api/atendente-externo/me', [
        'Authorization' => 'Bearer '.GeradorTokenTeste::semClaim(ClaimTokenCliente::Role),
    ]);

    $resposta->assertStatus(403);

    expect(Atendente::withoutGlobalScopes()->count())->toBe(0);
});

test('role fora do vocabulário do contrato é rejeitada na validação do token, antes de qualquer contexto', function () {
    $resposta = $this->getJson('/api/atendente-externo/me', [
        'Authorization' => 'Bearer '.GeradorTokenTeste::roleNaoReconhecida(),
    ]);

    $resposta->assertStatus(401)->assertJson(['message' => 'Token inválido.']);

    expect(Atendente::withoutGlobalScopes()->count())->toBe(0);
});

test('atendente interno (Sanctum) e atendente externo (JWT) nunca se confundem', function () {
    $interno = criarAtendente(['email' => 'interna@chatservice.test']);

    $loginInterno = $this->postJson('/api/atendentes/login', [
        'email' => 'interna@chatservice.test',
        'senha' => 'password',
    ])->json('token');

    $this->withHeader('Authorization', "Bearer {$loginInterno}")
        ->getJson('/api/atendentes/me')
        ->assertOk()
        ->assertJsonPath('atendente.id', $interno->id);

    // O guard 'sanctum' fica cacheado no AuthManager pra a duração do teste
    // (mesmo cuidado de tests/Feature/Atendente/AuthTest.php).
    $this->app['auth']->forgetGuards();

    $respostaExterna = $this->getJson('/api/atendente-externo/me', [
        'Authorization' => 'Bearer '.GeradorTokenTeste::papelAtendente(),
    ]);
    $respostaExterna->assertOk();

    $externo = Atendente::withoutGlobalScopes()->where('sub_externo', GeradorTokenTeste::SUB)->first();

    expect($externo)->not->toBeNull();
    expect($externo->id)->not->toBe($interno->id);
    expect($respostaExterna->json('atendente.id'))->toBe($externo->id);

    // O access token Sanctum do interno não é um JWT: cliente.token deve
    // rejeitá-lo estruturalmente, nunca provisionar nada a partir dele.
    $this->getJson('/api/atendente-externo/me', [
        'Authorization' => "Bearer {$loginInterno}",
    ])->assertStatus(401);

    // O JWT do externo não é um personal access token Sanctum válido: a
    // rota interna deve continuar exigindo autenticação.
    $this->getJson('/api/atendentes/me', [
        'Authorization' => 'Bearer '.GeradorTokenTeste::papelAtendente(),
    ])->assertUnauthorized();
});

test('mesmo sub_externo em outro sistema (iss) correlaciona para o MESMO atendente, acumulando vínculos em atendente_sistema', function () {
    // Redesenho aprovado pelo usuário (.ai/rules/atendente-externo.md): a
    // identidade do atendente externo é `sub_externo` sozinho, sem escopo
    // por `sistema_id` — diferente do cliente final. `sistema_id` em
    // `atendentes` é só a "home" do primeiro provisionamento; quem acumula
    // os demais sistemas é `atendente_sistema`.
    $sistemaB = Sistema::factory()->create([
        'codigo' => 'sistema-ficticio',
        'jwks_url' => 'https://sistema-ficticio.example.com/.well-known/jwks.json',
        'status' => StatusSistema::Ativo,
    ]);

    Http::fake([
        $this->sistema->jwks_url => Http::response(GeradorTokenTeste::jwks(), 200),
        $sistemaB->jwks_url => Http::response(GeradorTokenTeste::jwks(), 200),
    ]);

    $respostaA = $this->getJson('/api/atendente-externo/me', [
        'Authorization' => 'Bearer '.GeradorTokenTeste::papelAtendente(),
    ]);
    $respostaA->assertOk();

    $tokenSistemaB = GeradorTokenTeste::papelAtendente([
        ClaimTokenCliente::Iss->value => $sistemaB->codigo,
    ]);

    $respostaB = $this->getJson('/api/atendente-externo/me', [
        'Authorization' => 'Bearer '.$tokenSistemaB,
    ]);
    $respostaB->assertOk();

    // Mesmo atendente nas duas respostas — não duas identidades.
    expect($respostaB->json('atendente.id'))->toBe($respostaA->json('atendente.id'));

    $atendentesComEsseSub = Atendente::withoutGlobalScopes()
        ->where('sub_externo', GeradorTokenTeste::SUB)
        ->get();

    expect($atendentesComEsseSub)->toHaveCount(1);

    $atendente = $atendentesComEsseSub->first();

    // `sistema_id` continua sendo a home do primeiro provisionamento
    // (sistema A), não migra pro sistema do login mais recente.
    expect($atendente->sistema_id)->toBe($this->sistema->codigo);

    $sistemasVinculados = AtendenteSistema::query()
        ->where('atendente_id', $atendente->id)
        ->pluck('sistema_id');

    expect($sistemasVinculados)->toHaveCount(2)
        ->and($sistemasVinculados)->toContain($this->sistema->codigo, $sistemaB->codigo);
});

test('risco aceito: um segundo sistema emitindo o mesmo sub ganha vínculo automático com a identidade existente, sem verificação extra', function () {
    // Não é isolamento quebrado, é o modelo de confiança aprovado pelo
    // usuário (ver "Risco aceito" em .ai/rules/atendente-externo.md): a
    // correlação por `sub_externo` não é escopada por sistema, então um
    // segundo `iss` emitindo o mesmo `sub` de um atendente já vinculado a
    // outro sistema herda automaticamente esse vínculo — sem nenhuma
    // aprovação manual ou verificação adicional de unicidade entre
    // emissores. Este teste existe para que ninguém "corrija" essa
    // consequência silenciosamente sem consultar o usuário de novo.
    $sistemaB = Sistema::factory()->create([
        'codigo' => 'sistema-ficticio',
        'jwks_url' => 'https://sistema-ficticio.example.com/.well-known/jwks.json',
        'status' => StatusSistema::Ativo,
    ]);

    Http::fake([
        $this->sistema->jwks_url => Http::response(GeradorTokenTeste::jwks(), 200),
        $sistemaB->jwks_url => Http::response(GeradorTokenTeste::jwks(), 200),
    ]);

    // Atendente externo já estabelecido só no sistema A.
    $this->getJson('/api/atendente-externo/me', [
        'Authorization' => 'Bearer '.GeradorTokenTeste::papelAtendente(),
    ])->assertOk();

    $atendente = Atendente::withoutGlobalScopes()->where('sub_externo', GeradorTokenTeste::SUB)->first();

    expect(AtendenteSistema::query()->where('atendente_id', $atendente->id)->count())->toBe(1);

    // Sistema B (poderia ser um sistema comprometido) emite o mesmo `sub`.
    $tokenSistemaB = GeradorTokenTeste::papelAtendente([
        ClaimTokenCliente::Iss->value => $sistemaB->codigo,
    ]);

    $resposta = $this->getJson('/api/atendente-externo/me', [
        'Authorization' => 'Bearer '.$tokenSistemaB,
    ]);
    $resposta->assertOk();

    // O vínculo com o sistema B foi concedido automaticamente — sem flag,
    // sem aprovação, só pela apresentação de um JWT válido com o mesmo sub.
    expect($resposta->json('atendente.id'))->toBe($atendente->id);
    expect($resposta->json('sistemas_permitidos'))
        ->toContain($this->sistema->codigo, $sistemaB->codigo);

    expect(AtendenteSistema::query()->where('atendente_id', $atendente->id)->count())->toBe(2);
});
