<?php

namespace App\Repositories;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Collection;

class TicketRepository
{
    public function getAll(): Collection
    {
        return Ticket::all();
    }

    public function create(array $data): Ticket
    {
        return Ticket::create($data);
    }
}