<?php

namespace App\Services;

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
        $data['status'] = 'PENDIENTE_DIAGNOSTICO';
        
        return $this->ticketRepository->create($data);
    }

    public function getAllTickets()
    {
        return $this->ticketRepository->getAll();
    }
}