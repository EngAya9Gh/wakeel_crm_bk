<?php

namespace App\Console\Commands;

use App\Models\ApiLog;
use Illuminate\Console\Command;

class WatchApiLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:watch {--failed : Show only failed requests}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor incoming Public API requests in real-time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📡 Monitoring Public API Logs... (Press Ctrl+C to stop)');
        $this->info('----------------------------------------------------');

        $lastId = ApiLog::max('id') ?? 0;

        while (true) {
            $logs = ApiLog::where('id', '>', $lastId)
                ->when($this->option('failed'), function($query) {
                    return $query->where('success', false);
                })
                ->get();

            foreach ($logs as $log) {
                $status = $log->success ? '<info>[SUCCESS]</info>' : '<error>[FAILED]</error>';
                $time = $log->created_at->format('H:i:s');
                $method = $log->method;
                $code = $log->status_code;
                $name = $log->request_data['name'] ?? 'Unknown';
                
                $this->line("$status $time | $code | $method | Name: $name | Source: {$log->source}");
                
                if (!$log->success) {
                    $this->error("   Error: {$log->error_type} - {$log->error_message}");
                    if ($log->validation_errors) {
                        $this->line("   Validation: " . json_encode($log->validation_errors, JSON_UNESCAPED_UNICODE));
                    }
                }
                
                $lastId = $log->id;
            }

            sleep(2); // Wait 2 seconds before checking again
        }
    }
}
