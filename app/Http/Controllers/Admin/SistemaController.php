<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSistemaRequest;
use App\Http\Requests\Admin\UpdateSistemaRequest;
use App\Models\Sistema;
use App\Services\Sistema\StoreSistemaService;
use App\Services\Sistema\UpdateSistemaService;
use Illuminate\Http\JsonResponse;

class SistemaController extends Controller
{
    public function store(StoreSistemaRequest $request, StoreSistemaService $service): JsonResponse
    {
        $sistema = $service->handle($request->validated());

        return response()->json($sistema, 201);
    }

    public function update(UpdateSistemaRequest $request, Sistema $sistema, UpdateSistemaService $service): JsonResponse
    {
        $sistema = $service->handle($sistema, $request->validated());

        return response()->json($sistema);
    }
}
