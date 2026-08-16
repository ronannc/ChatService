<?php

use App\Models\Atendente;
use App\Models\Chamado;
use App\Models\Sistema;
use App\Support\SistemaContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function adminHeaders(): array
{
    return ['X-Admin-Api-Key' => config('chat.admin_api_key')];
}

function sistemaContext(): SistemaContext
{
    return app(SistemaContext::class);
}

function criarAtendente(array $overrides = []): Atendente
{
    $sistema = Sistema::factory()->create();
    sistemaContext()->set($sistema->codigo);

    return Atendente::factory()->create(array_merge([
        'sistema_id' => $sistema->codigo,
        'senha' => Hash::make('password'),
    ], $overrides));
}

/**
 * Loga um atendente já criado com a senha padrão de `criarAtendente()`
 * (CHAT-009, rotas de mensagens) e devolve só o token Sanctum.
 */
function tokenAtendente(Atendente $atendente, string $senha = 'password'): string
{
    return test()->postJson('/api/atendentes/login', [
        'email' => $atendente->email,
        'senha' => $senha,
    ])->json('token');
}

/**
 * POST em /api/broadcasting/auth (CHAT-006) no formato que o cliente Echo
 * envia (`channel_name`/`socket_id`), com Bearer opcional. `$bearer` é `null`
 * para exercitar o caso sem nenhuma autenticação.
 */
function autorizarCanalDoChamado(Chamado $chamado, ?string $bearer): TestResponse
{
    return test()->postJson('/api/broadcasting/auth', [
        'channel_name' => "private-chamado.{$chamado->id}",
        'socket_id' => '1234.5678',
    ], $bearer ? ['Authorization' => "Bearer {$bearer}"] : []);
}

/**
 * Cria só a tabela `sistemas` (sem o restante das migrations, que dependem
 * de DDL exclusivo do Postgres — RLS via `ENABLE ROW LEVEL SECURITY` e
 * `ALTER TABLE ... ADD CONSTRAINT ... CHECK`, ambos incompatíveis com o
 * sqlite `:memory:` usado fora do container Docker).
 *
 * Os testes de CHAT-005 (validação do token do cliente) só precisam de
 * `sistemas` — nenhum deles toca `chamados`/`mensagens`/`atendentes`, que são
 * as tabelas isoladas por RLS — então rodam contra esse esquema mínimo em
 * vez de exigir `RefreshDatabase` (que roda todas as migrations e falha
 * fora do Docker/Postgres). `make test` continua sendo a forma de rodar a
 * suíte inteira contra Postgres real.
 */
function prepararTabelaSistemasParaTeste(): void
{
    if (! Schema::hasTable('sistemas')) {
        Schema::create('sistemas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nome');
            $table->string('jwks_url');
            $table->string('status')->default('ativo');
            $table->timestamps();
        });
    }

    Sistema::query()->delete();
}
