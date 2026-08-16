<?php

namespace App\Http\Middleware;

use App\Enums\RemetenteMensagem;
use App\Enums\StatusChamado;
use App\Models\Atendente;
use App\Models\Chamado;
use App\Services\Auth\TokenClienteValidado;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Roda depois de `IdentificarClienteMensagem`/`IdentificarAtendenteMensagem`
 * — decide só autorização de escrita, não autenticação. Guard dedicado e
 * distinto de `EnsureParticipanteChamado` (leitura): aqui o status do
 * chamado importa (resolvido/finalizado bloqueia qualquer envio), lá não.
 *
 * No sucesso, deixa `chamado`, `remetente_tipo` e `remetente_ref` nos
 * attributes da request para o controller/Service reaproveitarem sem
 * reconsultar o chamado nem reavaliar quem é o remetente.
 */
class EnsureAutorizadoEnviarMensagem
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $chamado = Chamado::findOrFail($request->route('chamado'));

        if (in_array($chamado->status, [StatusChamado::Resolvido, StatusChamado::Finalizado], true)) {
            abort(403, 'Chamado encerrado não aceita novas mensagens.');
        }

        $tokenCliente = $request->attributes->get('token_cliente');
        $atendente = $request->user();

        if ($tokenCliente instanceof TokenClienteValidado) {
            if ($chamado->cliente_ref !== $tokenCliente->sub) {
                abort(403, 'Cliente não é participante deste chamado.');
            }

            $request->attributes->set('remetente_tipo', RemetenteMensagem::Cliente);
            $request->attributes->set('remetente_ref', $tokenCliente->sub);
        } elseif ($atendente instanceof Atendente) {
            if ($chamado->atendente_atual_id !== $atendente->id) {
                abort(403, 'Atendente não é o responsável por este chamado.');
            }

            $request->attributes->set('remetente_tipo', RemetenteMensagem::Atendente);
            $request->attributes->set('remetente_ref', (string) $atendente->id);
        } else {
            abort(403, 'Não autenticado.');
        }

        $request->attributes->set('chamado', $chamado);

        return $next($request);
    }
}
