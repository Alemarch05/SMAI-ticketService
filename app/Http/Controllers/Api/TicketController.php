<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TicketController extends Controller implements HasMiddleware
{
    public function __construct(
        protected TicketService $ticketService,
    ) {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('auth:api'),
        ];
    }

    /**
     * List all tickets or apply optional query filters.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $this->authenticatedUserId();
        if ($userId === null) {
            return $this->unauthenticatedResponse();
        }

        $filterInput = $this->prepareFilterInput($request->query());

        $validator = Validator::make($filterInput, [
            'status' => 'sometimes|array',
            'status.*' => ['string', Rule::in(Ticket::STATUSES)],
            'priority' => 'sometimes|array',
            'priority.*' => ['string', Rule::in(Ticket::PRIORITIES)],
            'category_id' => 'sometimes|integer|min:1',
            'user_id' => 'sometimes|integer|min:1',
            'assigned_to' => [
                'sometimes',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_string($value) && strtolower($value) === 'null') {
                        return;
                    }
                    if (filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                        $fail("El campo {$attribute} debe ser un entero positivo o 'null'.");
                    }
                },
            ],
            'unassigned' => 'sometimes|boolean',
            'mine' => 'sometimes|boolean',
            'location' => 'sometimes|string|max:255',
            'search' => 'sometimes|string|max:150',
            'from_date' => 'sometimes|date',
            'to_date' => 'sometimes|date|after_or_equal:from_date',
            'sort_by' => ['sometimes', 'string', Rule::in(['id', 'created_at', 'updated_at', 'status', 'priority', 'location'])],
            'sort_dir' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $filters = $validator->validated();
        if (! empty($filters['mine'])) {
            $filters['user_id'] = $userId;
        }
        unset($filters['mine']);

        $tickets = $this->ticketService->getAllTickets($filters);

        if ($tickets instanceof LengthAwarePaginator) {
            return response()->json([
                'success' => true,
                'data' => $tickets->items(),
                'meta' => [
                    'current_page' => $tickets->currentPage(),
                    'last_page' => $tickets->lastPage(),
                    'per_page' => $tickets->perPage(),
                    'total' => $tickets->total(),
                ],
            ], 200);
        }

        return response()->json([
            'success' => true,
            'data' => $tickets,
        ], 200);
    }

    /**
     * Create a new support ticket owned by the authenticated user.
     */
    public function store(Request $request): JsonResponse
    {
        $userId = $this->authenticatedUserId();
        if ($userId === null) {
            return $this->unauthenticatedResponse();
        }

        $input = $this->normalizeUppercaseFields($request->all(), ['priority']);

        $validator = Validator::make($input, [
            'location' => 'required|string|max:255',
            'category_id' => 'required|integer|exists:TK_categories,id',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'user_name' => 'sometimes|nullable|string|max:255',
            'priority' => ['sometimes', 'required', 'string', Rule::in(Ticket::PRIORITIES)],
            'assigned_to' => 'sometimes|nullable|integer|min:1',
            'level1_diagnostic' => 'sometimes|nullable|array',
            'diagnostic_category_id' => 'nullable|integer|in:1,2,3,4',
            'decisions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['user_id'] = $userId;

        $ticket = $this->ticketService->createTicket($data);

        return response()->json([
            'success' => true,
            'message' => 'Ticket creado con éxito',
            'data' => $ticket,
        ], 201);
    }

    /**
     * Show detailed information for a specific ticket.
     */
    public function show(int|string $id): JsonResponse
    {
        $ticketId = $this->parseTicketId($id);
        if ($ticketId === null) {
            return $this->notFoundResponse();
        }

        $ticket = $this->ticketService->getTicketById($ticketId);
        if (! $ticket) {
            return $this->notFoundResponse();
        }

        return response()->json([
            'success' => true,
            'data' => $ticket,
        ], 200);
    }

    /**
     * Assign a ticket to the authenticated support agent and escalate it.
     */
    public function assignToSupport(int|string $ticketId): JsonResponse
    {
        $userId = $this->authenticatedUserId();
        if ($userId === null) {
            return $this->unauthenticatedResponse();
        }

        $parsedTicketId = $this->parseTicketId($ticketId);
        if ($parsedTicketId === null) {
            return $this->notFoundResponse();
        }

        $ticket = $this->ticketService->assignToSupport($parsedTicketId, $userId);
        if (! $ticket) {
            return $this->notFoundResponse();
        }

        return response()->json([
            'success' => true,
            'message' => 'Ticket asignado al equipo de soporte y escalado',
            'data' => $ticket,
        ], 200);
    }

    /**
     * Record the cause and solution for a ticket assigned to the authenticated user.
     */
    public function updateResolution(Request $request, int|string $ticketId): JsonResponse
    {
        $userId = $this->authenticatedUserId();
        if ($userId === null) {
            return $this->unauthenticatedResponse();
        }

        $parsedTicketId = $this->parseTicketId($ticketId);
        if ($parsedTicketId === null) {
            return $this->notFoundResponse();
        }

        $validator = Validator::make($request->all(), [
            'cause' => 'required|string|max:255',
            'solution' => 'required|string',
            'standard_solution' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $ticket = $this->ticketService->updateAssignedTicketResolution(
                $parsedTicketId,
                $validator->validated(),
                $userId
            );
        } catch (AuthorizationException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 403);
        } catch (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'errors' => $exception->errors(),
            ], 422);
        }

        if (! $ticket) {
            return $this->notFoundResponse();
        }

        return response()->json([
            'success' => true,
            'message' => 'Causa y solución registradas con éxito',
            'data' => $ticket,
        ], 200);
    }

    /**
     * Update ticket status, priority, description, or administrative details.
     */
    public function update(Request $request, int|string $id): JsonResponse
    {
        $userId = $this->authenticatedUserId();
        if ($userId === null) {
            return $this->unauthenticatedResponse();
        }

        $ticketId = $this->parseTicketId($id);
        if ($ticketId === null) {
            return $this->notFoundResponse();
        }

        $input = $this->normalizeUppercaseFields($request->all(), ['status', 'priority']);

        $validator = Validator::make($input, [
            'location' => 'sometimes|required|string|max:255',
            'category_id' => 'sometimes|required|integer|exists:TK_categories,id',
            'title' => 'sometimes|nullable|string|max:255',
            'description' => 'sometimes|nullable|string',
            'user_name' => 'sometimes|nullable|string|max:255',
            'status' => ['sometimes', 'required', 'string', Rule::in(Ticket::STATUSES)],
            'priority' => ['sometimes', 'required', 'string', Rule::in(Ticket::PRIORITIES)],
            'assigned_to' => 'missing',
            'level1_diagnostic' => 'sometimes|nullable|array',
            'cause' => 'missing',
            'solution' => 'missing',
            'comment' => 'sometimes|nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        if (empty($data)) {
            return response()->json([
                'success' => false,
                'message' => 'Debe proporcionar al menos un campo válido para actualizar',
            ], 422);
        }

        $ticket = $this->ticketService->updateTicket($ticketId, $data, $userId);
        if (! $ticket) {
            return $this->notFoundResponse();
        }

        return response()->json([
            'success' => true,
            'message' => 'Ticket actualizado con éxito',
            'data' => $ticket,
        ], 200);
    }

    /**
     * Delete a ticket by ID.
     */
    public function destroy(int|string $id): JsonResponse
    {
        $userId = $this->authenticatedUserId();
        if ($userId === null) {
            return $this->unauthenticatedResponse();
        }

        $ticketId = $this->parseTicketId($id);
        if ($ticketId === null) {
            return $this->notFoundResponse();
        }

        $deleted = $this->ticketService->deleteTicket($ticketId, $userId);
        if (! $deleted) {
            return $this->notFoundResponse();
        }

        return response()->json([
            'success' => true,
            'message' => 'Ticket eliminado con éxito',
        ], 200);
    }

    private function authenticatedUserId(): ?int
    {
        $userId = auth('api')->id();

        return $userId !== null ? (int) $userId : null;
    }

    private function parseTicketId(int|string $id): ?int
    {
        if (is_int($id)) {
            return $id > 0 ? $id : null;
        }

        if (! ctype_digit($id)) {
            return null;
        }

        $parsed = (int) $id;

        return $parsed > 0 ? $parsed : null;
    }

    private function normalizeUppercaseFields(array $input, array $fields): array
    {
        // Alias aceptados desde el frontend (ej. "Alta Prioridad", "Crítica", "high") mapeados al enum BAJA/MEDIA/ALTA
        $priorityAliases = [
            'BAJO' => Ticket::PRIORITY_BAJA,
            'LOW' => Ticket::PRIORITY_BAJA,
            'BAJA PRIORIDAD' => Ticket::PRIORITY_BAJA,
            'PRIORIDAD BAJA' => Ticket::PRIORITY_BAJA,
            'MEDIO' => Ticket::PRIORITY_MEDIA,
            'MEDIUM' => Ticket::PRIORITY_MEDIA,
            'MEDIA PRIORIDAD' => Ticket::PRIORITY_MEDIA,
            'PRIORIDAD MEDIA' => Ticket::PRIORITY_MEDIA,
            'ALTO' => Ticket::PRIORITY_ALTA,
            'HIGH' => Ticket::PRIORITY_ALTA,
            'ALTA PRIORIDAD' => Ticket::PRIORITY_ALTA,
            'PRIORIDAD ALTA' => Ticket::PRIORITY_ALTA,
            'CRITICA' => Ticket::PRIORITY_ALTA,
            'CRITICO' => Ticket::PRIORITY_ALTA,
            'CRITICAL' => Ticket::PRIORITY_ALTA,
            'URGENTE' => Ticket::PRIORITY_ALTA,
        ];

        foreach ($fields as $field) {
            if (isset($input[$field]) && is_string($input[$field])) {
                $normalized = $this->normalizeEnumText($input[$field]);
                if ($field === 'priority' && isset($priorityAliases[$normalized])) {
                    $normalized = $priorityAliases[$normalized];
                }
                $input[$field] = $normalized;
            }
        }

        return $input;
    }

    /**
     * Convierte a mayúsculas, elimina tildes y espacios repetidos (ej. "Crítica " => "CRITICA").
     */
    private function normalizeEnumText(string $value): string
    {
        $upper = mb_strtoupper(trim($value), 'UTF-8');
        $upper = strtr($upper, ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U']);

        return (string) preg_replace('/\s+/', ' ', $upper);
    }

    private function prepareFilterInput(array $query): array
    {
        $filtered = [];

        foreach ($query as $key => $value) {
            if ($value === '' || $value === null || $value === []) {
                continue;
            }

            if (in_array($key, ['status', 'priority'], true)) {
                $items = is_array($value) ? $value : explode(',', (string) $value);
                $normalized = array_values(array_filter(array_map(
                    static fn ($item) => strtoupper(trim((string) $item)),
                    $items
                )));
                if (! empty($normalized)) {
                    $filtered[$key] = $normalized;
                }
                continue;
            }

            if (in_array($key, ['mine', 'unassigned'], true)) {
                $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                $filtered[$key] = $bool ?? $value;
                continue;
            }

            if ($key === 'sort_dir' && is_string($value)) {
                $filtered[$key] = strtolower(trim($value));
                continue;
            }

            if (in_array($key, ['search', 'location'], true) && is_string($value)) {
                $trimmed = trim($value);
                if ($trimmed !== '') {
                    $filtered[$key] = $trimmed;
                }
                continue;
            }

            $filtered[$key] = $value;
        }

        return $filtered;
    }

    private function notFoundResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Ticket no encontrado',
        ], 404);
    }

    private function unauthenticatedResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Usuario no autenticado',
        ], 401);
    }
}
