<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Um atendente enxerga chamados de múltiplos sistemas (o próprio mais os
     * concedidos via `atendente_sistema`), não de um único sistema "atual" —
     * a policy original de `chamados` (sistema_id = current_setting(...))
     * não serve para esse caso, e filtrar client-side em loop não seria RLS
     * de verdade.
     *
     * Esta é uma policy PERMISSIVA adicional: policies permissivas se
     * combinam por OR, então isso não afrouxa a policy existente
     * (`chamados_isolamento_sistema`) — só amplia a visibilidade quando o
     * GUC `app.sistemas_permitidos_atendente` estiver setado com a lista de
     * códigos permitidos (ver App\Support\SistemaContext::
     * definirSistemasPermitidosAtendente()), setado pela aplicação a partir
     * de um atendente autenticado, nunca a partir de input direto do
     * cliente.
     *
     * Desenhada para ser reaproveitada por CHAT-010 (fila) e CHAT-021
     * (histórico consolidado multi-sistema) — hoje só `chamados` precisa
     * dela (CHAT-006); `mensagens` fica para quando CHAT-010 precisar.
     */
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE POLICY chamados_sistemas_permitidos_atendente ON chamados
                USING (
                    NULLIF(current_setting('app.sistemas_permitidos_atendente', true), '') IS NOT NULL
                    AND sistema_id = ANY(string_to_array(current_setting('app.sistemas_permitidos_atendente', true), ','))
                )
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP POLICY IF EXISTS chamados_sistemas_permitidos_atendente ON chamados');
    }
};
