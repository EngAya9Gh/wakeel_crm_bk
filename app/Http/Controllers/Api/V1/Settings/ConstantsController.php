<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ConstantsController extends Controller
{
    use \App\Traits\ApiResponse;

    public function index(): JsonResponse
    {
        return $this->successResponse([
            'ticket_statuses' => [
                ['id' => 'open', 'name_ar' => 'مفتوحة', 'name_en' => 'Open'],
                ['id' => 'in_progress', 'name_ar' => 'قيد المعالجة', 'name_en' => 'In Progress'],
                ['id' => 'resolved', 'name_ar' => 'محلولة', 'name_en' => 'Resolved'],
                ['id' => 'closed', 'name_ar' => 'مغلقة', 'name_en' => 'Closed'],
            ],
            'ticket_priorities' => [
                ['id' => 'low', 'name_ar' => 'منخفضة', 'name_en' => 'Low'],
                ['id' => 'medium', 'name_ar' => 'متوسطة', 'name_en' => 'Medium'],
                ['id' => 'high', 'name_ar' => 'عالية', 'name_en' => 'High'],
                ['id' => 'urgent', 'name_ar' => 'عاجلة', 'name_en' => 'Urgent'],
            ],
            'ticket_sources' => [
                ['id' => 'manual', 'name_ar' => 'يدوي', 'name_en' => 'Manual'],
                ['id' => 'whatsapp', 'name_ar' => 'واتساب', 'name_en' => 'WhatsApp'],
                ['id' => 'email', 'name_ar' => 'بريد إلكتروني', 'name_en' => 'Email'],
                ['id' => 'phone', 'name_ar' => 'إتصال هاتفي', 'name_en' => 'Phone'],
            ],
            'evaluation_channels' => [
                ['id' => 'link', 'name_ar' => 'رابط تقييم', 'name_en' => 'Link'],
                ['id' => 'whatsapp', 'name_ar' => 'واتساب', 'name_en' => 'WhatsApp'],
                ['id' => 'manual', 'name_ar' => 'يدوي', 'name_en' => 'Manual'],
            ],
            'contact_positions' => [
                ['id' => 'owner', 'name_ar' => 'المالك', 'name_en' => 'Owner'],
                ['id' => 'manager', 'name_ar' => 'المدير العام', 'name_en' => 'General Manager'],
                ['id' => 'secretary', 'name_ar' => 'سكرتير/ة', 'name_en' => 'Secretary'],
                ['id' => 'hr', 'name_ar' => 'الموارد البشرية', 'name_en' => 'HR'],
                ['id' => 'it', 'name_ar' => 'تقنية المعلومات', 'name_en' => 'IT'],
                ['id' => 'finance', 'name_ar' => 'المالية', 'name_en' => 'Finance'],
                ['id' => 'sales', 'name_ar' => 'المبيعات', 'name_en' => 'Sales'],
                ['id' => 'other', 'name_ar' => 'أخرى', 'name_en' => 'Other'],
            ]
        ]);
    }
}
