<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Appointment;
use App\Models\InvoiceItem;
use App\Models\ClientTimeline;
use App\Models\Comment;
use App\Models\CommentType;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "🌱 Seeding Demo Data...\n";

        // Get Role IDs (Assuming DatabaseSeeder ran first)
        $salesRole = DB::table('roles')->where('name', 'موظف مبيعات')->value('id');
        $supportRole = DB::table('roles')->where('name', 'موظف دعم')->value('id');
        
        $salesTeam = DB::table('teams')->where('category', 'sales')->value('id');
        $supportTeam = DB::table('teams')->where('category', 'support')->value('id');

        // Ensure Comment Types exist
        $types = [
            ['name' => 'General', 'color' => '#9CA3AF', 'icon' => 'chat'],
            ['name' => 'Call', 'color' => '#3B82F6', 'icon' => 'phone'],
            ['name' => 'Meeting', 'color' => '#8B5CF6', 'icon' => 'users'],
            ['name' => 'Follow up', 'color' => '#F59E0B', 'icon' => 'clock'],
        ];

        foreach ($types as $type) {
            CommentType::firstOrCreate(['name' => $type['name']], $type);
        }
        $commentTypes = CommentType::pluck('id');

        // 1. Create 5 Sales Agents
        echo "creating users...\n";
        $salesAgents = User::factory()->count(5)->create([
            'role_id' => $salesRole,
            'team_id' => $salesTeam,
        ]);

        // 2. Create 3 Support Agents
        $supportAgents = User::factory()->count(3)->create([
            'role_id' => $supportRole,
            'team_id' => $supportTeam,
        ]);

        $allUsers = $salesAgents->merge($supportAgents);

        // 3. Create 50 Clients
        echo "creating clients...\n";
        $clients = Client::factory()->count(50)->make()->each(function ($client) use ($salesAgents) {
            $client->assigned_to = $salesAgents->random()->id;
            // Ensure valid IDs
            $client->status_id = \Illuminate\Support\Facades\DB::table('client_statuses')->inRandomOrder()->value('id') ?? 1;
            $client->source_id = \Illuminate\Support\Facades\DB::table('sources')->inRandomOrder()->value('id') ?? 1;
            $client->behavior_id = \Illuminate\Support\Facades\DB::table('behaviors')->inRandomOrder()->value('id') ?? 1;
            // Ensure city matches region just to be safe, though factory handles it randomly
            $regionId = \App\Models\Region::inRandomOrder()->value('id');
            $cityId = \App\Models\City::where('region_id', $regionId)->inRandomOrder()->value('id');
            
            $client->region_id = $regionId;
            $client->city_id = $cityId;
            $client->save();
            
            // Attach 1-3 random tags
            $tagIds = \Illuminate\Support\Facades\DB::table('tags')->inRandomOrder()->limit(rand(1, 3))->pluck('id')->toArray();
            $client->tags()->attach($tagIds);
        });

        // 4. Create Invoices & Appointments & Timeline & Comments for each Client
        echo "creating transactions (invoices, appointments, timeline, comments)...\n";
        foreach ($clients as $client) {
            // Randomly create Invoices (0 to 3 per client)
            if (rand(0, 10) > 3) {
                Invoice::factory()->count(rand(1, 3))->create([
                    'client_id' => $client->id,
                    'user_id' => $client->assigned_to,
                    'city_id' => $client->city_id,
                ])->each(function ($invoice) {
                    // Create Invoice Items
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'description' => 'Legal Consultation',
                        'quantity' => 1,
                        'unit_price' => $invoice->subtotal,
                        'total' => $invoice->subtotal,
                    ]);
                });
            }

            // Randomly create Appointments (0 to 5 per client)
            if (rand(0, 10) > 2) {
                Appointment::factory()->count(rand(1, 5))->create([
                    'client_id' => $client->id,
                    'user_id' => $client->assigned_to,
                ]);
            }

            // Create Comments
            if ($commentTypes->isNotEmpty()) {
                Comment::factory()->count(rand(1, 5))->create([
                    'client_id' => $client->id,
                    'user_id' => $client->assigned_to,
                    'type_id' => $commentTypes->random(),
                ]);
            }

            // Create Timeline Entries
            ClientTimeline::create([
                'client_id' => $client->id,
                'user_id' => $client->assigned_to,
                'event_type' => 'created',
                'description' => 'Client created via System',
                'created_at' => $client->created_at,
            ]);

            if ($client->first_contact_at) {
                ClientTimeline::create([
                    'client_id' => $client->id,
                    'user_id' => $client->assigned_to,
                    'event_type' => 'contacted',
                    'description' => 'First contact made',
                    'created_at' => $client->first_contact_at,
                ]);
            }
        }

        echo "✅ Demo Data Seeded Successfully!\n";
        
        $this->call([
            InvoicePaymentsSeeder::class,
        ]);
    }
}
