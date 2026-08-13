<?php

use App\Models\Chamado;
use App\Models\Sistema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * Testa a policy `chamados_sistemas_permitidos_atendente` (CHAT-006) direto
 * via SQL bruto, no mesmo espírito de tests/Feature/Sistema/
 * IsolamentoSistemaTest.php — sem passar por Eloquent, pra garantir que
 * quem isola de verdade é o Postgres, não o global scope.
 */
uses(RefreshDatabase::class);

test('a policy de sistemas permitidos do atendente só libera leitura cross-sistema quando o GUC lista o sistema', function () {
    $sistemaA = Sistema::factory()->create();
    $sistemaB = Sistema::factory()->create();
    $sistemaC = Sistema::factory()->create();

    sistemaContext()->set($sistemaA->codigo);
    $chamadoA = Chamado::factory()->create(['sistema_id' => $sistemaA->codigo]);

    sistemaContext()->set($sistemaB->codigo);
    $chamadoB = Chamado::factory()->create(['sistema_id' => $sistemaB->codigo]);

    sistemaContext()->set($sistemaC->codigo);
    Chamado::factory()->create(['sistema_id' => $sistemaC->codigo]);

    sistemaContext()->clear();
    sistemaContext()->definirSistemasPermitidosAtendente([$sistemaA->codigo, $sistemaB->codigo]);

    $visiveis = DB::table('chamados')->pluck('id')->all();

    expect($visiveis)->toEqualCanonicalizing([$chamadoA->id, $chamadoB->id]);
});

test('sem o GUC de sistemas permitidos setado, a policy adicional não libera nada', function () {
    $sistema = Sistema::factory()->create();

    sistemaContext()->set($sistema->codigo);
    Chamado::factory()->create(['sistema_id' => $sistema->codigo]);

    sistemaContext()->clear();

    expect(DB::table('chamados')->count())->toBe(0);
});

test('limparSistemasPermitidosAtendente desliga a policy adicional', function () {
    $sistemaA = Sistema::factory()->create();
    $sistemaB = Sistema::factory()->create();

    sistemaContext()->set($sistemaA->codigo);
    Chamado::factory()->create(['sistema_id' => $sistemaA->codigo]);

    sistemaContext()->set($sistemaB->codigo);
    Chamado::factory()->create(['sistema_id' => $sistemaB->codigo]);

    sistemaContext()->clear();
    sistemaContext()->definirSistemasPermitidosAtendente([$sistemaA->codigo, $sistemaB->codigo]);
    expect(DB::table('chamados')->count())->toBe(2);

    sistemaContext()->limparSistemasPermitidosAtendente();
    expect(DB::table('chamados')->count())->toBe(0);
});
