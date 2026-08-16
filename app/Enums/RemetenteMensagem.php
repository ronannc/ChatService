<?php

namespace App\Enums;

enum RemetenteMensagem: string
{
    case Cliente = 'cliente';
    case Atendente = 'atendente';
    case Bot = 'bot';
}
