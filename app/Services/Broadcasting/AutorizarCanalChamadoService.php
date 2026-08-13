<?php

namespace App\Services\Broadcasting;

use App\Models\Atendente;
use App\Models\Chamado;
use App\Models\Scopes\SistemaScope;
use App\Services\Auth\ClienteAutenticadoBroadcast;
use App\Support\AtendenteContext;
use App\Support\SistemaContext;

/**
 * Autoriza a assinatura do canal privado `chamado.{chamadoId}` (CHAT-006).
 * Chamado pelo callback registrado em routes/channels.php — o `$principal`
 * já vem resolvido por um dos guards da opção `guards` do canal (`sanctum`
 * para o atendente, `cliente-broadcast` para o cliente final), então esta
 * classe só decide a regra de autorização, não a autenticação.
 *
 * Devolve só true/false — sem payload adicional, por desenho (ver plano
 * aprovado do épico).
 */
class AutorizarCanalChamadoService
{
    public function handle(mixed $principal, string $chamadoId): bool
    {
        return match (true) {
            $principal instanceof ClienteAutenticadoBroadcast => $this->autorizarCliente($principal, $chamadoId),
            $principal instanceof Atendente => $this->autorizarAtendente($principal, $chamadoId),
            default => false,
        };
    }

    /**
     * Autorizado se o chamado pertence ao mesmo sistema emissor do token
     * (`iss`) e ao mesmo usuário dentro daquele sistema (`sub`) — a
     * identidade do cliente é sempre o par `(iss, sub)`, nunca só um dos
     * dois (ver docs/contratos/token-cliente.md).
     */
    private function autorizarCliente(ClienteAutenticadoBroadcast $cliente, string $chamadoId): bool
    {
        $token = $cliente->token;

        app(SistemaContext::class)->set($token->iss);

        $chamado = Chamado::find($chamadoId);

        return $chamado !== null
            && $chamado->sistema_id === $token->iss
            && $chamado->cliente_ref === $token->sub;
    }

    /**
     * Um atendente pode ter permissão em vários sistemas ao mesmo tempo — o
     * global scope Eloquent de sistema único (SistemaScope) não serve aqui
     * por desenho, então a leitura do chamado passa por ele e a isolação de
     * verdade fica só por conta da policy de RLS adicional
     * (`chamados_sistemas_permitidos_atendente`, permissiva, some por OR com
     * a policy de sistema único). `withoutGlobalScope` aqui não abre exceção
     * nenhuma no isolamento: sem o GUC setado por
     * `definirSistemasPermitidosAtendente()` corresponder à policy, o
     * Postgres não devolve a linha de qualquer forma (FORCE ROW LEVEL
     * SECURITY).
     */
    private function autorizarAtendente(Atendente $atendente, string $chamadoId): bool
    {
        app(AtendenteContext::class)->set($atendente);

        $sistemasPermitidos = app(AtendenteContext::class)->sistemasPermitidos();

        app(SistemaContext::class)->definirSistemasPermitidosAtendente($sistemasPermitidos);

        $chamado = Chamado::withoutGlobalScope(SistemaScope::class)->find($chamadoId);

        return $chamado !== null && $sistemasPermitidos->contains($chamado->sistema_id);
    }
}
