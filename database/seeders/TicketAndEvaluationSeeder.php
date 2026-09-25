<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TicketCategory;
use App\Models\EvaluationType;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\Evaluation;

class TicketAndEvaluationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenantId = 1; // Assuming default tenant for local demo
        $clientId = 53; // Target client ID as requested
        $userId = 1; // Admin user

        // 1. Seed Categories (Constants/Dynamics)
        $categories = [
            ['name' => 'دعم فني', 'color' => '#3498db', 'sla_hours' => 24, 'is_active' => true],
            ['name' => 'مبيعات', 'color' => '#2ecc71', 'sla_hours' => 12, 'is_active' => true],
            ['name' => 'شكاوى واقتراحات', 'color' => '#e74c3c', 'sla_hours' => 48, 'is_active' => true],
            ['name' => 'شؤون إدارية', 'color' => '#9b59b6', 'sla_hours' => 72, 'is_active' => true],
        ];

        foreach ($categories as $cat) {
            TicketCategory::updateOrCreate(
                ['tenant_id' => $tenantId, 'name' => $cat['name']],
                $cat
            );
        }

        $technicalCat = TicketCategory::where('name', 'دعم فني')->first();
        $salesCat = TicketCategory::where('name', 'مبيعات')->first();
        
        // Add a sub-category
        TicketCategory::updateOrCreate(
            ['tenant_id' => $tenantId, 'name' => 'مشكلة في النظام'],
            ['parent_id' => $technicalCat->id, 'color' => '#2980b9', 'sla_hours' => 12, 'is_active' => true]
        );

        // 2. Seed Evaluation Types (Constants/Dynamics)
        $evalTypes = [
            ['name' => 'تقييم جودة الخدمة', 'is_active' => true],
            ['name' => 'تقييم سرعة الرد', 'is_active' => true],
            ['name' => 'تقييم أداء الموظف', 'is_active' => true],
        ];

        foreach ($evalTypes as $type) {
            EvaluationType::updateOrCreate(
                ['tenant_id' => $tenantId, 'name' => $type['name']],
                $type
            );
        }

        $qualityType = EvaluationType::where('name', 'تقييم جودة الخدمة')->first();
        $speedType = EvaluationType::where('name', 'تقييم سرعة الرد')->first();
        $performanceType = EvaluationType::where('name', 'تقييم أداء الموظف')->first();

        // 3. Seed Tickets for Client 33 (All Sources)
        // manual (يدوي), whatsapp (واتساب), email (إيميل), phone (هاتف), chat_widget (موقع).
        
        $ticketsData = [
            ['num' => 'TCK-1001', 'source' => 'whatsapp', 'title' => 'مشكلة في تسجيل الدخول (واتساب)', 'cat' => $technicalCat->id],
            ['num' => 'TCK-1002', 'source' => 'manual', 'title' => 'استفسار عن باقات الأسعار (يدوي)', 'cat' => $salesCat->id],
            ['num' => 'TCK-1003', 'source' => 'email', 'title' => 'طلب فاتورة ضريبية (إيميل)', 'cat' => $salesCat->id],
            ['num' => 'TCK-1004', 'source' => 'phone', 'title' => 'شكوى تأخير الخدمة (هاتف)', 'cat' => TicketCategory::where('name', 'شكاوى واقتراحات')->first()->id],
            ['num' => 'TCK-1005', 'source' => 'chat_widget', 'title' => 'سؤال من الموقع (شات)', 'cat' => $salesCat->id],
        ];

        foreach ($ticketsData as $idx => $tData) {
            Ticket::updateOrCreate(
                ['tenant_id' => $tenantId, 'client_id' => $clientId, 'ticket_number' => $tData['num']],
                [
                    'user_id' => $userId,
                    'assigned_to' => $userId,
                    'category_id' => $tData['cat'],
                    'title' => $tData['title'],
                    'description' => 'هذه التذكرة جاءت عبر المصدر: ' . $tData['source'],
                    'status' => $idx % 2 == 0 ? 'resolved' : 'open',
                    'priority' => 'medium',
                    'source' => $tData['source'],
                    'sla_due_at' => now()->addHours(24),
                    'resolved_at' => $idx % 2 == 0 ? now()->subHours(1) : null,
                ]
            );
        }

        // 4. Seed Evaluations for Client 33 (All Channels)
        // manual (إدخال يدوي), whatsapp (عبر رابط واتساب), sms (عبر رسالة قصيرة).
        
        $evaluationsData = [
            ['type' => $qualityType->id, 'channel' => 'manual', 'rating' => 5, 'note' => 'تم التقييم يدوياً من قبل الموظف عبر الهاتف'],
            ['type' => $speedType->id, 'channel' => 'whatsapp', 'rating' => 4, 'note' => 'تقييم العميل عبر رابط الواتساب'],
            ['type' => $performanceType->id, 'channel' => 'sms', 'rating' => 3, 'note' => 'تقييم العميل عبر رابط الـ SMS'],
        ];

        foreach ($evaluationsData as $eData) {
            Evaluation::updateOrCreate(
                ['tenant_id' => $tenantId, 'client_id' => $clientId, 'type_id' => $eData['type'], 'channel' => $eData['channel']],
                [
                    'user_id' => $userId,
                    'assigned_user_id' => $userId,
                    'rating' => $eData['rating'],
                    'notes' => $eData['note'],
                ]
            );
        }
    }
}
