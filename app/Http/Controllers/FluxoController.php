<?php

namespace App\Http\Controllers;

use App\Http\Requests\AvancarFluxoRequest;
use App\Services\Auth\TokenClienteValidado;
use App\Services\Fluxo\AvancarFluxoService;
use Illuminate\Http\JsonResponse;

class FluxoController extends Controller
{
    public function avancar(int $chamado, AvancarFluxoRequest $request, AvancarFluxoService $service): JsonResponse
    {
        /** @var TokenClienteValidado $tokenCliente */
        $tokenCliente = $request->attributes->get('token_cliente');

        $chamadoAtualizado = $service->handle($chamado, $tokenCliente, $request->validated());

        return response()->json($chamadoAtualizado);
    }
}
