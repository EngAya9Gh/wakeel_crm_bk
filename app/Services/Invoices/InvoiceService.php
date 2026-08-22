<?php

declare(strict_types=1);

namespace App\Services\Invoices;

use App\Models\Invoice;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InvoiceService
{
    public function __construct(
        protected InvoiceRepositoryInterface $invoiceRepository,
        protected \App\Services\Clients\ClientService $clientService,
        protected \App\Services\Integrations\Contracts\SmsServiceInterface $smsService,
        protected \App\Services\Integrations\Contracts\WhatsAppServiceInterface $whatsAppService,
        protected \App\Services\Integrations\ERP\ErpService $erpService
    ) {}

    public function getInvoices(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->invoiceRepository->paginate($filters, $perPage);
    }

    public function getInvoiceById(int $id): Invoice
    {
        return $this->invoiceRepository->findById($id);
    }

    public function createInvoice(array $data, int $userId): Invoice
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data, $userId) {
            $data['user_id'] = $userId;
            $data['status'] = $data['status'] ?? 'draft';
            
            $invoice = $this->invoiceRepository->create($data);

            // Update Client Status to 'مشترك' (Subscriber)
            $this->updateClientToSubscriber($invoice->client_id, $invoice->id, $userId);

            // Sync with ERP system in the background queue to keep the app fast
            dispatch(new \App\Jobs\PostInvoiceToErp($invoice));

            return $invoice;
        });
    }

    protected function updateClientToSubscriber(int $clientId, int $invoiceId, int $userId): void
    {
        $subscriberStatus = \App\Models\ClientStatus::where('name', 'مشترك')->first();
        
        if ($subscriberStatus) {
            $this->clientService->changeStatus($clientId, $subscriberStatus->id, $userId, [
                 'reason' => 'تم تغيير الحالة تلقائياً بسبب إنشاء فاتورة',
                 'invoice_id' => $invoiceId
            ]);
        }
    }

    public function updateInvoice(Invoice $invoice, array $data): Invoice
    {
        return $this->invoiceRepository->update($invoice, $data);
    }

    public function deleteInvoice(Invoice $invoice): bool
    {
        return $this->invoiceRepository->delete($invoice);
    }

    public function changeStatus(Invoice $invoice, string $status): Invoice
    {
        $data = ['status' => $status];
        
        if ($status === 'paid') {
            $data['paid_at'] = now();
        }
        
        return $this->invoiceRepository->update($invoice, $data);
    }

    public function markAsSent(Invoice $invoice, array $channels = ['whatsapp']): Invoice
    {
        $message = "مرحباً {$invoice->client->name}،\nتم إصدار الفاتورة رقم {$invoice->invoice_number} بقيمة {$invoice->total} ريال.\nيرجى السداد في أقرب وقت. شكراً لثقتكم.";

        if (in_array('whatsapp', $channels) && $invoice->client && $invoice->client->phone) {
            $this->sendViaWhatsApp($invoice->client->phone, $message);
        }

        if (in_array('sms', $channels) && $invoice->client && $invoice->client->phone) {
            $this->smsService->send($invoice->client->phone, $message);
        }

        if ($invoice->status === 'draft') {
            return $this->invoiceRepository->update($invoice, ['status' => 'sent']);
        }

        return $invoice;
    }

    /**
     * Send a WhatsApp message using the thread-based approach.
     *
     * The provider blocks /message/send for outbound-only (Business-initiated) sessions.
     * The reliable path is: find the existing thread for the client → reply via the thread.
     * If no thread exists, the client has never contacted us on WhatsApp, so we throw a
     * descriptive error instead of a cryptic "session not connected" message.
     */
    protected function sendViaWhatsApp(string $phone, string $message): void
    {
        $threadId = $this->whatsAppService->findThreadByPhone($phone);

        if (!$threadId) {
            throw new \Exception(
                "لا يمكن إرسال رسالة واتس آب لهذا العميل ({$phone}). " .
                "يجب أن يبدأ العميل محادثة معنا على الواتس آب أولاً لفتح قناة التواصل."
            );
        }

        $sent = $this->whatsAppService->replyToThread($threadId, $message);

        if (!$sent) {
            throw new \Exception("فشل إرسال رسالة الواتس آب عبر المحادثة الموجودة. يرجى المحاولة مرة أخرى.");
        }
    }

    public function getInvoiceStats(array $filters = []): array
    {
        $query = Invoice::query();
        
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return [
            'total_invoices' => (clone $query)->count(),
            'total_revenue' => (clone $query)->where('status', 'paid')->sum('total'),
            'pending_amount' => (clone $query)->whereIn('status', ['sent', 'overdue'])->sum('total'),
            'by_status' => (clone $query)->selectRaw('status, count(*) as count, sum(total) as total')
                ->groupBy('status')
                ->get(),
        ];
    }
}
