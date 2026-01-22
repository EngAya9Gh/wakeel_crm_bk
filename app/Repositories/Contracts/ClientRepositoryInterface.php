<?php

namespace App\Repositories\Contracts;

interface ClientRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15);
    public function findById(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function restore(int $id);
    public function bulkUpdateStatus(array $clientIds, int $statusId);
    public function bulkAssign(array $clientIds, int $userId);
    public function bulkDelete(array $clientIds);
    public function getStats(array $filters = []);
    public function getKpis(array $filters = []);
}
