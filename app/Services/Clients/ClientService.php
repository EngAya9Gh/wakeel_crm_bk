<?php

namespace App\Services\Clients;

use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Models\Comment;
use App\Models\ClientFile;
use App\Models\ClientTimeline;
use App\Models\ClientFilter;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ClientService
{
    public function __construct(
        protected ClientRepositoryInterface $clientRepository
    ) {}

    public function getClients(array $filters, int $perPage = 15)
    {
        return $this->clientRepository->paginate($filters, $perPage);
    }

    public function getClientById(int $id)
    {
        return $this->clientRepository->findById($id);
    }

    public function createClient(array $data)
    {
        $data['first_contact_at'] = now();
        $client = $this->clientRepository->create($data);
        
        // Log timeline
        $this->logTimeline($client->id, 'client_created', 'تم إنشاء العميل');
        
        return $client;
    }

    public function updateClient(int $id, array $data)
    {
        $client = $this->clientRepository->update($id, $data);
        
        $this->logTimeline($id, 'client_updated', 'تم تحديث بيانات العميل');
        
        return $client;
    }

    public function deleteClient(int $id)
    {
        $this->logTimeline($id, 'client_deleted', 'تم حذف العميل');
        return $this->clientRepository->delete($id);
    }

    public function restoreClient(int $id)
    {
        $result = $this->clientRepository->restore($id);
        $this->logTimeline($id, 'client_restored', 'تم استعادة العميل');
        return $result;
    }

    public function changeStatus(int $id, int $statusId, ?int $userId = null, array $metadata = [])
    {
        $client = $this->clientRepository->update($id, ['status_id' => $statusId]);
        
        $meta = array_merge([
            'status_id' => $statusId,
            'changed_by' => $userId
        ], $metadata);

        $this->logTimeline($id, 'status_changed', "تم تغيير الحالة", $meta, $userId);
        
        return $client;
    }

    public function assignClient(int $id, int $userId, ?int $assignedBy = null)
    {
        $client = $this->clientRepository->update($id, ['assigned_to' => $userId]);
        
        $this->logTimeline($id, 'client_assigned', "تم إسناد العميل", [
            'assigned_to' => $userId,
            'assigned_by' => $assignedBy
        ], $assignedBy);
        
        return $client;
    }

    public function bulkUpdateStatus(array $clientIds, int $statusId)
    {
        return $this->clientRepository->bulkUpdateStatus($clientIds, $statusId);
    }

    public function bulkAssign(array $clientIds, int $userId)
    {
        return $this->clientRepository->bulkAssign($clientIds, $userId);
    }

    public function bulkDelete(array $clientIds)
    {
        return $this->clientRepository->bulkDelete($clientIds);
    }

    // Comments
    public function addComment(int $clientId, array $data, int $userId)
    {
        return \DB::transaction(function () use ($clientId, $data, $userId) {
            $comment = Comment::create([
                'client_id' => $clientId,
                'user_id' => $userId,
                'type_id' => $data['type_id'],
                'subject' => $data['subject'] ?? null,
                'content' => $data['content'],
                'outcome' => $data['outcome'] ?? 'neutral',
            ]);

            // Handle Mentions
            if (!empty($data['mentions'])) {
                $comment->mentions()->sync($data['mentions']);
            }

            // Handle Attachments
            if (!empty($data['attachments'])) {
                foreach ($data['attachments'] as $file) {
                    $path = $file->store('client_files/' . $clientId . '/comments', 'public');
                    $comment->attachments()->create([
                        'client_id' => $clientId,
                        'user_id' => $userId,
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'type' => $this->determineFileType($file),
                        'size' => $file->getSize(),
                    ]);
                }
            }

            $this->logTimeline($clientId, 'comment_added', 'تم إضافة تعليق: ' . ($data['subject'] ?? 'بدون عنوان'), null, $userId);

            return $comment->load(['user', 'type', 'mentions', 'attachments']);
        });
    }

    protected function determineFileType($file): string
    {
        $mime = $file->getMimeType();
        if (str_starts_with($mime, 'image/')) return 'image';
        if ($mime === 'application/pdf') return 'document';
        // Add more as needed
        return 'document';
    }

    public function getComments(int $clientId, int $perPage = 10)
    {
        return Comment::where('client_id', $clientId)
            ->with(['user:id,name,avatar', 'type', 'mentions', 'attachments'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    // Files
    public function uploadFile(int $clientId, $file, string $type, int $userId)
    {
        $path = $file->store('client_files/' . $clientId, 'public');
        
        $clientFile = ClientFile::create([
            'client_id' => $clientId,
            'user_id' => $userId,
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'type' => $type,
            'size' => $file->getSize(),
        ]);

        $this->logTimeline($clientId, 'file_uploaded', 'تم رفع ملف: ' . $file->getClientOriginalName(), null, $userId);

        return $clientFile;
    }

    public function deleteFile(int $fileId)
    {
        $file = ClientFile::findOrFail($fileId);
        Storage::disk('public')->delete($file->path);
        return $file->delete();
    }

    public function getFiles(int $clientId)
    {
        return ClientFile::where('client_id', $clientId)
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // Timeline
    public function getTimeline(int $clientId)
    {
        return ClientTimeline::where('client_id', $clientId)
            ->with('user:id,name,avatar')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    protected function logTimeline(int $clientId, string $eventType, string $description, ?array $metadata = null, ?int $userId = null)
    {
        ClientTimeline::create([
            'client_id' => $clientId,
            'user_id' => $userId ?? auth()->id(),
            'event_type' => $eventType,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    // Stats & KPIs
    public function getStats(array $filters = [])
    {
        return $this->clientRepository->getStats($filters);
    }

    public function getKpis(array $filters = [])
    {
        return $this->clientRepository->getKpis($filters);
    }

    // Filters CRUD
    public function getSavedFilters(int $userId)
    {
        return ClientFilter::where('user_id', $userId)->get();
    }

    public function saveFilter(array $data, int $userId)
    {
        return ClientFilter::create([
            'user_id' => $userId,
            'name' => $data['name'],
            'criteria' => $data['criteria'],
        ]);
    }

    public function deleteSavedFilter(int $id, int $userId)
    {
        $filter = ClientFilter::where('id', $id)->where('user_id', $userId)->firstOrFail();
        return $filter->delete();
    }

    // Export & PDF
    public function generateClientPdf(int $clientId)
    {
        // Requires 'barryvdh/laravel-dompdf' package
        $client = $this->getClientById($clientId);
        $pdf = Pdf::loadView('pdf.client_profile', ['client' => $client]);
        return $pdf->download("client_{$client->id}_{$client->name}.pdf");
    }

    public function exportClients(array $filters)
    {
        // Using a simple CSV generation for now to avoid complexity if package fails,
        // or we can use Maatwebsite/Excel if installed successfully.
        // For simplicity and speed without extra classes, let's stream a CSV.
        
        $clients = $this->clientRepository->paginate($filters, 10000); // Get all (limit reasonable)
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="clients_export.csv"',
        ];

        $callback = function () use ($clients) {
            $file = fopen('php://output', 'w');
            // Add BOM for Excel UTF-8 compatibility
            fputs($file, "\xEF\xBB\xBF"); 
            
            fputcsv($file, ['ID', 'Name', 'Phone', 'Email', 'Status', 'City', 'Created At']);

            foreach ($clients as $client) {
                fputcsv($file, [
                    $client->id,
                    $client->name,
                    $client->phone,
                    $client->email,
                    $client->status->name ?? '',
                    $client->city->name ?? '',
                    $client->created_at->format('Y-m-d'),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
