<?php

namespace App\Http\Controllers\Api\V1\Clients;

use App\Http\Controllers\Controller;
use App\Services\Clients\ClientService;
use App\Http\Resources\Api\V1\Clients\ClientResource;
use App\Http\Resources\Api\V1\Clients\ClientCollectionResource;
use App\Http\Resources\Api\V1\Clients\ClientDetailResource;
use App\Http\Resources\Api\V1\Clients\CommentResource;
use App\Http\Requests\Api\V1\Clients\StoreClientRequest;
use App\Http\Requests\Api\V1\Clients\UpdateClientRequest;
use App\Http\Requests\Api\V1\Clients\ChangeStatusRequest;
use App\Http\Requests\Api\V1\Clients\AssignClientRequest;
use App\Http\Requests\Api\V1\Clients\AddCommentRequest;
use App\Http\Requests\Api\V1\Clients\UploadFileRequest;
use App\Http\Requests\Api\V1\Clients\BulkStatusRequest;
use App\Http\Requests\Api\V1\Clients\BulkAssignRequest;
use App\Http\Requests\Api\V1\Clients\BulkDeleteRequest;
use App\Http\Requests\Api\V1\Clients\StoreFilterRequest;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    use \App\Traits\ApiResponse;

    public function __construct(
        protected ClientService $clientService
    ) {}

    /**
     * GET /api/v1/clients
     * قائمة العملاء مع فلترة وبحث وترقيم
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'status_id', 'priority', 'lead_rating', 'assigned_to',
            'source_id', 'region_id', 'city_id', 'tags', 'search',
            'sort_by', 'sort_dir'
        ]);

        $perPage = $request->input('per_page', 15);
        $clients = $this->clientService->getClients($filters, $perPage);

        return $this->paginatedResponse($clients, 'تم جلب العملاء بنجاح');
    }

    /**
     * GET /api/v1/clients/list
     * قائمة مختصرة للعملاء (Dropdown)
     */
    public function list(Request $request)
    {
        $search = $request->input('search');
        $perPage = $request->input('per_page', 15);
        $clients = $this->clientService->getDropdownList($search, $perPage);

        return $this->paginatedResponse($clients, 'تم جلب القائمة بنجاح');
    }

    /**
     * GET /api/v1/clients/{id}
     * تفاصيل عميل
     */
    public function show(int $id)
    {
        $client = $this->clientService->getClientById($id);
        return $this->successResponse(new ClientDetailResource($client));
    }

    /**
     * POST /api/v1/clients
     * إضافة عميل جديد
     */
    public function store(StoreClientRequest $request)
    {
        $client = $this->clientService->createClient($request->validated());
        return $this->createdResponse(new ClientResource($client), 'تم إنشاء العميل بنجاح');
    }

    /**
     * PUT /api/v1/clients/{id}
     * تعديل عميل
     */
    public function update(UpdateClientRequest $request, int $id)
    {
        $client = $this->clientService->updateClient($id, $request->validated());
        return $this->successResponse(new ClientResource($client), 'تم تحديث العميل بنجاح');
    }

    /**
     * DELETE /api/v1/clients/{id}
     * حذف عميل (Soft Delete)
     */
    public function destroy(int $id)
    {
        $this->clientService->deleteClient($id);
        return $this->deletedResponse('تم حذف العميل بنجاح');
    }

    /**
     * POST /api/v1/clients/{id}/restore
     * استعادة عميل محذوف
     */
    public function restore(int $id)
    {
        $this->clientService->restoreClient($id);
        return $this->successResponse(null, 'تم استعادة العميل بنجاح');
    }

    /**
     * PATCH /api/v1/clients/{id}/status
     * تغيير حالة عميل
     */
    public function changeStatus(ChangeStatusRequest $request, int $id)
    {
        $client = $this->clientService->changeStatus(
            $id,
            $request->input('status_id'),
            $request->user()->id
        );
        
        return $this->successResponse(new ClientResource($client), 'تم تغيير الحالة بنجاح');
    }

    /**
     * PATCH /api/v1/clients/{id}/assign
     * إسناد عميل لمستخدم
     */
    public function assign(AssignClientRequest $request, int $id)
    {
        $client = $this->clientService->assignClient(
            $id,
            $request->input('user_id'),
            $request->user()->id
        );
        
        return $this->successResponse(new ClientResource($client), 'تم إسناد العميل بنجاح');
    }

    /**
     * POST /api/v1/clients/bulk/status
     * تغيير حالة مجموعة عملاء
     */
    public function bulkStatus(BulkStatusRequest $request)
    {
        $count = $this->clientService->bulkUpdateStatus(
            $request->input('client_ids'),
            $request->input('status_id')
        );
        
        return $this->successResponse(null, "تم تحديث حالة {$count} عميل بنجاح");
    }

    /**
     * POST /api/v1/clients/bulk/assign
     * إسناد مجموعة عملاء
     */
    public function bulkAssign(BulkAssignRequest $request)
    {
        $count = $this->clientService->bulkAssign(
            $request->input('client_ids'),
            $request->input('user_id')
        );
        
        return $this->successResponse(null, "تم إسناد {$count} عميل بنجاح");
    }

    /**
     * DELETE /api/v1/clients/bulk
     * حذف مجموعة عملاء
     */
    public function bulkDelete(BulkDeleteRequest $request)
    {
        $count = $this->clientService->bulkDelete($request->input('client_ids'));
        
        return $this->successResponse(null, "تم حذف {$count} عميل بنجاح");
    }

    /**
     * GET /api/v1/clients/{id}/comments
     * جلب تعليقات العميل
     */
    public function getComments(Request $request, int $id)
    {
        $perPage = $request->input('per_page', 10);
        $comments = $this->clientService->getComments($id, $perPage);
        return $this->paginatedResponse($comments, 'تم جلب التعليقات بنجاح');
    }

    /**
     * POST /api/v1/clients/{id}/comments
     * إضافة تعليق
     */
    public function addComment(AddCommentRequest $request, int $id)
    {
        $comment = $this->clientService->addComment(
            $id,
            $request->validated(),
            $request->user()->id
        );
        
        return $this->createdResponse(new CommentResource($comment), 'تم إضافة التعليق بنجاح');
    }

    /**
     * POST /api/v1/clients/{id}/files
     * رفع ملف
     */
    public function uploadFile(UploadFileRequest $request, int $id)
    {
        $file = $this->clientService->uploadFile(
            $id,
            $request->file('file'),
            $request->input('type'),
            $request->user()->id
        );
        
        return $this->createdResponse($file, 'تم رفع الملف بنجاح');
    }

    /**
     * GET /api/v1/clients/{id}/files
     * جلب ملفات العميل
     */
    public function getFiles(int $id)
    {
        $files = $this->clientService->getFiles($id);
        return $this->successResponse($files);
    }

    public function getTimeline(int $id)
    {
        $timeline = $this->clientService->getTimeline($id);
        return $this->successResponse($timeline);
    }

    /**
     * GET /api/v1/clients/{id}/invoices
     * جلب فواتير العميل
     */
    public function getInvoices(Request $request, int $id)
    {
        $perPage = $request->input('per_page', 10);
        $invoices = $this->clientService->getClientInvoices($id, $perPage);
        return $this->paginatedResponse($invoices, 'تم جلب فواتير العميل بنجاح');
    }

    /**
     * GET /api/v1/clients/{id}/appointments
     * جلب مواعيد العميل
     */
    public function getAppointments(Request $request, int $id)
    {
        $perPage = $request->input('per_page', 10);
        $appointments = $this->clientService->getClientAppointments($id, $perPage);
        return $this->paginatedResponse($appointments, 'تم جلب مواعيد العميل بنجاح');
    }

    /**
     * GET /api/v1/clients/stats
     * إحصائيات العملاء
     */
    public function stats(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to']);
        $stats = $this->clientService->getStats($filters);
        return $this->successResponse($stats);
    }

    /**
     * GET /api/v1/clients/kpis
     * مؤشرات الأداء
     */
    public function kpis(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to']);
        $kpis = $this->clientService->getKpis($filters);
        return $this->successResponse($kpis);
    }

    // Filters
    public function getFilters(Request $request)
    {
        $filters = $this->clientService->getSavedFilters($request->user()->id);
        return $this->successResponse($filters);
    }

    public function storeFilter(StoreFilterRequest $request)
    {
        $filter = $this->clientService->saveFilter($request->validated(), $request->user()->id);
        return $this->createdResponse($filter, 'تم حفظ الفلتر بنجاح');
    }

    public function deleteFilter(int $id, Request $request)
    {
        $this->clientService->deleteSavedFilter($id, $request->user()->id);
        return $this->deletedResponse('تم حذف الفلتر بنجاح');
    }

    // Export & PDF
    public function export(Request $request)
    {
        $filters = $request->except(['page', 'per_page']); // Export ignores pagination mostly
        return $this->clientService->exportClients($filters);
    }

    public function downloadPdf(int $id)
    {
        return $this->clientService->generateClientPdf($id);
    }

    // ===================== PROCEDURES (TASKS) =====================

    /**
     * GET /api/v1/clients/{id}/procedures
     * جلب إجراءات العمل للعميل
     */
    public function getProcedures(int $id)
    {
        $procedures = $this->clientService->getProcedures($id);
        return $this->successResponse($procedures, 'تم جلب الإجراءات بنجاح');
    }

    /**
     * POST /api/v1/clients/{id}/procedures
     * إضافة إجراء جديد
     */
    public function addProcedure(Request $request, int $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:pending,completed,cancelled',
            'due_date' => 'nullable|date',
        ]);

        $procedure = $this->clientService->createProcedure($id, $validated);
        return $this->createdResponse($procedure, 'تمت إضافة الإجراء بنجاح');
    }

    /**
     * PUT /api/v1/clients/{clientId}/procedures/{procedureId}
     * تعديل إجراء
     */
    public function updateProcedure(Request $request, int $clientId, int $procedureId)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:pending,completed,cancelled',
            'due_date' => 'nullable|date',
        ]);

        $procedure = $this->clientService->updateProcedure($procedureId, $validated, $request->user()->id);
        return $this->successResponse($procedure, 'تم تحديث الإجراء بنجاح');
    }

    /**
     * DELETE /api/v1/clients/{clientId}/procedures/{procedureId}
     * حذف إجراء
     */
    public function deleteProcedure(int $clientId, int $procedureId)
    {
        $this->clientService->deleteProcedure($procedureId);
        return $this->deletedResponse('تم حذف الإجراء بنجاح');
    }
}
