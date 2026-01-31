<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Invoices;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Invoices\StorePaymentRequest;
use App\Http\Requests\Api\V1\Invoices\UpdatePaymentRequest;
use App\Http\Resources\Api\V1\Invoices\PaymentResource;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Services\Invoices\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoicePaymentController extends Controller
{
    use \App\Traits\ApiResponse;

    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * GET /api/v1/invoices/{invoice}/payments
     */
    public function index(Invoice $invoice)
    {
        $payments = $invoice->payments()->with('user')->orderBy('payment_date', 'desc')->get();

        return $this->successResponse(
            PaymentResource::collection($payments),
            'تم جلب الدفعات بنجاح'
        );
    }

    /**
     * POST /api/v1/invoices/{invoice}/payments
     */
    public function store(StorePaymentRequest $request, Invoice $invoice)
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        $payment = $this->paymentService->addPayment($invoice, $data);

        return $this->createdResponse(
            new PaymentResource($payment->load('user')),
            'تم إضافة الدفعة بنجاح'
        );
    }

    /**
     * PUT /api/v1/invoices/{invoice}/payments/{payment}
     */
    public function update(UpdatePaymentRequest $request, Invoice $invoice, InvoicePayment $payment)
    {
        if ($payment->invoice_id !== $invoice->id) {
            return $this->errorResponse('الدفعة لا تتبع لهذه الفاتورة', 404);
        }

        $payment = $this->paymentService->updatePayment($payment, $request->validated());

        return $this->successResponse(
            new PaymentResource($payment->load('user')),
            'تم تحديث الدفعة بنجاح'
        );
    }

    /**
     * DELETE /api/v1/invoices/{invoice}/payments/{payment}
     */
    public function destroy(Invoice $invoice, InvoicePayment $payment)
    {
        if ($payment->invoice_id !== $invoice->id) {
            return $this->errorResponse('الدفعة لا تتبع لهذه الفاتورة', 404);
        }

        $this->paymentService->deletePayment($payment);

        return $this->deletedResponse('تم حذف الدفعة بنجاح');
    }
}
