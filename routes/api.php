<?php

use App\Http\Controllers\Admin\SistemaController;
use App\Http\Controllers\Atendente\AuthController;
use App\Http\Controllers\Atendente\MeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['admin.api-key', 'throttle:30,1'])->prefix('admin')->group(function () {
    Route::post('sistemas', [SistemaController::class, 'store']);
    Route::patch('sistemas/{sistema:codigo}', [SistemaController::class, 'update']);
});

Route::post('atendentes/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware(['atendente.auth-bypass', 'auth:sanctum', 'atendente.context'])->group(function () {
    Route::get('atendentes/me', [MeController::class, 'show']);
});
