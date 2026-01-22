<?php

namespace App\Repositories\Eloquent;

use App\Models\Client;
use App\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Support\Facades\DB;

class EloquentClientRepository implements ClientRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15)
    {
        $query = Client::query()
            ->with(['status', 'assignedTo', 'tags', 'city', 'region'])
            ->withCount(['comments', 'files', 'invoices', 'appointments']);

        // Apply Filters
        if (!empty($filters['status_id'])) {
            $query->where('status_id', $filters['status_id']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['lead_rating'])) {
            $query->where('lead_rating', $filters['lead_rating']);
        }

        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (!empty($filters['source_id'])) {
            $query->where('source_id', $filters['source_id']);
        }

        if (!empty($filters['region_id'])) {
            $query->where('region_id', $filters['region_id']);
        }

        if (!empty($filters['city_id'])) {
            $query->where('city_id', $filters['city_id']);
        }

        if (!empty($filters['tags'])) {
            $query->whereHas('tags', function ($q) use ($filters) {
                $q->whereIn('tags.id', (array)$filters['tags']);
            });
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('email', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('phone', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('company', 'like', '%' . $filters['search'] . '%');
            });
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);
        
        // Secondary sorting for retention of order if timestamps match
        if ($sortBy !== 'id') {
            $query->orderBy('id', 'desc');
        }

        return $query->paginate($perPage);
    }

    public function findById(int $id)
    {
        return Client::with([
            'status', 'source', 'behavior', 'invalidReason',
            'region', 'city', 'assignedTo', 'tags'
        ])
        ->withCount(['comments', 'files', 'invoices', 'appointments'])
        ->findOrFail($id);
    }

    public function create(array $data)
    {
        DB::beginTransaction();
        try {
            $tags = $data['tags'] ?? [];
            unset($data['tags']);

            $client = Client::create($data);

            if (!empty($tags)) {
                $client->tags()->sync($tags);
            }

            DB::commit();
            return $client->load(['status', 'assignedTo', 'tags', 'city', 'region']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data)
    {
        DB::beginTransaction();
        try {
            $client = Client::findOrFail($id);
            
            $tags = $data['tags'] ?? null;
            unset($data['tags']);

            $client->update($data);

            if ($tags !== null) {
                $client->tags()->sync($tags);
            }

            DB::commit();
            return $client->load(['status', 'assignedTo', 'tags', 'city', 'region']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(int $id)
    {
        $client = Client::findOrFail($id);
        return $client->delete();
    }

    public function restore(int $id)
    {
        $client = Client::withTrashed()->findOrFail($id);
        return $client->restore();
    }

    public function bulkUpdateStatus(array $clientIds, int $statusId)
    {
        return Client::whereIn('id', $clientIds)->update(['status_id' => $statusId]);
    }

    public function bulkAssign(array $clientIds, int $userId)
    {
        return Client::whereIn('id', $clientIds)->update(['assigned_to' => $userId]);
    }

    public function bulkDelete(array $clientIds)
    {
        return Client::whereIn('id', $clientIds)->delete();
    }

    public function getStats(array $filters = [])
    {
        $query = Client::query();

        // Apply date filter if provided
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return [
            'total_clients' => (clone $query)->count(),
            'by_status' => (clone $query)->select('status_id', DB::raw('count(*) as count'))
                ->groupBy('status_id')
                ->with('status:id,name,color')
                ->get()
                ->map(fn($item) => [
                    'status_id' => $item->status_id,
                    'status_name' => $item->status->name ?? 'N/A',
                    'color' => $item->status->color ?? '#000',
                    'count' => $item->count
                ]),
            'by_priority' => (clone $query)->select('priority', DB::raw('count(*) as count'))
                ->groupBy('priority')
                ->get(),
            'by_source' => (clone $query)->select('source_id', DB::raw('count(*) as count'))
                ->groupBy('source_id')
                ->with('source:id,name')
                ->get()
                ->map(fn($item) => [
                    'source_id' => $item->source_id,
                    'source_name' => $item->source->name ?? 'N/A',
                    'count' => $item->count
                ]),
            'invalid_registrations' => (clone $query)->where('source_status', 'invalid')
                ->select('invalid_reason_id', DB::raw('count(*) as count'))
                ->groupBy('invalid_reason_id')
                ->with('invalidReason:id,name')
                ->get()
                ->map(fn($item) => [
                    'reason_id' => $item->invalid_reason_id,
                    'reason_name' => $item->invalidReason->name ?? 'غير محدد',
                    'count' => $item->count
                ]),
            'employees_performance' => (clone $query)->whereNotNull('assigned_to')
                ->select('assigned_to', DB::raw('count(*) as total_assigned'), DB::raw('SUM(CASE WHEN converted_at IS NOT NULL THEN 1 ELSE 0 END) as converted_count'))
                ->groupBy('assigned_to')
                ->with('assignedTo:id,name')
                ->get()
                ->map(fn($item) => [
                    'user_id' => $item->assigned_to,
                    'user_name' => $item->assignedTo->name ?? 'N/A',
                    'total_assigned' => $item->total_assigned,
                    'converted_count' => (int)$item->converted_count,
                    'conversion_rate' => $item->total_assigned > 0 ? round(($item->converted_count / $item->total_assigned) * 100, 1) : 0,
                ]),
        ];
    }

    public function getKpis(array $filters = [])
    {
        $query = Client::query();

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $total = (clone $query)->count();
        $converted = (clone $query)->whereNotNull('converted_at')->count();
        $hot_leads = (clone $query)->where('lead_rating', 'hot')->count();
        $avg_conversion_days = (clone $query)
            ->whereNotNull('converted_at')
            ->selectRaw('AVG(DATEDIFF(converted_at, first_contact_at)) as avg_days')
            ->value('avg_days');

        $avg_response_time = (clone $query)
            ->join('comments', 'clients.id', '=', 'comments.client_id')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, clients.created_at, comments.created_at)) as avg_hours')
            ->whereRaw('comments.id = (SELECT MIN(id) FROM comments WHERE client_id = clients.id)')
            ->value('avg_hours');

        $loss_rate = $total > 0 ? round(((clone $query)->where('status_id', 4)->count() / $total) * 100, 2) : 0; // Assuming 4 is 'lost' status ID, adjust as needed or use a scope

        return [
            'total_clients' => $total,
            'converted_clients' => $converted,
            'conversion_rate' => $total > 0 ? round(($converted / $total) * 100, 2) : 0,
            'avg_response_time' => round((float)$avg_response_time, 1),
            'loss_rate' => $loss_rate,
            'hot_leads' => $hot_leads,
            'avg_conversion_days' => round((float)$avg_conversion_days, 1),
        ];
    }
}
