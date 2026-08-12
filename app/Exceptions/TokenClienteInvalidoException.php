<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Lançada para qualquer um dos motivos fechados de invalidez do token do
 * cliente final (docs/contratos/token-cliente.md §4).
 *
 * A mensagem pública (`getMessage()`) é sempre a mesma rejeição genérica —
 * o `motivo` específico existe só para o chamador logar, nunca para compor
 * a resposta ao cliente. É proteção deliberada: distinguir os motivos na
 * resposta daria a um atacante um oráculo sobre qual parte do token está
 * errada.
 */
class TokenClienteInvalidoException extends RuntimeException
{
    public function __construct(public readonly string $motivo)
    {
        parent::__construct('Token inválido.');
    }
}
