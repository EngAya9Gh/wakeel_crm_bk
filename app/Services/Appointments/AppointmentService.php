<?php

declare(strict_types=1);

namespace App\Services\Appointments;

use App\Models\Appointment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AppointmentService
{
    public function getAppointments(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Appointment::query()
            ->with(['client:id,name,phone', 'user:id,name']);

        // Apply Filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('start_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $dateTo = $filters['date_to'];
            // If it's just a date (Y-m-d), include the entire day
            if (strlen($dateTo) === 10) {
                $dateTo .= ' 23:59:59';
            }
            $query->where('start_at', '<=', $dateTo);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'start_at';
        $sortDir = $filters['sort_dir'] ?? 'asc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    public function getAppointmentById(int $id): Appointment
    {
        return Appointment::with(['client', 'user'])->findOrFail($id);
    }

    public function createAppointment(array $data, int $userId): Appointment
    {
        return DB::transaction(function () use ($data, $userId) {
            $data['user_id'] = $userId;
            $data['status'] = $data['status'] ?? 'scheduled';
            
            return Appointment::create($data)->load(['client', 'user']);
        });
    }

    public function updateAppointment(Appointment $appointment, array $data): Appointment
    {
        return DB::transaction(function () use ($appointment, $data) {
            $appointment->update($data);
            return $appointment->load(['client', 'user']);
        });
    }

    public function deleteAppointment(Appointment $appointment): bool
    {
        return $appointment->delete();
    }

    public function changeStatus(Appointment $appointment, string $status): Appointment
    {
        $appointment->update(['status' => $status]);
        return $appointment->load(['client', 'user']);
    }

    public function getUpcomingAppointments(int $userId, int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return Appointment::where('user_id', $userId)
            ->where('status', 'scheduled')
            ->whereBetween('start_at', [now(), now()->addDays($days)])
            ->with(['client:id,name,phone'])
            ->orderBy('start_at')
            ->get();
    }
}
