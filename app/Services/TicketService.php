<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\TicketDiagnostic;
use App\Repositories\TicketRepository;
use App\Models\Ticket;

class TicketService
{
    protected TicketRepository $ticketRepository;

    public function __construct(TicketRepository $ticketRepository)
    {
        $this->ticketRepository = $ticketRepository;
    }

    public function createTicket(array $data): Ticket
    {
        return DB::transaction(function () use ($data) {
            $decisions = $data['decisions'] ?? null;
            $diagnosticCategoryId = $data['diagnostic_category_id'] ?? null;
            unset($data['decisions'], $data['diagnostic_category_id']);

            $data['status'] = 'PENDIENTE_DIAGNOSTICO';
            
            $ticket = $this->ticketRepository->create($data);

            if (!empty($decisions) && !empty($diagnosticCategoryId)) {
                TicketDiagnostic::create([
                    'ticket_id'   => $ticket->id,
                    'category_id' => $diagnosticCategoryId,
                    'decisions'   => $decisions,
                ]);
            }

            return $ticket;
        });
    }

    public function getAllTickets()
    {
        return $this->ticketRepository->getAll();
    }
}