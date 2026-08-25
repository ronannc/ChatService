<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `fluxo_definicoes` é intencionalmente global — sem `sistema_id`, sem
     * RLS. O motor de estados (CHAT-023) é único e compartilhado por todos
     * os sistemas integrados; o que é isolado por `sistema_id` é o ESTADO
     * de cada chamado dentro do fluxo (colunas novas em `chamados`), não a
     * definição do fluxo em si.
     *
     * `(chave, versao)` é único: uma definição nova de um fluxo existente
     * entra como versão incrementada, nunca sobrescreve a anterior — é o
     * que permite um chamado em andamento continuar preso à versão que
     * estava vigente no momento em que entrou no fluxo (snapshot via
     * `chamados.fluxo_definicao_id`, nunca uma referência "current").
     */
    public function up(): void
    {
        Schema::create('fluxo_definicoes', function (Blueprint $table) {
            $table->id();
            $table->string('chave');
            $table->unsignedInteger('versao');
            $table->jsonb('definicao');
            $table->timestamps();

            $table->unique(['chave', 'versao']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fluxo_definicoes');
    }
};
