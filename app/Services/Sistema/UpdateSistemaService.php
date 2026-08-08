<?php

namespace App\Services\Sistema;

use App\Models\Sistema;

class UpdateSistemaService
{
    /**
     * @param  array{nome?: string, jwks_url?: string, status?: string}  $dados
     */
    public function handle(Sistema $sistema, array $dados): Sistema
    {
        $sistema->update($dados);

        return $sistema;
    }
}
