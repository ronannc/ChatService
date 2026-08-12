<?php

namespace App\Services\Auth;

/**
 * Resultado de uma validação bem-sucedida do token do cliente final
 * (`ValidarTokenClienteService`). `iss` é o `codigo` do sistema emissor, já
 * confirmado cadastrado e ativo — é o valor usado para resolver o contexto
 * de `sistema_id` via `SistemaContext::set()`.
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
        public array $claims,
    ) {}
}
