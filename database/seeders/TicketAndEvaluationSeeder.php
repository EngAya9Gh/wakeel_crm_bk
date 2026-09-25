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
        $clientId = 33; // Target client ID as requested
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

        // 3. Seed Tickets for Client 33
        $ticket1 = Ticket::updateOrCreate(
            ['tenant_id' => $tenantId, 'client_id' => $clientId, 'ticket_number' => 'TCK-1001'],
            [
                'user_id' => $userId,
                'assigned_to' => $userId,
                'category_id' => $technicalCat->id,
                'title' => 'مشكلة في تسجيل الدخول للمنصة',
                'description' => 'العميل يواجه مشكلة ولا يستطيع الدخول لحسابه منذ يومين.',
                'status' => 'resolved',
                'priority' => 'high',
                'source' => 'whatsapp',
                'sla_due_at' => now()->addHours(24),
                'resolved_at' => now()->subHours(2),
            ]
        );

        // Add Messages to Ticket 1
        TicketMessage::firstOrCreate(
            ['ticket_id' => $ticket1->id, 'content' => 'الرجاء تزويدي برقم الهوية للتحقق'],
            [
                'user_id' => $userId, 
                'sender_name' => 'محمد الإداري',
                'is_internal' => false
            ]
        );

        TicketMessage::firstOrCreate(
            ['ticket_id' => $ticket1->id, 'content' => 'تم الحل وتحديث كلمة المرور'],
            [
                'user_id' => $userId, 
                'sender_name' => 'محمد الإداري',
                'is_internal' => true // Internal note
            ]
        );

        $ticket2 = Ticket::updateOrCreate(
            ['tenant_id' => $tenantId, 'client_id' => $clientId, 'ticket_number' => 'TCK-1002'],
            [
                'user_id' => $userId,
                'assigned_to' => $userId,
                'category_id' => TicketCategory::where('name', 'مبيعات')->first()->id,
                'title' => 'استفسار عن باقات الأسعار',
                'description' => 'العميل يسأل عن أسعار الباقة السنوية.',
                'status' => 'open',
                'priority' => 'medium',
                'source' => 'manual',
                'sla_due_at' => now()->addHours(12),
            ]
        );

        // 4. Seed Evaluations for Client 33
        
        // Evaluation tied to Ticket 1
        Evaluation::updateOrCreate(
            ['tenant_id' => $tenantId, 'client_id' => $clientId, 'ticket_id' => $ticket1->id],
            [
                'user_id' => $userId,
                'assigned_user_id' => $userId,
                'type_id' => $qualityType->id,
                'rating' => 5,
                'notes' => 'الموظف كان سريعاً جداً في حل المشكلة التقنية',
                'channel' => 'manual'
            ]
        );

        // General Evaluation (Not tied to a ticket)
        Evaluation::updateOrCreate(
            ['tenant_id' => $tenantId, 'client_id' => $clientId, 'type_id' => $speedType->id, 'ticket_id' => null],
            [
                'user_id' => $userId,
                'assigned_user_id' => $userId,
                'rating' => 4,
                'notes' => 'تم الرد على استفساري على الواتساب بشكل سريع ومفيد',
                'channel' => 'whatsapp'
            ]
        );
    }
}
