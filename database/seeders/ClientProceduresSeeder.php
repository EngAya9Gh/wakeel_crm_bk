<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\User;
use App\Models\ClientProcedure;
use Carbon\Carbon;

class ClientProceduresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = Client::all();
        $users = User::all();

        if ($clients->isEmpty()) {
            $this->command->info('No clients found, skipping procedures seeding.');
            return;
        }

        $procedureTemplates = [
            'pending' => [
                ['title' => 'تواصل أولي', 'desc' => 'الاتصال بالعميل لتحديد المتطلبات الأولية'],
                ['title' => 'إرسال عرض سعر', 'desc' => 'تجهيز وإرسال عرض السعر المبدئي بناءً على الاجتماع'],
                ['title' => 'متابعة العقد', 'desc' => 'التواصل مع القسم القانوني لتجهيز مسودة العقد'],
                ['title' => 'عرض تجريبي', 'desc' => 'تحديد موعد لعرض تجريبي للنظام (Demo)'],
            ],
            'completed' => [
                ['title' => 'تسجيل بيانات العميل', 'desc' => 'إضافة كافة بيانات الشركة والمسؤولين'],
                ['title' => 'تحليل المنافسين', 'desc' => 'إعداد تقرير مختصر عن منافسي العميل في السوق'],
                ['title' => 'اجتماع تعارفي', 'desc' => 'تم عقد الاجتماع في مقر الشركة'],
            ],
            'cancelled' => [
                ['title' => 'زيارة ميدانية', 'desc' => 'إلغاء الزيارة بطلب من العميل'],
            ]
        ];

        foreach ($clients as $client) {
            // Add 1-3 pending procedures
            $count = rand(1, 3);
            for ($i = 0; $i < $count; $i++) {
                $template = $procedureTemplates['pending'][array_rand($procedureTemplates['pending'])];
                ClientProcedure::create([
                    'client_id' => $client->id,
                    'title' => $template['title'],
                    'description' => $template['desc'],
                    'status' => 'pending',
                    'due_date' => Carbon::now()->addDays(rand(1, 14)),
                ]);
            }

            // Add 1-2 completed procedures
            $count = rand(1, 2);
            for ($i = 0; $i < $count; $i++) {
                $template = $procedureTemplates['completed'][array_rand($procedureTemplates['completed'])];
                $user = $users->isNotEmpty() ? $users->random() : null;
                
                ClientProcedure::create([
                    'client_id' => $client->id,
                    'title' => $template['title'],
                    'description' => $template['desc'],
                    'status' => 'completed',
                    'due_date' => Carbon::now()->subDays(rand(5, 20)),
                    'completed_at' => Carbon::now()->subDays(rand(1, 5)),
                    'completed_by' => $user?->id,
                ]);
            }
            
             // Occasionally add a cancelled procedure
             if (rand(0, 100) < 20) { // 20% chance
                $template = $procedureTemplates['cancelled'][array_rand($procedureTemplates['cancelled'])];
                ClientProcedure::create([
                    'client_id' => $client->id,
                    'title' => $template['title'],
                    'description' => $template['desc'],
                    'status' => 'cancelled',
                    'due_date' => Carbon::now()->subDays(rand(10, 30)),
                ]);
             }
        }
    }
}
