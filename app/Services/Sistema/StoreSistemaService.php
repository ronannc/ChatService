<?php

namespace App\Services\Sistema;

use App\Enums\StatusSistema;
use App\Models\Sistema;
use App\Support\CacheSistema;

class StoreSistemaService
{
    /**
     * @param  array{codigo: string, nome: string, jwks_url: string, status?: string}  $dados
     */
    public function handle(array $dados): Sistema
    {
        $sistema = Sistema::create([
            ...$dados,
            'status' => $dados['status'] ?? StatusSistema::Ativo->value,
        ]);

        // Invalidação explícita (não TTL) do cache de cadastro lido pela
        // validação do token do cliente — ver App\Support\CacheSistema.
        CacheSistema::esquecer($sistema->codigo);

        return $sistema;
    }
}
