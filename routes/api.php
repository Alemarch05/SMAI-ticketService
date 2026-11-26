<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\LogHistoryController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/categories', [CategoryController::class, 'store']);

Route::middleware('auth:api')->group(function () {
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}/diagnostic-tree', [CategoryController::class, 'getDiagnosticTree']);
    Route::get('/categories/{id}/standard-solutions', [CategoryController::class, 'getStandardSolutions']);

    Route::post('/tickets/{ticketId}/assign', [TicketController::class, 'assignToSupport']);
    Route::patch('/tickets/{ticketId}/resolution', [TicketController::class, 'updateResolution']);
    Route::apiResource('tickets', TicketController::class);

    // Endpoints del Módulo C
    Route::get('/tickets/{ticketId}/logs', [LogHistoryController::class, 'getLogs']);
    Route::post('/tickets/logs', [LogHistoryController::class, 'storeLog']);
    Route::get('/historical-cases', [LogHistoryController::class, 'getHistoricalCases']);
});
