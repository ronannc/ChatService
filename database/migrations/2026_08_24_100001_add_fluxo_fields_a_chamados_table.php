<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `fluxo_definicao_id` trava a versão exata da definição no momento em
     * que o chamado entra no fluxo (CHAT-023) — nunca é reapontado para uma
     * versão mais nova enquanto o chamado está em andamento nela, mesmo que
     * a definição "current" da mesma `chave` mude no meio do caminho.
     *
     * `no_atual` guarda o identificador (string) do nó corrente dentro do
     * JSON da definição; `respostas_coletadas` acumula as respostas do
     * cliente ao longo do fluxo (e de fluxos encadeados via nó tipo "fim",
     * dentro do MESMO chamado). Todas nullable: um chamado pode nunca ter
     * entrado em nenhum fluxo.
     */
    public function up(): void
    {
        Schema::table('chamados', function (Blueprint $table) {
            $table->foreignId('fluxo_definicao_id')->nullable()->after('atendente_atual_id')->constrained('fluxo_definicoes');
            $table->string('no_atual')->nullable()->after('fluxo_definicao_id');
            $table->jsonb('respostas_coletadas')->default('{}')->after('no_atual');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chamados', function (Blueprint $table) {
            $table->dropColumn('respostas_coletadas');
            $table->dropColumn('no_atual');
            $table->dropConstrainedForeignId('fluxo_definicao_id');
        });
    }
};
