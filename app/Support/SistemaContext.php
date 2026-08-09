<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class SistemaContext
{
    private ?string $codigo = null;

    private bool $bypassAtivo = false;

    /**
     * Define o sistema atual da request, propagando para a sessão do
     * Postgres (lido pelas policies de Row Level Security via
     * current_setting('app.current_sistema_id', true)).
     */
    public function set(string $codigo): void
    {
        $this->codigo = $codigo;

        DB::statement('SELECT set_config(?, ?, false)', ['app.current_sistema_id', $codigo]);
    }

    /**
     * Limpa o contexto — nenhuma linha das tabelas isoladas deve ser
     * lida ou gravada enquanto não houver um sistema definido.
     */
    public function clear(): void
    {
        $this->codigo = null;

        DB::statement('SELECT set_config(?, ?, false)', ['app.current_sistema_id', '']);
    }

    public function get(): ?string
    {
        return $this->codigo;
    }

    /**
     * Nome do GUC do Postgres lido pela policy de RLS de `atendentes`
     * (ver migration allow_login_lookup_bypass_rls_em_atendentes). Usado
     * tanto pelo login (App\Services\Atendente\LoginAtendenteService, com
     * escopo LOCAL/transaction) quanto pela resolução do token Sanctum
     * (EnableAtendenteAuthRlsBypass/ResolveAtendenteContext, com escopo de
     * sessão) — ambos precisam achar a linha do atendente antes de
     * qualquer sistema_id estar no contexto.
     */
    public const GUC_BYPASS_RESOLUCAO_ATENDENTE = 'app.bypass_rls_resolucao_atendente';

    /**
     * Liga o bypass usado pela resolução de identidade do atendente (login
     * e resolução do token Sanctum): nem o global scope do Eloquent
     * (SistemaScope, só para o model Atendente) nem a RLS do Postgres
     * podem filtrar essa leitura específica.
     */
    public function ativarBypassParaResolucaoDeAtendente(): void
    {
        $this->bypassAtivo = true;

        DB::statement('SELECT set_config(?, ?, false)', [self::GUC_BYPASS_RESOLUCAO_ATENDENTE, 'true']);
    }

    /**
     * Desliga o bypass. Chamado tanto assim que o atendente é resolvido
     * (antes do controller rodar, no caminho de sucesso) quanto no
     * `terminate()` de EnableAtendenteAuthRlsBypass (garantindo que
     * desliga mesmo se a autenticação falhar no meio do caminho).
     */
    public function desativarBypassParaResolucaoDeAtendente(): void
    {
        $this->bypassAtivo = false;

        DB::statement('SELECT set_config(?, ?, false)', [self::GUC_BYPASS_RESOLUCAO_ATENDENTE, 'false']);
    }

    public function bypassAtivo(): bool
    {
        return $this->bypassAtivo;
    }
}
