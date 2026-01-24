<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Invoices;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Invoices\StoreInvoiceRequest;
use App\Http\Requests\Api\V1\Invoices\UpdateInvoiceRequest;
use App\Http\Requests\Api\V1\Invoices\ChangeInvoiceStatusRequest;
use App\Http\Resources\Api\V1\Invoices\InvoiceResource;
use App\Http\Resources\Api\V1\Invoices\InvoiceCollectionResource;
use App\Models\Invoice;
use App\Services\Invoices\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InvoiceController extends Controller
{
    use \App\Traits\ApiResponse;

    public function __construct(
        protected InvoiceService $invoiceService
    ) {}

    /**
     * GET /api/v1/invoices
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'status', 'client_id', 'user_id', 'search',
            'date_from', 'date_to', 'due_date_from', 'due_date_to',
            'sort_by', 'sort_dir'
        ]);

        $invoices = $this->invoiceService->getInvoices($filters, $request->input('per_page', 15));

        return $this->paginatedResponse($invoices, 'تم جلب الفواتير بنجاح');
    }

    /**
     * POST /api/v1/invoices
     */
    public function store(StoreInvoiceRequest $request)
    {
        $invoice = $this->invoiceService->createInvoice(
            $request->validated(),
            $request->user()->id
        );

        return $this->createdResponse(new InvoiceResource($invoice), 'تم إنشاء الفاتورة بنجاح');
    }

    /**
     * GET /api/v1/invoices/{invoice}
     */
    public function show(Invoice $invoice)
    {
        return $this->successResponse(new InvoiceResource($invoice->load(['client', 'user', 'items.product', 'tags'])));
    }

    /**
     * PUT /api/v1/invoices/{invoice}
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        $updatedInvoice = $this->invoiceService->updateInvoice($invoice, $request->validated());

        return $this->successResponse(new InvoiceResource($updatedInvoice), 'تم تحديث الفاتورة بنجاح');
    }

    /**
     * DELETE /api/v1/invoices/{invoice}
     */
    public function destroy(Invoice $invoice)
    {
        $this->invoiceService->deleteInvoice($invoice);

        return $this->deletedResponse('تم حذف الفاتورة بنجاح');
    }

    /**
     * PATCH /api/v1/invoices/{invoice}/status
     */
    public function changeStatus(ChangeInvoiceStatusRequest $request, Invoice $invoice)
    {
        $updatedInvoice = $this->invoiceService->changeStatus(
            $invoice,
            $request->input('status')
        );

        return $this->successResponse(new InvoiceResource($updatedInvoice), 'تم تغيير حالة الفاتورة بنجاح');
    }

    /**
     * GET /api/v1/invoices/{invoice}/pdf
     */
    public function downloadPdf(Invoice $invoice)
    {
        // Require barryvdh/laravel-dompdf
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', ['invoice' => $invoice->load(['client', 'items.product', 'tags'])]);
        return $pdf->download("invoice_{$invoice->invoice_number}.pdf");
    }

    /**
     * POST /api/v1/invoices/{invoice}/send
     */
    public function sendToClient(Request $request, Invoice $invoice)
    {
        $channels = $request->input('channels', ['whatsapp', 'sms']);
        $this->invoiceService->markAsSent($invoice, $channels);

        return $this->successResponse(null, 'تم إرسال الفاتورة للعميل بنجاح');
    }
}
