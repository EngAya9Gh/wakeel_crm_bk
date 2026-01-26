<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\User;
use App\Models\Appointment;
use Carbon\Carbon;

class AppointmentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = Client::all();
        $users = User::all();

        if ($clients->isEmpty()) {
            $this->command->info('No clients found, skipping appointments seeding.');
            return;
        }

        if ($users->isEmpty()) {
            $this->command->info('No users found, skipping appointments seeding.');
            return;
        }

        $appointmentTemplates = [
            'meeting' => [
                ['title' => 'اجتماع مناقشة المتطلبات', 'desc' => 'مناقشة تفاصيل المشروع والنطاق'],
                ['title' => 'اجتماع توقيع العقد', 'desc' => 'مراجعة وتوقيع العقود النهائية'],
                ['title' => 'اجتماع المراجعة الشهرية', 'desc' => 'مراجعة الأداء والتقارير الشهرية'],
            ],
            'call' => [
                ['title' => 'مكالمه متابعة', 'desc' => 'متابعة حالة العرض المرسل'],
                ['title' => 'استشارة هاتفية', 'desc' => 'تقديم استشارة سريعة حول الخدمات'],
            ],
            'visit' => [
                ['title' => 'زيارة الموقع', 'desc' => 'زيارة ميدانية لموقع العميل'],
                ['title' => 'تدريب الموظفين', 'desc' => 'دورة تدريبية في مقر العميل'],
            ],
        ];

        $locations = ['مقر الشركة', 'أونلاين (Zoom)', 'مكتب العميل', 'هاتفي'];

        foreach ($clients as $client) {
            // Add 1-4 appointments per client
            $count = rand(1, 4);
            
            for ($i = 0; $i < $count; $i++) {
                $type = array_rand($appointmentTemplates);
                $template = $appointmentTemplates[$type][array_rand($appointmentTemplates[$type])];
                
                // 50% chance of past appointment (completed), 50% future (scheduled)
                $isPast = rand(0, 1) === 1;
                $status = $isPast ? 'completed' : 'scheduled';
                
                $startAt = $isPast 
                    ? Carbon::now()->subDays(rand(1, 60))->setHour(rand(9, 16))->setMinute(0)
                    : Carbon::now()->addDays(rand(1, 30))->setHour(rand(9, 16))->setMinute(0);
                
                $endAt = (clone $startAt)->addHour();

                Appointment::create([
                    'client_id' => $client->id,
                    'user_id' => $users->random()->id,
                    'title' => $template['title'],
                    'description' => $template['desc'],
                    'start_at' => $startAt,
                    'end_at' => $endAt,
                    'location' => $locations[array_rand($locations)],
                    'type' => $type,
                    'status' => $status,
                    'reminder_at' => $startAt->copy()->subMinutes(30),
                ]);
            }
        }
        
        $this->command->info('Appointments seeded successfully!');
    }
}
