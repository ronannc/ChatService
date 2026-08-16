<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * `GET /fila` (CHAT-010) filtra por `status = 'aguardando_fila'` e
     * `sistema_id IN (...)`, ordenando por `(created_at, id)`. O índice
     * composto existente `chamados_sistema_id_cliente_ref_index` (sistema_id,
     * cliente_ref) não cobre `status` nem o `ORDER BY`, forçando um `Sort`
     * explícito a cada poll do atendente. Índice parcial porque a fração de
     * linhas em `aguardando_fila` é pequena e transitória frente ao total
     * (chamados resolvidos/finalizados não são arquivados) — mais compacto e
     * barato de manter do que um índice composto cobrindo todos os status.
     */
    public function up(): void
    {
        DB::statement(
            "CREATE INDEX chamados_fila_por_sistema_index ON chamados (sistema_id, created_at) WHERE status = 'aguardando_fila'"
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS chamados_fila_por_sistema_index');
    }
};
