<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChamadoRequest;
use App\Services\Auth\TokenClienteValidado;
use App\Services\Chamado\AssumirChamadoService;
use App\Services\Chamado\ListarFilaChamadosService;
use App\Services\Chamado\StoreChamadoService;
use Illuminate\Http\JsonResponse;

class ChamadoController extends Controller
{
    public function store(StoreChamadoRequest $request, StoreChamadoService $service): JsonResponse
    {
        /** @var TokenClienteValidado $tokenCliente */
        $tokenCliente = $request->attributes->get('token_cliente');

        $chamado = $service->handle($tokenCliente);

        return response()->json($chamado, 201);
    }

    public function index(ListarFilaChamadosService $service): JsonResponse
    {
        return response()->json($service->handle());
    }

    public function assumir(int $chamado, AssumirChamadoService $service): JsonResponse
    {
        return response()->json($service->handle($chamado));
    }
}
