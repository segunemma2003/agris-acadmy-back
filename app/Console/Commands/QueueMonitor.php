<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class QueueMonitor extends Command
{
    protected $signature = 'queue:monitor {--retry-failed : Automatically retry failed jobs}';
    protected $description = 'Monitor queue health and optionally retry failed jobs';

    public function handle()
    {
        $this->info('=== Queue Health Monitor ===');
        $this->newLine();

        // Check pending jobs
        $pending = DB::table('jobs')->count();
        $this->info("Pending Jobs: {$pending}");

        // Check failed jobs
        $failed = DB::table('failed_jobs')->count();
        $this->info("Failed Jobs: {$failed}");

        // Check running workers
        $workers = $this->getRunningWorkers();
        $this->info("Running Workers: {$workers}");

        $this->newLine();

        // Health status
        if ($failed > 100) {
            $this->error("⚠️  WARNING: High number of failed jobs ({$failed})!");
            $this->warn("Consider running: php artisan queue:retry all");
        } elseif ($failed > 0) {
            $this->warn("⚠️  {$failed} failed job(s) detected");
        } else {
            $this->info("✓ No failed jobs");
        }

        if ($workers === 0) {
            $this->error("⚠️  WARNING: No queue workers running!");
            $this->warn("Start workers with: sudo supervisorctl start laravel-worker:*");
        } elseif ($workers < 2) {
            $this->warn("⚠️  Only {$workers} worker(s) running. Consider increasing worker count for better throughput.");
        } else {
            $this->info("✓ {$workers} worker(s) running");
        }

        if ($pending > 1000) {
            $this->error("⚠️  WARNING: High number of pending jobs ({$pending})!");
            $this->warn("Consider increasing worker count or processing capacity");
        } elseif ($pending > 0) {
            $this->info("✓ {$pending} job(s) pending processing");
        } else {
            $this->info("✓ No pending jobs");
        }

        // Auto-retry failed jobs if requested
        if ($this->option('retry-failed') && $failed > 0) {
            $this->newLine();
            $this->info("Retrying failed jobs...");
            Artisan::call('queue:retry', ['id' => 'all']);
            $this->info("✓ Retry command executed");
        }

        return 0;
    }

    private function getRunningWorkers(): int
    {
        // Try to get worker count from supervisor
        $output = shell_exec('sudo supervisorctl status laravel-worker:* 2>/dev/null | grep -c "RUNNING" || echo "0"');
        $count = (int) trim($output);

        // Fallback: check process list
        if ($count === 0) {
            $output = shell_exec('ps aux | grep "queue:work" | grep -v grep | wc -l');
            $count = (int) trim($output);
        }

        return $count;
    }
}




