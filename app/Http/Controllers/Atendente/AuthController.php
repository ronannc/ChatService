<?php

namespace App\Http\Controllers\Atendente;

use App\Http\Controllers\Controller;
use App\Http\Requests\Atendente\LoginAtendenteRequest;
use App\Services\Atendente\LoginAtendenteService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function login(LoginAtendenteRequest $request, LoginAtendenteService $service): JsonResponse
    {
        $resultado = $service->handle($request->validated());

        return response()->json([
            'atendente' => $resultado['atendente'],
            'token' => $resultado['token'],
        ], 200);
    }
}
