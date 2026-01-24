<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Source;

class WebsiteFormsSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * This seeder adds the website form sources to the database
     * for proper lead tracking from external website forms.
     */
    public function run(): void
    {
        $sources = [
            [
                'name' => 'نموذج اتصل بنا',
                'is_active' => true,
            ],
            [
                'name' => 'صفحة الهبوط',
                'is_active' => true,
            ],
            [
                'name' => 'نموذج الموقع',
                'is_active' => true,
            ],
        ];

        foreach ($sources as $source) {
            Source::firstOrCreate(
                ['name' => $source['name']],
                $source
            );
        }

        $this->command->info('✅ Website form sources created successfully!');
    }
}
