<?php

namespace App\Http\Middleware;

use App\Models\Atendente;
use App\Models\Chamado;
use App\Services\Auth\TokenClienteValidado;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guard de LEITURA do histórico de mensagens — deliberadamente distinto de
 * `EnsureAutorizadoEnviarMensagem`: ignora o status do chamado (histórico é
 * legível mesmo resolvido/finalizado), só confirma que o principal
 * autenticado é participante (cliente dono ou atendente_atual_id).
 */
class EnsureParticipanteChamado
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $chamado = Chamado::findOrFail($request->route('chamado'));

        $tokenCliente = $request->attributes->get('token_cliente');
        $atendente = $request->user();

        $autorizado = match (true) {
            $tokenCliente instanceof TokenClienteValidado => $chamado->cliente_ref === $tokenCliente->sub,
            $atendente instanceof Atendente => $chamado->atendente_atual_id === $atendente->id,
            default => false,
        };

        if (! $autorizado) {
            abort(403, 'Não é participante deste chamado.');
        }

        $request->attributes->set('chamado', $chamado);

        return $next($request);
    }
}
