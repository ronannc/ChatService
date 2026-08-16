<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `atendente_atual_id` guarda apenas quem está atualmente responsável
     * pelo chamado — o fluxo completo de "assumir" um chamado (fila,
     * transferência, liberação) é CHAT-011. Aqui só o campo mínimo para
     * CHAT-009 decidir se um atendente pode enviar mensagem num chamado.
     *
     * Nullable: todo chamado nasce em `aguardando_fila` sem atendente.
     */
    public function up(): void
    {
        Schema::table('chamados', function (Blueprint $table) {
            $table->foreignId('atendente_atual_id')->nullable()->after('cliente_ref')->constrained('atendentes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chamados', function (Blueprint $table) {
            $table->dropConstrainedForeignId('atendente_atual_id');
        });
    }
};
