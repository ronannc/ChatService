<?php

namespace App\Services\Chamado;

use App\Enums\StatusChamado;
use App\Models\Chamado;
use App\Models\Scopes\SistemaScope;
use App\Support\AtendenteContext;
use App\Support\SistemaContext;
use Illuminate\Pagination\LengthAwarePaginator;

class ListarFilaChamadosService
{
    public function __construct(
        private readonly AtendenteContext $atendenteContext,
        private readonly SistemaContext $sistemaContext,
    ) {}

    public function handle(): LengthAwarePaginator
    {
        $sistemasPermitidos = $this->atendenteContext->sistemasPermitidos();

        if ($sistemasPermitidos->isEmpty()) {
            return new LengthAwarePaginator([], 0, 15);
        }

        $this->sistemaContext->definirSistemasPermitidosAtendente($sistemasPermitidos);

        try {
            return Chamado::withoutGlobalScope(SistemaScope::class)
                ->where('status', StatusChamado::AguardandoFila)
                ->whereIn('sistema_id', $sistemasPermitidos)
                ->orderBy('created_at')
                ->orderBy('id')
                ->paginate();
        } finally {
            $this->sistemaContext->limparSistemasPermitidosAtendente();
        }
    }
}
