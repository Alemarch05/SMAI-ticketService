<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LogHistoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class LogHistoryController extends Controller
{
    protected LogHistoryService $service;

    public function __construct(LogHistoryService $service)
    {
        $this->service = $service;
    }

    /**
     * Obtenemos la bitácora de un ticket
     */
    public function getLogs(int $ticketId): JsonResponse
    {
        $logs = $this->service->getLogsByTicket($ticketId);

        return response()->json([
            'success' => true,
            'data' => $logs
        ], 200);
    }

    /**
     * Registrar un evento manualmente en la bitácora
     */
    public function storeLog(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ticket_id' => 'required|integer',
            'action' => 'required|string|max:255',
            'comment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        // Obtenemos el ID del usuario directamente del JWT
        $data['user_id'] = auth()->id() ?? 1;

        $log = $this->service->createLog($data);

        return response()->json([
            'success' => true,
            'message' => 'Evento registrado en bitácora',
            'data' => $log
        ], 201);
    }

    /**
     * Consultar casos históricos (Dataset para IA)
     */
    public function getHistoricalCases(): JsonResponse
    {
        $cases = $this->service->getHistoricalCases();

        return response()->json([
            'success' => true,
            'data' => $cases
        ], 200);
    }
}