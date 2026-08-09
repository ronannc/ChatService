<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Duas operações precisam achar a linha do atendente pelo e-mail/token
     * antes de qualquer sistema_id existir no contexto — é essa própria
     * resolução que estabelece o contexto, não o contrário:
     *   - o login por e-mail (App\Services\Atendente\LoginAtendenteService);
     *   - a resolução do token Sanctum em toda request autenticada
     *     (App\Http\Middleware\EnableAtendenteAuthRlsBypass/
     *     ResolveAtendenteContext, que cercam o middleware auth:sanctum).
     * A policy original exigia o contexto pra qualquer leitura, o que
     * tornava as duas operações impossíveis.
     *
     * A flag de sessão abaixo só é ligada pela própria aplicação (nunca a
     * partir de input do cliente) e só durante essas duas janelas — ver
     * App\Support\SistemaContext::GUC_BYPASS_RESOLUCAO_ATENDENTE.
     */
    public function up(): void
    {
        DB::statement('DROP POLICY IF EXISTS atendentes_isolamento_sistema ON atendentes');

        DB::statement(<<<'SQL'
            CREATE POLICY atendentes_isolamento_sistema ON atendentes
                USING (
                    sistema_id = current_setting('app.current_sistema_id', true)
                    OR current_setting('app.bypass_rls_resolucao_atendente', true) = 'true'
                )
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP POLICY IF EXISTS atendentes_isolamento_sistema ON atendentes');

        DB::statement(<<<'SQL'
            CREATE POLICY atendentes_isolamento_sistema ON atendentes
                USING (sistema_id = current_setting('app.current_sistema_id', true))
            SQL);
    }
};
