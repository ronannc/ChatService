<?php

namespace App\Services\Mensagem;

use App\Models\Mensagem;
use Illuminate\Contracts\Pagination\CursorPaginator;

class ListarMensagensService
{
    /**
     * Histórico paginado por cursor — evita duplicar/pular mensagens com
     * inserts concorrentes (diferente de paginação por offset). Ordenação
     * por `created_at` + `id` (desempate estável para mensagens no mesmo
     * timestamp). Ignora o status do chamado por desenho: leitura é
     * permitida a qualquer participante autorizado independente do status.
     */
    public function handle(int $chamadoId): CursorPaginator
    {
        return Mensagem::where('chamado_id', $chamadoId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->cursorPaginate(50);
    }
}
