<?php

namespace App\Services;

use App\Repositories\TicketLogRepository;
use App\Repositories\HistoricalCaseRepository;
use Illuminate\Database\Eloquent\Collection;

class LogHistoryService
{
    protected TicketLogRepository $logRepository;
    protected HistoricalCaseRepository $historyRepository;

    public function __construct(
        TicketLogRepository $logRepository,
        HistoricalCaseRepository $historyRepository
    ) {
        $this->logRepository = $logRepository;
        $this->historyRepository = $historyRepository;
    }

    public function getLogsByTicket(int $ticketId): Collection
    {
        return $this->logRepository->getByTicketId($ticketId);
    }

    public function createLog(array $data): \App\Models\TicketLog
    {
        return $this->logRepository->create($data);
    }

    public function getHistoricalCases(): Collection
    {
        return $this->historyRepository->getAll();
    }
}