<?php

namespace App\Services;

use App\Models\Memory;
use Illuminate\Support\Facades\Log;

class VpsHubSyncService
{
    private string $sshHost = 'root@187.108.197.199';

    private string $sshPort = '6985';

    private string $sshSocket = '/tmp/devorq-hub.sock';

    private string $hubPath = '/var/devorq/hub/memories';

    private string $sshCmd;

    public function __construct()
    {
        $this->sshCmd = "ssh -p {$this->sshPort} -o ControlPath={$this->sshSocket} {$this->sshHost}";
    }

    /**
     * Check if VPS HUB is reachable.
     */
    public function isConnected(): bool
    {
        $output = shell_exec("{$this->sshCmd} 'echo ok' 2>/dev/null");

        return trim($output ?? '') === 'ok';
    }

    /**
     * Sync a memory to VPS HUB (writes JSON file).
     */
    public function syncMemory(Memory $memory): bool
    {
        $status = $memory->validation_status->value;
        $dir = "{$this->hubPath}/{$status}";

        $filename = "{$dir}/{$memory->id}.json";
        $json = $this->memoryToJson($memory);

        $escapedJson = escapeshellarg($json);
        $cmd = "{$this->sshCmd} \"cat > {$filename} << 'DEVORQEOF'\n{$json}\nDEVORQEOF\" 2>/dev/null";

        $result = shell_exec($cmd);

        Log::info("VpsHubSync: synced memory {$memory->id} to {$filename}");

        return true;
    }

    /**
     * Sync all memories of a given status.
     */
    public function syncAllByStatus(string $status): int
    {
        $memories = Memory::where('validation_status', $status)->get();
        $count = 0;

        foreach ($memories as $memory) {
            $this->syncMemory($memory);
            $count++;
        }

        return $count;
    }

    /**
     * Sync ALL memories to VPS HUB.
     */
    public function syncAll(): array
    {
        $statuses = ['pending', 'validated', 'rejected', 'superseded'];
        $results = [];

        foreach ($statuses as $status) {
            $count = $this->syncAllByStatus($status);
            $results[$status] = $count;
        }

        return $results;
    }

    /**
     * Sync metrics to VPS HUB.
     */
    public function syncMetrics(MemoryMetricsService $metrics): bool
    {
        $report = $metrics->getRecurrenceReport();

        $files = [
            'top_recurring' => 'top_recurring.json',
            'by_stack' => 'by_stack.json',
            'by_severity' => 'by_severity.json',
            'by_source' => 'by_source.json',
            'timeline' => 'timeline.json',
            'summary' => 'summary.json',
        ];

        // Summary
        $summary = [
            'total' => $report['by_type'][0]['count'] ?? 0,
            'pending' => $report['pending_count'],
            'generated_at' => now()->toIso8601String(),
        ];

        foreach ($files as $key => $filename) {
            $data = ${$key} ?? [];
            if ($key === 'summary') {
                $data = $summary;
            } else {
                $data = $report[$key] ?? [];
            }

            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $path = "/var/devorq/hub/metrics/{$filename}";

            $escapedJson = escapeshellarg($json);
            shell_exec("{$this->sshCmd} \"cat > {$path} << 'DEVORQEOF'\n{$json}\nDEVORQEOF\" 2>/dev/null");
        }

        Log::info('VpsHubSync: metrics synced');

        return true;
    }

    /**
     * Read memories from VPS HUB by status.
     */
    public function listHubMemories(string $status = 'pending'): array
    {
        $cmd = "{$this->sshCmd} \"find {$this->hubPath}/{$status} -name '*.json' -exec cat {} \; 2>/dev/null\"";
        $output = shell_exec($cmd);

        if (empty($output)) {
            return [];
        }

        $memories = [];
        $files = array_filter(array_map('trim', explode("\n", trim($output))));

        // Each file is a complete JSON - need to parse individually
        // Since JSON files are concatenated, we split by }{
        $jsonStrings = $this->splitJsonObjects($output);

        foreach ($jsonStrings as $jsonString) {
            $jsonString = trim($jsonString);
            if (empty($jsonString)) {
                continue;
            }
            // Ensure it's a valid JSON object
            if (! str_starts_with($jsonString, '{')) {
                $jsonString = '{'.$jsonString;
            }
            if (! str_ends_with($jsonString, '}')) {
                $jsonString = $jsonString.'}';
            }

            $decoded = json_decode($jsonString, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $memories[] = $decoded;
            }
        }

        return $memories;
    }

    /**
     * Split concatenated JSON objects.
     */
    private function splitJsonObjects(string $output): array
    {
        // Simple approach: split by }\n{
        $parts = preg_split('/\}\s*\{/', $output);

        return $parts;
    }

    /**
     * Convert a Memory model to JSON string for VPS HUB.
     */
    private function memoryToJson(Memory $memory): string
    {
        $data = [
            'id' => $memory->id,
            'title' => $memory->title,
            'description' => $memory->description,
            'type' => $memory->type->value,
            'stack' => $memory->stack,
            'scope' => $memory->scope->value,
            'validation_status' => $memory->validation_status->value,
            'severity' => $memory->severity?->value,
            'recurrence_count' => $memory->recurrence_count,
            'official_reference' => $memory->official_reference,
            'external_reference' => $memory->external_reference,
            'source_system' => $memory->source_system?->value,
            'source_project' => $memory->source_project,
            'source_file' => $memory->source_file,
            'original_id' => $memory->original_id,
            'validated_at' => $memory->validated_at?->toIso8601String(),
            'validated_by' => $memory->validated_by,
            'created_at' => $memory->created_at?->toIso8601String(),
            'updated_at' => $memory->updated_at?->toIso8601String(),
        ];

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
