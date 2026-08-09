<?php

namespace App\Http\Controllers\Atendente;

use App\Http\Controllers\Controller;
use App\Support\AtendenteContext;
use Illuminate\Http\JsonResponse;

class MeController extends Controller
{
    public function show(AtendenteContext $context): JsonResponse
    {
        return response()->json([
            'atendente' => $context->atendente(),
            'sistemas_permitidos' => $context->sistemasPermitidos(),
        ]);
    }
}
