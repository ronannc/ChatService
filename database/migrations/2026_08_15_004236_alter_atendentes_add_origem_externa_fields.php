<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CHAT-005B: atendente externo é provisionado just-in-time a partir de
     * um JWT com `role=atendente` (mesmo JWKS do cliente final), sem
     * cadastro prévio de e-mail/senha — daí `email`/`senha` passarem a
     * nullable e a chegada de `sub_externo`/`origem` para diferenciar o
     * atendente interno (Sanctum, CHAT-005A) do externo (JWT, CHAT-005B).
     *
     * A identidade do atendente externo é `sub_externo` sozinho — decisão
     * do usuário (não `(sistema_id, sub_externo)`, que era o desenho
     * original): a API core externa usa o mesmo `sub` para a mesma pessoa
     * em qualquer sistema integrado, então o mesmo atendente humano
     * autenticando via dois sistemas diferentes precisa continuar sendo o
     * MESMO registro `Atendente`, não dois. `sistema_id` na tabela
     * `atendentes` vira só o sistema de origem do primeiro provisionamento
     * (a "home"), sem participar da identidade — quem acumula os múltiplos
     * sistemas do mesmo atendente é `atendente_sistema`
     * (`ProvisionarAtendenteExternoService`).
     *
     * O unique é parcial (`WHERE sub_externo IS NOT NULL`) porque
     * `sub_externo` é nulo para todo atendente interno.
     *
     * Risco aceito documentado em .ai/rules/atendente-externo.md: como a
     * correlação não é mais escopada por sistema, um sistema integrado
     * comprometido que emita um `sub` colidente com o de um atendente já
     * vinculado a OUTRO sistema ganha vínculo (via `atendente_sistema`) com
     * a identidade existente daquele atendente.
     */
    public function up(): void
    {
        Schema::table('atendentes', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('senha')->nullable()->change();
            $table->string('sub_externo')->nullable()->after('senha');
            $table->string('origem')->default('interno')->after('sub_externo');
        });

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX atendentes_sub_externo_unique
                ON atendentes (sub_externo)
                WHERE sub_externo IS NOT NULL
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS atendentes_sub_externo_unique');

        Schema::table('atendentes', function (Blueprint $table) {
            $table->dropColumn(['sub_externo', 'origem']);
            $table->string('email')->nullable(false)->change();
            $table->string('senha')->nullable(false)->change();
        });
    }
};
