<?php

namespace App\Repositories;

use App\Models\TicketLog;
use Illuminate\Database\Eloquent\Collection;

class TicketLogRepository
{
    public function create(array $data): TicketLog
    {
        return TicketLog::create($data);
    }

    public function getByTicketId(int $ticketId): Collection
    {
        return TicketLog::where('ticket_id', $ticketId)->orderBy('created_at', 'desc')->get();
    }
}