<?php

namespace App\Http\Controllers;

use App\Enums\RemetenteMensagem;
use App\Http\Requests\StoreMensagemRequest;
use App\Models\Chamado;
use App\Services\Mensagem\ListarMensagensService;
use App\Services\Mensagem\StoreMensagemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MensagemController extends Controller
{
    public function store(StoreMensagemRequest $request, StoreMensagemService $service): JsonResponse
    {
        /** @var Chamado $chamado */
        $chamado = $request->attributes->get('chamado');

        /** @var RemetenteMensagem $remetenteTipo */
        $remetenteTipo = $request->attributes->get('remetente_tipo');

        $remetenteRef = $request->attributes->get('remetente_ref');

        $mensagem = $service->handle($chamado->id, $remetenteTipo, $remetenteRef, $request->validated('texto'));

        return response()->json($mensagem, 201);
    }

    public function index(Request $request, ListarMensagensService $service): JsonResponse
    {
        /** @var Chamado $chamado */
        $chamado = $request->attributes->get('chamado');

        return response()->json($service->handle($chamado->id));
    }
}
