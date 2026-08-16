<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Diferente do MySQL, o Postgres não cria índice automático para uma FK
     * (`mensagens_chamado_id_foreign`) — sem este índice,
     * `ListarMensagensService::handle()` (`where(chamado_id)->orderBy
     * (created_at, id)->cursorPaginate()`) faz seq scan conforme a tabela
     * cresce. A ordem das colunas casa com a cláusula WHERE + ORDER BY
     * exata usada pela paginação por cursor.
     */
    public function up(): void
    {
        Schema::table('mensagens', function (Blueprint $table) {
            $table->index(['chamado_id', 'created_at', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mensagens', function (Blueprint $table) {
            $table->dropIndex(['chamado_id', 'created_at', 'id']);
        });
    }
};
