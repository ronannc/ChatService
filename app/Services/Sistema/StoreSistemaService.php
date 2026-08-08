<?php

namespace App\Services\Sistema;

use App\Enums\StatusSistema;
use App\Models\Sistema;

class StoreSistemaService
{
    /**
     * @param  array{codigo: string, nome: string, jwks_url: string, status?: string}  $dados
     */
    public function handle(array $dados): Sistema
    {
        return Sistema::create([
            ...$dados,
            'status' => $dados['status'] ?? StatusSistema::Ativo->value,
        ]);
    }
}
