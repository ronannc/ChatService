<?php

namespace App\Support;

use Illuminate\Support\Collection;
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

    /**
     * Nome do GUC lido pela policy adicional de RLS de `chamados`
     * (`chamados_sistemas_permitidos_atendente`) — uma policy PERMISSIVA
     * extra (some por OR com `chamados_isolamento_sistema`), pensada para um
     * atendente que precisa enxergar chamados de múltiplos sistemas ao mesmo
     * tempo, não de um único "sistema atual". Diferente de
     * `GUC_BYPASS_RESOLUCAO_ATENDENTE`, isso não é bypass — é uma lista
     * explícita de sistemas permitidos, avaliada pela própria policy.
     */
    public const GUC_SISTEMAS_PERMITIDOS_ATENDENTE = 'app.sistemas_permitidos_atendente';

    /**
     * Propaga a lista de sistemas permitidos do atendente autenticado para a
     * sessão do Postgres, como string separada por vírgula (lida pela policy
     * via `string_to_array`). Usado hoje pela autorização de canal privado
     * de broadcasting (CHAT-006); desenhado para ser reaproveitado por
     * CHAT-010 (fila) e CHAT-021 (histórico consolidado multi-sistema).
     *
     * @param  Collection<int, string>|array<int, string>  $codigos
     */
    public function definirSistemasPermitidosAtendente(iterable $codigos): void
    {
        $lista = collect($codigos)->implode(',');

        DB::statement('SELECT set_config(?, ?, false)', [self::GUC_SISTEMAS_PERMITIDOS_ATENDENTE, $lista]);
    }

    /**
     * Limpa a lista de sistemas permitidos do atendente. Simetria com
     * `definirSistemasPermitidosAtendente()` — evita que a policy continue
     * enxergando uma lista de uma request anterior.
     */
    public function limparSistemasPermitidosAtendente(): void
    {
        DB::statement('SELECT set_config(?, ?, false)', [self::GUC_SISTEMAS_PERMITIDOS_ATENDENTE, '']);
    }
}
