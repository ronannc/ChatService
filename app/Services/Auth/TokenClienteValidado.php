<?php

namespace App\Services\Auth;

use App\Support\ContratoTokenCliente;

/**
 * Resultado de uma validação bem-sucedida do token do cliente final
 * (`ValidarTokenClienteService`). `iss` é o `codigo` do sistema emissor, já
 * confirmado cadastrado e ativo — é o valor usado para resolver o contexto
 * de `sistema_id` via `SistemaContext::set()`.
 *
 * `role` é exposta como campo de primeira classe (não só dentro de
 * `claims`) porque CHAT-005B a usa para decidir entre o fluxo de cliente e
 * o de provisionamento de atendente externo — falha fechada por padrão:
 * ausência da claim vira `ContratoTokenCliente::ROLE_CLIENTE`, nunca
 * `atendente`.
 */
final readonly class TokenClienteValidado
{
    /**
     * @param  array<string, mixed>  $claims
     */
    public function __construct(
        public string $iss,
        public string $sub,
        public string $scope,
        public string $role,
        public array $claims,
    ) {}

    public function ehAtendente(): bool
    {
        return $this->role === ContratoTokenCliente::ROLE_ATENDENTE;
    }
}
