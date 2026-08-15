<?php

namespace App\Services\Atendente;

use App\Enums\OrigemAtendente;
use App\Enums\StatusAtendente;
use App\Models\Atendente;
use App\Models\AtendenteSistema;
use App\Services\Auth\TokenClienteValidado;
use App\Support\SistemaContext;
use Illuminate\Support\Facades\DB;

/**
 * Resolve ou provisiona, just-in-time, o atendente externo autenticado via
 * JWT com `role=atendente` (CHAT-005B). Diferente do atendente interno
 * (CHAT-005A, Sanctum, cadastro prévio com e-mail/senha), aqui não existe
 * cadastro anterior: a própria apresentação de um token válido, assinado
 * por um sistema integrado cadastrado e ativo, é a garantia de confiança —
 * não há flag adicional de aprovação.
 *
 * Decisão do usuário (substituiu o desenho original deste Service): a
 * identidade do atendente externo é `sub_externo` **sozinho**, sem escopo
 * por `sistema_id` — a API core externa usa o mesmo `sub` para a mesma
 * pessoa em qualquer sistema integrado, então o mesmo atendente humano
 * autenticando via dois sistemas diferentes precisa continuar sendo o
 * MESMO registro `Atendente`. `sistema_id` na tabela `atendentes` é só o
 * sistema do primeiro provisionamento (a "home"); quem acumula os demais
 * sistemas é `atendente_sistema`, via `firstOrCreate` a cada chamada.
 *
 * **Risco aceito, não implícito** (ver .ai/rules/atendente-externo.md):
 * como a busca não é mais escopada por sistema, ela precisa ignorar o
 * global scope (`SistemaScope`) e a RLS de `atendentes` — do contrário um
 * atendente cuja "home" é outro sistema seria invisível e o
 * `firstOrCreate` criaria um segundo registro, batendo no unique de
 * `sub_externo` e lançando exceção em vez de correlacionar. Isso usa o
 * mesmo mecanismo de `LoginAtendenteService`
 * (`SistemaContext::GUC_BYPASS_RESOLUCAO_ATENDENTE`, `SET LOCAL` escopado à
 * transaction) — não o middleware `EnableAtendenteAuthRlsBypass` (que liga
 * o bypass pra request inteira); aqui o bypass dura só a própria consulta.
 * A consequência de segurança é aceita pelo usuário: um sistema integrado
 * comprometido que emita um `sub` colidente com o de um atendente já
 * vinculado a OUTRO sistema ganha vínculo (via `atendente_sistema`) com a
 * identidade existente daquele atendente, herdando o que esse atendente já
 * tem acesso via os demais sistemas vinculados.
 *
 * Idempotência via lookup-then-create (`firstOrCreate`), não via captura de
 * exceção de unique constraint: o índice único parcial
 * `atendentes_sub_externo_unique` é a rede de segurança contra corrida, não
 * o mecanismo primário.
 */
class ProvisionarAtendenteExternoService
{
    public function handle(TokenClienteValidado $tokenCliente): Atendente
    {
        // Cuidado ao aninhar: este `DB::transaction()` precisa ser sempre a
        // transação mais externa. Se este Service for chamado de dentro de
        // outra transação já aberta, o Laravel cria um SAVEPOINT em vez de
        // uma nova transação — e `SET LOCAL` dentro de um savepoint
        // sobrevive ao `RELEASE SAVEPOINT`, só sendo desfeito no
        // commit/rollback da transação externa. O bypass vazaria em
        // silêncio pro resto dela. Ver .ai/rules/atendente-externo.md.
        $atendente = DB::transaction(function () use ($tokenCliente): Atendente {
            DB::statement(
                'SELECT set_config(?, ?, true)',
                [SistemaContext::GUC_BYPASS_RESOLUCAO_ATENDENTE, 'true'],
            );

            return Atendente::withoutGlobalScopes()->firstOrCreate(
                ['sub_externo' => $tokenCliente->sub],
                [
                    'sistema_id' => $tokenCliente->iss,
                    'nome' => "Atendente externo {$tokenCliente->sub}",
                    'status' => StatusAtendente::Ativo,
                    'origem' => OrigemAtendente::Externo,
                ],
            );
        });

        AtendenteSistema::firstOrCreate([
            'atendente_id' => $atendente->id,
            'sistema_id' => $tokenCliente->iss,
        ]);

        return $atendente;
    }
}
