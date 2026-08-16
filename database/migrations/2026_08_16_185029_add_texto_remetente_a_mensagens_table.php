<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `remetente_tipo` + `remetente_ref` (string genérica, guarda
     * `cliente_ref` ou o id do atendente conforme o tipo) em vez de duas FKs
     * nullable — mesmo espírito de `cliente_ref` em `chamados` (CHAT-009).
     */
    public function up(): void
    {
        Schema::table('mensagens', function (Blueprint $table) {
            $table->text('texto')->after('chamado_id');
            $table->string('remetente_tipo')->after('texto');
            $table->string('remetente_ref')->after('remetente_tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mensagens', function (Blueprint $table) {
            $table->dropColumn(['texto', 'remetente_tipo', 'remetente_ref']);
        });
    }
};
