<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tabelas isoladas por sistema_id, protegidas por Row Level Security.
     *
     * atendente_sistema é DELIBERADAMENTE excluída: é a própria tabela de
     * permissão multi-sistema de um atendente, então uma policy baseada em
     * "sistema_id = sistema atual da sessão" quebraria sua finalidade
     * (listar a quais outros sistemas o atendente tem acesso). Isolá-la de
     * verdade exige uma policy baseada no atendente autenticado, não no
     * sistema atual — isso depende de identidade de atendente (CHAT-005A),
     * que ainda não existe.
     *
     * @var array<int, string>
     */
    private array $tabelas = ['chamados', 'mensagens', 'atendentes'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tabelas as $tabela) {
            // FORCE é essencial: sem ele, a policy não vale para o usuário
            // dono das tabelas (o mesmo usuário que a aplicação usa aqui).
            DB::statement("ALTER TABLE {$tabela} ENABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$tabela} FORCE ROW LEVEL SECURITY");

            DB::statement(<<<SQL
                CREATE POLICY {$tabela}_isolamento_sistema ON {$tabela}
                    USING (sistema_id = current_setting('app.current_sistema_id', true))
                SQL);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tabelas as $tabela) {
            DB::statement("DROP POLICY IF EXISTS {$tabela}_isolamento_sistema ON {$tabela}");
            DB::statement("ALTER TABLE {$tabela} NO FORCE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$tabela} DISABLE ROW LEVEL SECURITY");
        }
    }
};
