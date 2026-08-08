<?php

use App\Http\Controllers\Admin\SistemaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['admin.api-key', 'throttle:30,1'])->prefix('admin')->group(function () {
    Route::post('sistemas', [SistemaController::class, 'store']);
    Route::patch('sistemas/{sistema:codigo}', [SistemaController::class, 'update']);
});
