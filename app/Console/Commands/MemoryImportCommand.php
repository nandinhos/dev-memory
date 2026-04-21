<?php

namespace App\Console\Commands;

use App\Models\Memory;
use App\Services\MemoryNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MemoryImportCommand extends Command
{
    protected $signature = 'memory:import
                            {source : Source to import (all, devorq, troubleshooting, bug_report, handover, e2e_audit, skills)}
                            {--dry-run : Show what would be imported without saving}
                            {--force : Overwrite existing memories with same original_id}';

    protected $description = 'Import memories from various sources into dev-memory-laravel';

    private MemoryNormalizer $normalizer;
    private array $imported = [];
    private array $skipped = [];
    private array $errors = [];

    public function __construct()
    {
        parent::__construct();
        $this->normalizer = new MemoryNormalizer;
    }

    public function handle(): int
    {
        $source = $this->argument('source');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info("=== Memory Import ===");
        $this->info("Source: {$source}");
        $this->info("Dry run: " . ($dryRun ? 'YES' : 'NO'));
        $this->line("");

        $memories = match ($source) {
            'all' => $this->importAll(),
            'devorq' => $this->importDevorqLessons(),
            'troubleshooting' => $this->importTroubleshooting(),
            'bug_report' => $this->importBugReports(),
            'handover' => $this->importHandover(),
            'e2e_audit' => $this->importE2EAudit(),
            'skills' => $this->importSkillDocs(),
            default => [],
        };

        $this->info("Found {$memories->count()} memory entries.");

        foreach ($memories as $raw) {
            $this->processEntry($raw, $dryRun, $force);
        }

        $this->line("");
        $this->info("=== Summary ===");
        $this->info("Imported: " . count($this->imported));
        $this->info("Skipped: " . count($this->skipped));
        $this->info("Errors: " . count($this->errors));

        if (! empty($this->errors)) {
            $this->warn("\nErrors:");
            foreach ($this->errors as $error) {
                $this->warn("  - {$error}");
            }
        }

        if (! empty($this->skipped) && $this->output->isVerbose()) {
            $this->warn("\nSkipped:");
            foreach ($this->skipped as $skip) {
                $this->warn("  - {$skip}");
            }
        }

        return self::SUCCESS;
    }

    private function processEntry(array $raw, bool $dryRun, bool $force): void
    {
        try {
            $data = $this->normalizer->normalize($raw, $raw['_source']);

            if (empty($data->title) || $data->title === 'Untitled Memory') {
                $this->warn("Skipping entry with empty title");
                $this->skipped[] = 'Empty title';
                return;
            }

            // Check for duplicate by original_id
            if (! empty($data->original_id) && ! $force) {
                $exists = Memory::where('original_id', $data->original_id)
                    ->where('source_system', $data->source_system)
                    ->exists();

                if ($exists) {
                    $this->skipped[] = "Duplicate: {$data->title}";
                    return;
                }
            }

            if ($dryRun) {
                $this->line("[DRY RUN] Would import: {$data->title}");
                return;
            }

            $memory = Memory::create($data->toArray());
            $this->imported[] = $memory->id;
            $this->info("Imported: {$memory->title}");

        } catch (\Throwable $e) {
            $this->errors[] = "{$raw['title'] ?? 'Unknown'}: {$e->getMessage()}";
        }
    }

    private function importAll(): \Illuminate\Support\Collection
    {
        return collect()
            ->merge($this->importDevorqLessons())
            ->merge($this->importTroubleshooting())
            ->merge($this->importBugReports())
            ->merge($this->importHandover())
            ->merge($this->importE2EAudit())
            ->merge($this->importSkillDocs());
    }

    private function importDevorqLessons(): \Illuminate\Support\Collection
    {
        $this->info("\n--- DEVORQ Lessons ---");
        $entries = collect();

        // Local devorq lessons
        $localPath = '/projects/devorq_v3/.devorq/state/lessons/applied';
        if (is_dir($localPath)) {
            foreach (glob("{$localPath}/*.json") as $file) {
                $content = json_decode(file_get_contents($file), true);
                if ($content) {
                    $content['_source'] = 'devorq_lessons';
                    $content['_source_file'] = $file;
                    $content['_source_project'] = 'devorq_v3';
                    $entries->push($content);
                    $this->line("Found: " . basename($file));
                }
            }
        }

        return $entries;
    }

    private function importTroubleshooting(): \Illuminate\Support\Collection
    {
        $this->info("\n--- Troubleshooting Docs ---");
        $entries = collect();

        $files = [
            '/projects/devorq_v3/docs/TROUBLESHOOTING.md' => [
                'source' => 'troubleshooting',
                'project' => 'devorq_v3',
                'stack' => 'devorq',
            ],
            '/projects/repo-vedovelli/docs/TROUBLESHOOTING.md' => [
                'source' => 'troubleshooting',
                'project' => 'repo-vedovelli',
                'stack' => 'TypeScript/Vite',
            ],
        ];

        foreach ($files as $path => $meta) {
            if (! file_exists($path)) {
                $this->warn("File not found: {$path}");
                continue;
            }

            $content = file_get_contents($path);
            $parsed = $this->parseTroubleshootingMarkdown($content, $meta);
            foreach ($parsed as $entry) {
                $entries->push($entry);
                $this->line("Found: " . $entry['title']);
            }
        }

        return $entries;
    }

    private function parseTroubleshootingMarkdown(string $content, array $meta): array
    {
        $entries = [];
        $lines = explode("\n", $content);
        $currentProblem = null;
        $currentSolution = null;
        $inProblem = false;

        foreach ($lines as $line) {
            // Detect problem headers
            if (preg_match('/^#{1,3}\s*(.+)/', $line, $matches)) {
                if ($currentProblem && $currentSolution) {
                    $entries[] = [
                        'title' => trim($currentProblem),
                        'description' => trim($currentSolution),
                        'type' => 'best_practice',
                        'stack' => $meta['stack'],
                        '_source' => $meta['source'],
                        '_source_file' => $meta['file'] ?? null,
                        '_source_project' => $meta['project'],
                    ];
                }
                $currentProblem = $matches[1];
                $currentSolution = null;
                $inProblem = true;
            } elseif ($inProblem && (str_starts_with(trim($line), '```') || preg_match('/^(Solução|Solution|Fix):/i', $line))) {
                $currentSolution = trim($line);
            } elseif ($inProblem && $currentSolution) {
                $currentSolution .= "\n" . $line;
            }
        }

        // Last entry
        if ($currentProblem && $currentSolution) {
            $entries[] = [
                'title' => trim($currentProblem),
                'description' => trim($currentSolution),
                'type' => 'best_practice',
                'stack' => $meta['stack'],
                '_source' => $meta['source'],
                '_source_file' => $meta['file'] ?? null,
                '_source_project' => $meta['project'],
            ];
        }

        return $entries;
    }

    private function importBugReports(): \Illuminate\Support\Collection
    {
        $this->info("\n--- Bug Reports ---");
        $entries = collect();

        $files = [
            '/projects/nandogravity/Docs/COREOPS_BUG_REPORT.md' => [
                'source' => 'bug_report',
                'project' => 'nandogravity',
                'stack' => 'Coreops/MCP',
            ],
            '/projects/guest-list-pro/docs/SPEC-WIDGETS-ERRO.md' => [
                'source' => 'bug_report',
                'project' => 'guest-list-pro',
                'stack' => 'Laravel/Filament',
            ],
        ];

        foreach ($files as $path => $meta) {
            if (! file_exists($path)) {
                $this->warn("File not found: {$path}");
                continue;
            }

            $content = file_get_contents($path);
            $parsed = $this->parseBugReportMarkdown($content, $meta);
            foreach ($parsed as $entry) {
                $entries->push($entry);
                $this->line("Found: " . $entry['title']);
            }
        }

        return $entries;
    }

    private function parseBugReportMarkdown(string $content, array $meta): array
    {
        $entries = [];
        $lines = explode("\n", $content);
        $currentBug = null;
        $currentDesc = [];
        $inBug = false;

        foreach ($lines as $line) {
            if (preg_match('/^#{1,3}\s*(Bug|Problema|Error):?\s*(.+)/i', $line, $matches)) {
                if ($currentBug) {
                    $entries[] = [
                        'title' => trim($currentBug),
                        'description' => implode("\n", array_filter($currentDesc)),
                        'type' => 'error',
                        'stack' => $meta['stack'],
                        '_source' => $meta['source'],
                        '_source_file' => $meta['file'] ?? null,
                        '_source_project' => $meta['project'],
                    ];
                }
                $currentBug = $matches[2];
                $currentDesc = [];
                $inBug = true;
            } elseif ($inBug && ! empty(trim($line))) {
                $currentDesc[] = $line;
            }
        }

        if ($currentBug) {
            $entries[] = [
                'title' => trim($currentBug),
                'description' => implode("\n", array_filter($currentDesc)),
                'type' => 'error',
                'stack' => $meta['stack'],
                '_source' => $meta['source'],
                '_source_file' => $meta['file'] ?? null,
                '_source_project' => $meta['project'],
            ];
        }

        return $entries;
    }

    private function importHandover(): \Illuminate\Support\Collection
    {
        $this->info("\n--- Handover Docs ---");
        $entries = collect();

        $files = [
            '/projects/guest-list-pro/HANDOVER.md' => [
                'source' => 'handover',
                'project' => 'guest-list-pro',
                'stack' => 'Laravel/Filament/Livewire',
            ],
            '/projects/eventos-control/docs/HANDOFF-EVENTOS-CONTROL.md' => [
                'source' => 'handover',
                'project' => 'eventos-control',
                'stack' => 'Laravel/Livewire',
            ],
        ];

        foreach ($files as $path => $meta) {
            if (! file_exists($path)) {
                $this->warn("File not found: {$path}");
                continue;
            }

            $content = file_get_contents($path);
            $parsed = $this->parseHandoverMarkdown($content, $meta);
            foreach ($parsed as $entry) {
                $entries->push($entry);
                $this->line("Found: " . $entry['title']);
            }
        }

        return $entries;
    }

    private function parseHandoverMarkdown(string $content, array $meta): array
    {
        $entries = [];

        // Extract specific known issues from handovers
        $patterns = [
            '/(TicketTypePolicy[^.]+not[^\.]+(?:registered|configurada)[^\.]+)/i',
            '/(Observer[^\.]+(?:sendTo|sendToDatabase)[^\.]+)/i',
            '/(404[^.]+(?:TicketType|Audit|PromoterPermission)[^\.]+)/i',
            '/(Modals?[^\.]+(?:não|not)[^\.]+(?:abrem|open|working)[^\.]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[1] as $match) {
                    $title = Str::limit(trim($match), 80);
                    $entries[] = [
                        'title' => $title,
                        'description' => trim($match),
                        'type' => 'error',
                        'stack' => $meta['stack'],
                        '_source' => $meta['source'],
                        '_source_file' => $meta['file'] ?? null,
                        '_source_project' => $meta['project'],
                        'severity' => 'high',
                    ];
                }
            }
        }

        return $entries;
    }

    private function importE2EAudit(): \Illuminate\Support\Collection
    {
        $this->info("\n--- E2E Audit Reports ---");
        $entries = collect();

        $auditPath = '/projects/guest-list-pro/docs/report_e2e';
        if (! is_dir($auditPath)) {
            $this->warn("Audit path not found: {$auditPath}");
            return $entries;
        }

        $meta = [
            'source' => 'e2e_audit',
            'project' => 'guest-list-pro',
            'stack' => 'Laravel/Filament',
        ];

        foreach (glob("{$auditPath}/*.md") as $file) {
            $content = file_get_contents($file);
            $parsed = $this->parseAuditReport($content, array_merge($meta, ['file' => $file]));
            foreach ($parsed as $entry) {
                $entries->push($entry);
                $this->line("Found: " . $entry['title']);
            }
        }

        return $entries;
    }

    private function parseAuditReport(string $content, array $meta): array
    {
        $entries = [];

        // Extract 404s and errors
        if (preg_match_all('/(\/(?:admin|api)[^\s]+)\s*→\s*(404|Not Found|error)/i', $content, $matches)) {
            foreach ($matches[1] as $path) {
                $entries[] = [
                    'title' => "404 Error: {$path}",
                    'description' => "Rota retornou 404 ou erro durante teste E2E",
                    'type' => 'error',
                    'stack' => $meta['stack'],
                    '_source' => $meta['source'],
                    '_source_file' => $meta['file'] ?? null,
                    '_source_project' => $meta['project'],
                    'severity' => 'medium',
                ];
            }
        }

        return $entries;
    }

    private function importSkillDocs(): \Illuminate\Support\Collection
    {
        $this->info("\n--- Skill Docs (VPS HUB) ---");
        $entries = collect();

        // Import from VPS HUB via SSH
        $sshCmd = 'ssh -p 6985 -o ControlPath=/tmp/devorq-hub.sock root@187.108.197.199 "find /var/devorq/hub/lessons -name SKILL.md" 2>/dev/null';

        $output = shell_exec($sshCmd);
        if (! $output) {
            $this->warn("Could not connect to VPS HUB");
            return $entries;
        }

        $files = array_filter(array_map('trim', explode("\n", $output)));

        foreach ($files as $file) {
            $content = shell_exec("ssh -p 6985 -o ControlPath=/tmp/devorq-hub.sock root@187.108.197.199 'cat {$file}' 2>/dev/null");
            if (! $content) {
                continue;
            }

            $parsed = $this->parseSkillMarkdown($content, $file);
            foreach ($parsed as $entry) {
                $entries->push($entry);
                $this->line("Found: " . $entry['title']);
            }
        }

        return $entries;
    }

    private function parseSkillMarkdown(string $content, string $sourceFile): array
    {
        $entries = [];

        // Extract pitfalls and common errors from skills
        $patterns = [
            '/[Pp]itfall[s]?[:]\s*\n((?:-[^\n]+\n?)+)/',
            '/[Cc]ommon\s+[eE]rror[s]?[:]\s*\n((?:-[^\n]+\n?)+)/',
            '/[Ll]essons?[s]?\s+[Ll]earned[:]\s*\n((?:-[^\n]+\n?)+)/',
            '/[Ee]rror[:]\s*\n((?:-[^\n]+\n?)+)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $lines = array_filter(array_map('trim', explode("\n", $matches[1])));
                foreach ($lines as $line) {
                    $line = preg_replace('/^[-*]\s*/, '', $line);
                    if (strlen($line) > 10) {
                        $entries[] = [
                            'title' => Str::limit($line, 80),
                            'description' => $line,
                            'type' => 'lesson',
                            'stack' => $this->inferStackFromSkill($content),
                            '_source' => 'skill_docs',
                            '_source_file' => $sourceFile,
                            '_source_project' => 'vps_hub',
                        ];
                    }
                }
            }
        }

        return $entries;
    }

    private function inferStackFromSkill(string $content): ?string
    {
        $stacks = ['Laravel', 'PHP', 'Filament', 'TypeScript', 'Python', 'Docker', 'Coreops', 'Shell'];
        foreach ($stacks as $stack) {
            if (stripos($content, $stack) !== false) {
                return $stack;
            }
        }
        return 'General';
    }
}
