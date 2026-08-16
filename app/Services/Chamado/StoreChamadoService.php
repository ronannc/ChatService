<?php

namespace App\Services\Chamado;

use App\Enums\StatusChamado;
use App\Models\Chamado;
use App\Services\Auth\TokenClienteValidado;

class StoreChamadoService
{
    /**
     * Cria um chamado para o cliente autenticado. `sistema_id` e
     * `cliente_ref` vêm exclusivamente do token já validado pelo middleware
     * `cliente.token` (`iss` e `sub`), nunca de input do request — um
     * `sistema_id` no body não tem efeito algum aqui.
     */
    public function handle(TokenClienteValidado $tokenCliente): Chamado
    {
        return Chamado::create([
            'sistema_id' => $tokenCliente->iss,
            'cliente_ref' => $tokenCliente->sub,
            'status' => StatusChamado::AguardandoFila,
        ]);
    }
}
