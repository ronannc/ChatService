<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * As policies de RLS (CHAT-003) comparam sistema_id com
     * current_setting('app.current_sistema_id', true), que retorna string
     * vazia quando o contexto é explicitamente limpo. Sem essa constraint,
     * um sistema com codigo = '' faria a policy vazar suas linhas para
     * qualquer conexão sem contexto definido.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE sistemas ADD CONSTRAINT sistemas_codigo_nao_vazio CHECK (codigo <> '')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE sistemas DROP CONSTRAINT sistemas_codigo_nao_vazio');
    }
};
