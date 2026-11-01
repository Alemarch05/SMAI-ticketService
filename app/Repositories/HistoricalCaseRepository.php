<?php

namespace App\Repositories;

use App\Models\HistoricalCase;
use Illuminate\Database\Eloquent\Collection;

class HistoricalCaseRepository
{
    public function getAll(): Collection
    {
        return HistoricalCase::all();
    }

    public function create(array $data): HistoricalCase
    {
        return HistoricalCase::create($data);
    }
}