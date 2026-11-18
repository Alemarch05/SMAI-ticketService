<?php

namespace App\Repositories;

use App\Models\Ticket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class TicketRepository
{
    /**
     * Retrieve all or filtered tickets, optionally paginated.
     */
    public function getAll(array $filters = []): Collection|LengthAwarePaginator
    {
        $query = $this->buildFilteredQuery($filters);

        if (! empty($filters['per_page'])) {
            return $query->paginate((int) $filters['per_page']);
        }

        return $query->get();
    }

    public function findById(int $id, array $relations = []): ?Ticket
    {
        return Ticket::with($relations)->find($id);
    }

    public function create(array $data): Ticket
    {
        return Ticket::create($data);
    }

    public function update(Ticket $ticket, array $data): Ticket
    {
        $ticket->update($data);

        return $ticket->refresh();
    }

    public function delete(Ticket $ticket): bool
    {
        return (bool) $ticket->delete();
    }

    private function buildFilteredQuery(array $filters): Builder
    {
        $query = Ticket::query()->with(['category:id,name,description']);

        if (! empty($filters['status'])) {
            $query->whereIn('status', $this->normalizeListFilter($filters['status']));
        }

        if (! empty($filters['priority'])) {
            $query->whereIn('priority', $this->normalizeListFilter($filters['priority']));
        }

        if (isset($filters['category_id']) && $filters['category_id'] !== '') {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (isset($filters['user_id']) && $filters['user_id'] !== '') {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (! empty($filters['unassigned'])) {
            $query->whereNull('assigned_to');
        } elseif (array_key_exists('assigned_to', $filters) && $filters['assigned_to'] !== '') {
            if (is_string($filters['assigned_to']) && strtolower($filters['assigned_to']) === 'null') {
                $query->whereNull('assigned_to');
            } elseif ($filters['assigned_to'] !== null) {
                $query->where('assigned_to', (int) $filters['assigned_to']);
            }
        }

        if (! empty($filters['location'])) {
            $query->whereLike('location', '%' . trim((string) $filters['location']) . '%');
        }

        if (! empty($filters['search'])) {
            $term = '%' . trim((string) $filters['search']) . '%';
            $query->where(function (Builder $subQuery) use ($term) {
                $subQuery->whereLike('title', $term)
                    ->orWhereLike('description', $term)
                    ->orWhereLike('location', $term)
                    ->orWhereLike('cause', $term)
                    ->orWhereLike('solution', $term);
            });
        }

        if (! empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        $this->applySorting($query, $filters['sort_by'] ?? 'created_at', $filters['sort_dir'] ?? 'desc');

        return $query;
    }

    /**
     * @param  array<int, string>|string  $value
     * @return array<int, string>
     */
    private function normalizeListFilter(array|string $value): array
    {
        $items = is_array($value) ? $value : explode(',', $value);

        return array_values(array_filter(array_map(
            static fn ($item) => strtoupper(trim((string) $item)),
            $items
        )));
    }

    private function applySorting(Builder $query, string $sortBy, string $sortDir): void
    {
        $direction = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

        if ($sortBy === 'priority') {
            $query->orderByRaw(
                "CASE priority WHEN 'ALTA' THEN 3 WHEN 'MEDIA' THEN 2 WHEN 'BAJA' THEN 1 ELSE 0 END {$direction}"
            )->orderByDesc('id');

            return;
        }

        $allowedColumns = ['id', 'created_at', 'updated_at', 'status', 'location'];
        $column = in_array($sortBy, $allowedColumns, true) ? $sortBy : 'created_at';

        $query->orderBy($column, $direction);
        if ($column !== 'id') {
            $query->orderBy('id', $direction);
        }
    }
}
