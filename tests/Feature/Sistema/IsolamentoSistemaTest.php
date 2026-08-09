<?php

use App\Enums\EncerradoPor;
use App\Enums\StatusChamado;
use App\Models\Atendente;
use App\Models\AtendenteSistema;
use App\Models\Chamado;
use App\Models\Mensagem;
use App\Models\Sistema;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('eloquent só retorna linhas do sistema definido no contexto', function () {
    $sistemaA = Sistema::factory()->create();
    $sistemaB = Sistema::factory()->create();

    sistemaContext()->set($sistemaA->codigo);
    Chamado::factory()->create(['sistema_id' => $sistemaA->codigo]);

    sistemaContext()->set($sistemaB->codigo);
    Chamado::factory()->create(['sistema_id' => $sistemaB->codigo]);

    sistemaContext()->set($sistemaA->codigo);
    expect(Chamado::query()->count())->toBe(1);

    sistemaContext()->set($sistemaB->codigo);
    expect(Chamado::query()->count())->toBe(1);
});

test('eloquent não retorna nenhuma linha sem contexto de sistema definido', function () {
    $sistema = Sistema::factory()->create();

    sistemaContext()->set($sistema->codigo);
    Chamado::factory()->create(['sistema_id' => $sistema->codigo]);

    sistemaContext()->clear();
    expect(Chamado::query()->count())->toBe(0);
});

test('rls bloqueia leitura via sql bruto de outro sistema', function (string $tabela, Closure $criar) {
    $sistemaA = Sistema::factory()->create();
    $sistemaB = Sistema::factory()->create();

    sistemaContext()->set($sistemaA->codigo);
    $criar($sistemaA);

    sistemaContext()->set($sistemaB->codigo);
    $criar($sistemaB);

    sistemaContext()->set($sistemaA->codigo);
    expect(DB::table($tabela)->count())->toBe(1);
})->with([
    'chamados' => ['chamados', fn (Sistema $sistema) => Chamado::factory()->create(['sistema_id' => $sistema->codigo])],
    'mensagens' => ['mensagens', fn (Sistema $sistema) => Mensagem::factory()->create(['sistema_id' => $sistema->codigo])],
    'atendentes' => ['atendentes', fn (Sistema $sistema) => Atendente::factory()->create(['sistema_id' => $sistema->codigo])],
]);

test('rls não retorna nenhuma linha via sql bruto sem contexto definido', function (string $tabela, Closure $criar) {
    $sistema = Sistema::factory()->create();

    sistemaContext()->set($sistema->codigo);
    $criar($sistema);

    sistemaContext()->clear();
    expect(DB::table($tabela)->count())->toBe(0);
})->with([
    'chamados' => ['chamados', fn (Sistema $sistema) => Chamado::factory()->create(['sistema_id' => $sistema->codigo])],
    'mensagens' => ['mensagens', fn (Sistema $sistema) => Mensagem::factory()->create(['sistema_id' => $sistema->codigo])],
    'atendentes' => ['atendentes', fn (Sistema $sistema) => Atendente::factory()->create(['sistema_id' => $sistema->codigo])],
]);

test('rls bloqueia gravar uma linha para outro sistema', function (string $tabela, array $colunasExtra) {
    $sistemaA = Sistema::factory()->create();
    $sistemaB = Sistema::factory()->create();

    sistemaContext()->set($sistemaA->codigo);

    expect(fn () => DB::table($tabela)->insert([
        'sistema_id' => $sistemaB->codigo,
        ...$colunasExtra,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
})->with([
    'chamados' => ['chamados', ['status' => StatusChamado::AguardandoFila->value]],
    'atendentes' => ['atendentes', []],
]);

test('chamado nasce com status aguardando_fila e encerrado_por nulo', function () {
    $sistema = Sistema::factory()->create();
    sistemaContext()->set($sistema->codigo);

    $chamado = Chamado::factory()->create(['sistema_id' => $sistema->codigo]);

    expect($chamado->status)->toBe(StatusChamado::AguardandoFila);
    expect($chamado->encerrado_por)->toBeNull();
});

test('chamado aceita encerrado_por quando resolvido', function () {
    $sistema = Sistema::factory()->create();
    sistemaContext()->set($sistema->codigo);

    $chamado = Chamado::factory()->create([
        'sistema_id' => $sistema->codigo,
        'status' => StatusChamado::Resolvido,
        'encerrado_por' => EncerradoPor::Cliente,
    ]);

    expect($chamado->encerrado_por)->toBe(EncerradoPor::Cliente);
});

test('sistema_id é preenchido automaticamente a partir do contexto ao criar', function () {
    $sistema = Sistema::factory()->create();
    sistemaContext()->set($sistema->codigo);

    $chamado = Chamado::factory()->make(['sistema_id' => null]);
    $chamado->save();

    expect($chamado->sistema_id)->toBe($sistema->codigo);
});

test('mensagem só é visível sob o contexto do seu próprio sistema', function () {
    $sistemaA = Sistema::factory()->create();
    $sistemaB = Sistema::factory()->create();

    sistemaContext()->set($sistemaA->codigo);
    Mensagem::factory()->create(['sistema_id' => $sistemaA->codigo]);

    sistemaContext()->set($sistemaB->codigo);
    Mensagem::factory()->create(['sistema_id' => $sistemaB->codigo]);

    sistemaContext()->set($sistemaA->codigo);
    expect(Mensagem::query()->count())->toBe(1);

    sistemaContext()->clear();
    expect(Mensagem::query()->count())->toBe(0);
});

test('atendente_sistema vincula um atendente a múltiplos sistemas sem sofrer o global scope', function () {
    $sistemaA = Sistema::factory()->create();
    $sistemaB = Sistema::factory()->create();

    sistemaContext()->set($sistemaA->codigo);
    $atendente = Atendente::factory()->create(['sistema_id' => $sistemaA->codigo]);

    AtendenteSistema::factory()->create([
        'atendente_id' => $atendente->id,
        'sistema_id' => $sistemaA->codigo,
    ]);
    AtendenteSistema::factory()->create([
        'atendente_id' => $atendente->id,
        'sistema_id' => $sistemaB->codigo,
    ]);

    sistemaContext()->set($sistemaB->codigo);

    expect(AtendenteSistema::query()->where('atendente_id', $atendente->id)->count())->toBe(2);
});

test('atendente_sistema não tem rls — decisão deliberada, ver migration de rls', function () {
    $sistemaA = Sistema::factory()->create();
    $sistemaB = Sistema::factory()->create();

    sistemaContext()->set($sistemaA->codigo);
    $atendente = Atendente::factory()->create(['sistema_id' => $sistemaA->codigo]);

    AtendenteSistema::factory()->create([
        'atendente_id' => $atendente->id,
        'sistema_id' => $sistemaB->codigo,
    ]);

    expect(DB::table('atendente_sistema')->count())->toBe(1);
});
