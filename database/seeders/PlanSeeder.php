<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = config('features.plans', []);

        $order = 0;
        foreach ($plans as $slug => $data) {
            \App\Models\Plan::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $data['name'] ?? ucfirst($slug),
                    'modules' => $data['modules'] ?? [],
                    'is_active' => true,
                    'sort_order' => ++$order,
                ]
            );
        }
    }
}
