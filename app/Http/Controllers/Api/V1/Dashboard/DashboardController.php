<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\Comment;

class DashboardController extends Controller
{
    use \App\Traits\ApiResponse;

    /**
     * GET /api/v1/dashboard/summary
     * ملخص عام للوحة التحكم
     */
    public function summary(Request $request)
    {
        $userId = $request->user()->id;
        
        // إحصائيات العملاء
        $clientsStats = [
            'total' => Client::count(),
            'this_month' => Client::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'by_status' => Client::select('status_id', DB::raw('count(*) as count'))
                ->groupBy('status_id')
                ->with('status:id,name,color')
                ->get()
                ->map(fn($item) => [
                    'status' => $item->status?->name ?? 'N/A',
                    'color' => $item->status?->color ?? '#000',
                    'count' => $item->count,
                ]),
        ];

        // إحصائيات الفواتير
        $invoicesStats = [
            'total' => Invoice::count(),
            'total_revenue' => Invoice::where('status', 'paid')->sum('total'),
            'pending' => Invoice::whereIn('status', ['sent', 'overdue'])->sum('total'),
            'this_month' => Invoice::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total'),
        ];

        // إحصائيات المواعيد
        $appointmentsStats = [
            'total' => Appointment::count(),
            'upcoming' => Appointment::where('status', 'scheduled')
                ->where('start_at', '>=', now())
                ->count(),
            'today' => Appointment::whereDate('start_at', today())->count(),
        ];

        return $this->successResponse([
            'clients' => $clientsStats,
            'invoices' => $invoicesStats,
            'appointments' => $appointmentsStats,
        ]);
    }

    /**
     * GET /api/v1/dashboard/charts
     * بيانات الرسوم البيانية
     */
    public function charts(Request $request)
    {
        $period = $request->input('period', 'month'); // week, month, year
        
        // عدد العملاء الجدد حسب الفترة
        $clientsChart = $this->getClientsChartData($period);
        
        // إيرادات الفواتير حسب الفترة
        $revenueChart = $this->getRevenueChartData($period);
        
        // توزيع العملاء حسب المصدر
        $sourceDistribution = Client::select('source_id', DB::raw('count(*) as count'))
            ->groupBy('source_id')
            ->with('source:id,name')
            ->get()
            ->map(fn($item) => [
                'source' => $item->source?->name ?? 'غير محدد',
                'count' => $item->count,
            ]);

        return $this->successResponse([
            'clients_trend' => $clientsChart,
            'revenue_trend' => $revenueChart,
            'source_distribution' => $sourceDistribution,
        ]);
    }


    /**
     * GET /api/v1/dashboard/recent-activities
     * آخر النشاطات
     */
    public function recentActivities(Request $request)
    {
        $limit = $request->input('limit', 10);

        // آخر العملاء المضافين
        $recentClients = Client::with('status:id,name,color')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'name', 'status_id', 'created_at'])
            ->map(fn($client) => [
                'type' => 'client_created',
                'message' => "عميل جديد: {$client->name}",
                'link_id' => $client->id,
                'status' => $client->status?->name,
                'color' => $client->status?->color,
                'created_at' => $client->created_at->diffForHumans(),
            ]);

        // آخر الفواتير
        $recentInvoices = Invoice::with('client:id,name')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'client_id', 'invoice_number', 'total', 'status', 'created_at'])
            ->map(fn($invoice) => [
                'type' => 'invoice_created',
                'message' => "فاتورة {$invoice->invoice_number} - {$invoice->client?->name}",
                'link_id' => $invoice->id,
                'total' => number_format((float) $invoice->total, 2),
                'status' => $invoice->status,
                'created_at' => $invoice->created_at->diffForHumans(),
            ]);

        // آخر المواعيد (القادمة)
        $upcomingAppointments = Appointment::with('client:id,name')
            ->where('status', 'scheduled')
            ->where('start_at', '>=', now())
            ->orderBy('start_at')
            ->limit(5)
            ->get(['id', 'client_id', 'title', 'start_at'])
            ->map(fn($appointment) => [
                'type' => 'upcoming_appointment',
                'message' => "{$appointment->title} - {$appointment->client?->name}",
                'link_id' => $appointment->id,
                'start_at' => $appointment->start_at->format('Y-m-d H:i'),
                'time_until' => $appointment->start_at->diffForHumans(),
            ]);

        // آخر التعليقات/المتابعات
        $recentComments = Comment::with(['client:id,name', 'user:id,name', 'type:id,name,color'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($comment) => [
                'type' => 'comment_added',
                'message' => "{$comment->user?->name} علق على {$comment->client?->name}",
                'link_id' => $comment->client_id, // usually link to client
                'content' => \Illuminate\Support\Str::limit($comment->content, 50),
                'comment_type' => $comment->type?->name,
                'color' => $comment->type?->color,
                'created_at' => $comment->created_at->diffForHumans(),
            ]);

        return $this->successResponse([
            'recent_clients' => $recentClients,
            'recent_invoices' => $recentInvoices,
            'upcoming_appointments' => $upcomingAppointments,
            'recent_comments' => $recentComments,
        ]);
    }

    // ===================== PRIVATE HELPERS =====================

    private function getClientsChartData(string $period): array
    {
        $data = [];
        
        if ($period === 'week') {
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $data[] = [
                    'label' => $date->format('D'),
                    'count' => Client::whereDate('created_at', $date)->count(),
                ];
            }
        } elseif ($period === 'month') {
            for ($i = 29; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $data[] = [
                    'label' => $date->format('d'),
                    'count' => Client::whereDate('created_at', $date)->count(),
                ];
            }
        } else { // year
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $data[] = [
                    'label' => $date->format('M'),
                    'count' => Client::whereMonth('created_at', $date->month)
                        ->whereYear('created_at', $date->year)
                        ->count(),
                ];
            }
        }
        
        return $data;
    }

    private function getRevenueChartData(string $period): array
    {
        $data = [];
        
        if ($period === 'week') {
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $data[] = [
                    'label' => $date->format('D'),
                    'total' => Invoice::where('status', 'paid')
                        ->whereDate('paid_at', $date)
                        ->sum('total'),
                ];
            }
        } elseif ($period === 'month') {
            for ($i = 29; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $data[] = [
                    'label' => $date->format('d'),
                    'total' => Invoice::where('status', 'paid')
                        ->whereDate('paid_at', $date)
                        ->sum('total'),
                ];
            }
        } else { // year
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $data[] = [
                    'label' => $date->format('M'),
                    'total' => Invoice::where('status', 'paid')
                        ->whereMonth('paid_at', $date->month)
                        ->whereYear('paid_at', $date->year)
                        ->sum('total'),
                ];
            }
        }
        
        return $data;
    }
}
