<?php

namespace App\Services\Sistema;

use App\Models\Sistema;
use App\Support\CacheSistema;

class UpdateSistemaService
{
    /**
     * @param  array{nome?: string, jwks_url?: string, status?: string}  $dados
     */
    public function handle(Sistema $sistema, array $dados): Sistema
    {
        $sistema->update($dados);

        // Invalidação explícita (não TTL): desativar um sistema ou girar a
        // jwks_url precisa derrubar/atualizar o acesso imediatamente para a
        // validação do token do cliente — ver App\Support\CacheSistema.
        CacheSistema::esquecer($sistema->codigo);

        return $sistema;
    }
}
