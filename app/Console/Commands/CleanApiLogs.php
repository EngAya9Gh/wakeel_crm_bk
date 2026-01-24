<?php

namespace App\Console\Commands;

use App\Models\ApiLog;
use Illuminate\Console\Command;

class CleanApiLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:log-clean {--days=30 : The number of days of logs to keep}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old API logs from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $date = now()->subDays($days);

        $count = ApiLog::where('created_at', '<', $date)->delete();

        $this->info("🧹 Deleted {$count} API logs older than {$days} days.");
    }
}
