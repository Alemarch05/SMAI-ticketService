<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketDiagnostic;
use App\Repositories\TicketLogRepository;
use App\Repositories\TicketRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TicketService
{
    public function __construct(
        protected TicketRepository $ticketRepository,
        protected TicketLogRepository $ticketLogRepository,
    ) {
    }

    public function getAllTickets(array $filters = []): Collection|LengthAwarePaginator
    {
        return $this->ticketRepository->getAll($filters);
    }

    public function getTicketById(int $id): ?Ticket
    {
        return $this->ticketRepository->findById($id, ['category', 'logs', 'diagnostic']);
    }

    public function createTicket(array $data): Ticket
    {
        $decisions = $data['decisions'] ?? null;
        $diagnosticCategoryId = $data['diagnostic_category_id'] ?? null;
        unset($data['decisions'], $data['diagnostic_category_id']);

        $data['status'] = Ticket::STATUS_PENDIENTE_DIAGNOSTICO;
        $data['priority'] = $data['priority'] ?? Ticket::PRIORITY_MEDIA;

        return DB::transaction(function () use ($data, $decisions, $diagnosticCategoryId) {
            $ticket = $this->ticketRepository->create($data);

            if (!empty($decisions) && !empty($diagnosticCategoryId)) {
                TicketDiagnostic::create([
                    'ticket_id'   => $ticket->id,
                    'category_id' => $diagnosticCategoryId,
                    'decisions'   => $decisions,
                ]);
            }

            $this->recordLog(
                ticketId: $ticket->id,
                userId: (int) $data['user_id'],
                action: 'CREACION',
                comment: "Ticket creado en {$ticket->location} con prioridad {$ticket->priority} y estado {$ticket->status}"
            );

            if (! empty($ticket->assigned_to)) {
                $this->recordLog(
                    ticketId: $ticket->id,
                    userId: (int) $data['user_id'],
                    action: 'ASIGNACION',
                    comment: "Ticket asignado al técnico #{$ticket->assigned_to}"
                );
            }

            return $ticket->load(['category:id,name,description', 'diagnostic']);
        });
    }

    public function updateTicket(int $id, array $data, int $actorUserId): ?Ticket
    {
        $ticket = $this->ticketRepository->findById($id);

        if (! $ticket) {
            return null;
        }

        return DB::transaction(function () use ($ticket, $data, $actorUserId) {
            $userComment = isset($data['comment']) ? trim((string) $data['comment']) : null;
            unset($data['comment']);

            $original = [
                'status' => $ticket->status,
                'priority' => $ticket->priority,
                'assigned_to' => $ticket->assigned_to,
                'location' => $ticket->location,
                'title' => $ticket->title,
                'description' => $ticket->description,
                'category_id' => $ticket->category_id,
                'cause' => $ticket->cause,
                'solution' => $ticket->solution,
                'level1_diagnostic' => $ticket->level1_diagnostic,
            ];

            $updatedTicket = $this->ticketRepository->update($ticket, $data);

            $this->recordUpdateLogs($updatedTicket, $original, $data, $actorUserId, $userComment);

            return $updatedTicket->load(['category:id,name,description', 'logs', 'diagnostic']);
        });
    }

    public function deleteTicket(int $id, int $actorUserId): bool
    {
        $ticket = $this->ticketRepository->findById($id);

        if (! $ticket) {
            return false;
        }

        return DB::transaction(function () use ($ticket, $actorUserId) {
            Log::info('Ticket eliminado', [
                'ticket_id' => $ticket->id,
                'deleted_by' => $actorUserId,
                'owner_user_id' => $ticket->user_id,
                'location' => $ticket->location,
                'title' => $ticket->title,
            ]);

            return $this->ticketRepository->delete($ticket);
        });
    }

    private function recordUpdateLogs(
        Ticket $updatedTicket,
        array $original,
        array $submittedData,
        int $actorUserId,
        ?string $userComment
    ): void {
        $loggedSpecificChange = false;

        if (array_key_exists('status', $submittedData) && $updatedTicket->status !== $original['status']) {
            $action = match ($updatedTicket->status) {
                Ticket::STATUS_CERRADO => 'CIERRE',
                Ticket::STATUS_ESCALADO => 'ESCALAMIENTO',
                default => 'CAMBIO_ESTADO',
            };

            $this->recordLog(
                ticketId: $updatedTicket->id,
                userId: $actorUserId,
                action: $action,
                comment: $this->appendComment(
                    "Estado actualizado de {$original['status']} a {$updatedTicket->status}",
                    $userComment
                )
            );
            $loggedSpecificChange = true;
        }

        if (array_key_exists('priority', $submittedData) && $updatedTicket->priority !== $original['priority']) {
            $this->recordLog(
                ticketId: $updatedTicket->id,
                userId: $actorUserId,
                action: 'CAMBIO_PRIORIDAD',
                comment: $this->appendComment(
                    "Prioridad actualizada de {$original['priority']} a {$updatedTicket->priority}",
                    $userComment
                )
            );
            $loggedSpecificChange = true;
        }

        if (array_key_exists('assigned_to', $submittedData) && $updatedTicket->assigned_to !== $original['assigned_to']) {
            $assignmentText = $updatedTicket->assigned_to !== null
                ? "Ticket asignado al técnico #{$updatedTicket->assigned_to}"
                : 'Asignación de técnico removida';

            $this->recordLog(
                ticketId: $updatedTicket->id,
                userId: $actorUserId,
                action: 'ASIGNACION',
                comment: $this->appendComment($assignmentText, $userComment)
            );
            $loggedSpecificChange = true;
        }

        $generalFields = ['location', 'title', 'description', 'category_id', 'cause', 'solution', 'level1_diagnostic'];
        $changedGeneralFields = array_values(array_filter(
            $generalFields,
            static fn (string $field): bool => array_key_exists($field, $submittedData)
                && $updatedTicket->{$field} !== $original[$field]
        ));

        if (! empty($changedGeneralFields) || (! $loggedSpecificChange && ! empty($userComment))) {
            $baseMessage = ! empty($changedGeneralFields)
                ? 'Campos actualizados: ' . implode(', ', $changedGeneralFields)
                : 'Actualización de ticket registrada';

            $this->recordLog(
                ticketId: $updatedTicket->id,
                userId: $actorUserId,
                action: 'ACTUALIZACION',
                comment: $this->appendComment($baseMessage, $userComment)
            );
        }
    }

    private function recordLog(int $ticketId, int $userId, string $action, string $comment): void
    {
        $this->ticketLogRepository->create([
            'ticket_id' => $ticketId,
            'user_id' => $userId,
            'action' => $action,
            'comment' => $comment,
        ]);
    }

    private function appendComment(string $baseMessage, ?string $userComment): string
    {
        if (empty($userComment)) {
            return $baseMessage;
        }

        return "{$baseMessage}. {$userComment}";
    }
}
