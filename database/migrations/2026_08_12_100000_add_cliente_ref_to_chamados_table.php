<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `cliente_ref` guarda o `sub` do token do cliente (docs/contratos/
     * token-cliente.md) — a identidade do usuário final dentro do sistema de
     * origem. Junto com `sistema_id`, forma o par `(sistema_id, cliente_ref)`
     * que a autorização do canal privado usa (CHAT-006) e que CHAT-008
     * reaproveita para popular a coluna na criação do chamado.
     *
     * Nullable porque chamados existentes (e qualquer fluxo futuro sem
     * cliente final associado) não têm essa referência.
     */
    public function up(): void
    {
        Schema::table('chamados', function (Blueprint $table) {
            $table->string('cliente_ref')->nullable()->after('sistema_id');
            $table->index(['sistema_id', 'cliente_ref']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chamados', function (Blueprint $table) {
            $table->dropIndex(['sistema_id', 'cliente_ref']);
            $table->dropColumn('cliente_ref');
        });
    }
};
