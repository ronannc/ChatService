<?php

namespace App\Enums;

enum EncerradoPor: string
{
    case Atendente = 'atendente';
    case Cliente = 'cliente';
    case Sistema = 'sistema';
}
