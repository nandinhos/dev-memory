<?php

namespace App\Console\Commands;

use App\Services\MemoryMetricsService;
use App\Services\VpsHubSyncService;
use Illuminate\Console\Command;

class MemorySyncCommand extends Command
{
    protected $signature = 'memory:sync
                            {action : pull|push|sync-all|status|metrics}
                            {--status=pending : Status filter (pending, validated, rejected)}';

    protected $description = 'Sync memories with VPS HUB (srv163217)';

    public function handle(): int
    {
        $action = $this->argument('action');
        $status = $this->option('status');

        $this->info("=== Memory Sync ===");

        $sync = new VpsHubSyncService;

        // Check connection
        if (! $sync->isConnected()) {
            $this->error("Cannot connect to VPS HUB (root@187.108.197.199:6985)");
            $this->line("Make sure SSH socket is available at /tmp/devorq-hub.sock");
            return self::FAILURE;
        }

        $this->info("Connected to VPS HUB ✓");

        return match ($action) {
            'pull' => $this->pull($sync, $status),
            'push' => $this->push($sync, $status),
            'sync-all' => $this->syncAll($sync),
            'status' => $this->status($sync),
            'metrics' => $this->metrics($sync),
            default => $this->unknownAction($action),
        };
    }

    private function pull(VpsHubSyncService $sync, string $status): int
    {
        $this->info("Pulling memories from VPS HUB ({$status})...");

        $memories = $sync->listHubMemories($status);
        $count = count($memories);

        $this->info("Found {$count} memories in VPS HUB ({$status})");

        if ($count === 0) {
            $this->line("Nothing to pull.");
            return self::SUCCESS;
        }

        // For now, just list them. Full import into local DB would be done
        // by a separate import command that reads from files.
        foreach (array_slice($memories, 0, 10) as $memory) {
            $this->line("  - [{$memory['id']}] {$memory['title']}");
        }

        if ($count > 10) {
            $this->line("  ... and " . ($count - 10) . " more");
        }

        $this->warn("Pull imports memories FROM VPS HUB to local DB — not yet implemented.");
        $this->line("Use 'memory:sync push' to push local memories to VPS HUB.");

        return self::SUCCESS;
    }

    private function push(VpsHubSyncService $sync, string $status): int
    {
        $this->info("Pushing local memories to VPS HUB ({$status})...");

        $count = $sync->syncAllByStatus($status);

        $this->info("Pushed {$count} memories to VPS HUB ({$status})");

        return self::SUCCESS;
    }

    private function syncAll(VpsHubSyncService $sync): int
    {
        $this->info("Syncing ALL local memories to VPS HUB...");

        $results = $sync->syncAll();

        foreach ($results as $status => $count) {
            $this->line("  {$status}: {$count} memories");
        }

        $total = array_sum($results);
        $this->info("Total: {$total} memories synced to VPS HUB");

        return self::SUCCESS;
    }

    private function status(VpsHubSyncService $sync): int
    {
        $this->info("VPS HUB Memory Status");
        $this->line("");

        $statuses = ['pending', 'validated', 'rejected', 'superseded'];
        $hubPath = '/var/devorq/hub/memories';

        foreach ($statuses as $status) {
            $cmd = "ssh -p 6985 -o ControlPath=/tmp/devorq-hub.sock root@187.108.197.199 \"ls {$hubPath}/{$status}/ 2>/dev/null | wc -l\"";
            $count = (int) trim(shell_exec($cmd) ?? '0');

            $label = str_pad($status, 12);
            $bar = str_repeat('█', min($count, 20));
            $this->line("  {$label} {$bar} ({$count})");
        }

        $this->line("");
        $metricsPath = '/var/devorq/hub/metrics';
        $cmd = "ssh -p 6985 -o ControlPath=/tmp/devorq-hub.sock root@187.108.197.199 \"ls {$metricsPath}/ 2>/dev/null\"";
        $metrics = trim(shell_exec($cmd) ?? '');

        if ($metrics) {
            $this->line("Metrics: {$metrics}");
        }

        return self::SUCCESS;
    }

    private function metrics(VpsHubSyncService $sync): int
    {
        $this->info("Syncing metrics to VPS HUB...");

        $metricsService = new MemoryMetricsService;
        $sync->syncMetrics($metricsService);

        $this->info("Metrics synced to /var/devorq/hub/metrics/");

        return self::SUCCESS;
    }

    private function unknownAction(string $action): int
    {
        $this->error("Unknown action: {$action}");
        $this->line("Valid actions: pull, push, sync-all, status, metrics");
        return self::FAILURE;
    }
}
