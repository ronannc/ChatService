<?php

namespace App\Enums;

enum StatusChamado: string
{
    case FluxoEmAndamento = 'fluxo_em_andamento';
    case AguardandoFila = 'aguardando_fila';
    case EmAtendimento = 'em_atendimento';
    case AguardandoCliente = 'aguardando_cliente';
    case Resolvido = 'resolvido';
    case Finalizado = 'finalizado';
}
