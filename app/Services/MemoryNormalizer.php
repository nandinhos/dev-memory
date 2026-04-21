<?php

namespace App\Services;

use App\Enums\MemorySource;
use App\Enums\MemoryType;
use App\Enums\Severity;
use App\Enums\ValidationStatus;

class MemoryNormalizer
{
    /**
     * Normalize a raw memory entry into the standard MemoryData format.
     * Handles deduplication by checking for similar existing memories.
     */
    public function normalize(array $raw, string $source): MemoryData
    {
        $type = $this->inferType($raw, $source);
        $severity = $this->inferSeverity($raw, $source);

        return new MemoryData([
            'title' => $this->extractTitle($raw),
            'description' => $this->extractDescription($raw),
            'type' => $type,
            'stack' => $this->inferStack($raw),
            'scope' => $this->inferScope($raw),
            'source_system' => $source,
            'source_file' => $raw['_source_file'] ?? null,
            'source_project' => $raw['_source_project'] ?? null,
            'original_id' => $raw['id'] ?? null,
            'severity' => $severity,
            'recurrence_count' => $raw['recurrence_count'] ?? 1,
            'official_reference' => $raw['official_reference'] ?? null,
            'external_reference' => $raw['external_reference'] ?? null,
            'validation_status' => ValidationStatus::PENDING,
        ]);
    }

    /**
     * Infer memory type from raw data and source.
     */
    public function inferType(array $raw, string $source): MemoryType
    {
        // Check explicit type field
        if (isset($raw['type'])) {
            return match (strtolower($raw['type'])) {
                'error', 'bug', 'issue' => MemoryType::ERROR,
                'lesson', 'learned' => MemoryType::LESSON,
                'best_practice', 'best', 'practice' => MemoryType::BEST_PRACTICE,
                default => MemoryType::LESSON,
            };
        }

        // Infer from source and keywords
        $title = strtolower($raw['title'] ?? '');
        $description = strtolower($raw['description'] ?? '');

        if (
            str_contains($title, 'erro') || str_contains($title, 'error') ||
            str_contains($title, 'bug') || str_contains($title, 'problema') ||
            str_contains($title, 'fix') || str_contains($description, 'erro') ||
            str_contains($description, 'error') || str_contains($description, 'não funciona')
        ) {
            return MemoryType::ERROR;
        }

        if (
            str_contains($title, 'best practice') || str_contains($title, 'recomendação') ||
            str_contains($title, 'solução') || str_contains($description, 'recomend')
        ) {
            return MemoryType::BEST_PRACTICE;
        }

        return MemoryType::LESSON;
    }

    /**
     * Infer severity from raw data and source.
     */
    public function inferSeverity(array $raw, string $source): ?Severity
    {
        // Check explicit severity field
        if (isset($raw['severity'])) {
            return match (strtolower($raw['severity'])) {
                'low', 'baixo' => Severity::LOW,
                'medium', 'médio', 'medio' => Severity::MEDIUM,
                'high', 'alto', 'alta' => Severity::HIGH,
                'critical', 'crítico', 'critico' => Severity::CRITICAL,
                default => null,
            };
        }

        // Infer from source type
        return match ($source) {
            'bug_report' => Severity::HIGH,
            'e2e_audit' => Severity::MEDIUM,
            default => null,
        };
    }

    /**
     * Extract and clean title from raw data.
     */
    public function extractTitle(array $raw): string
    {
        $title = $raw['title'] ?? $raw['name'] ?? $raw['heading'] ?? '';

        // Clean markdown headers
        $title = preg_replace('/^#+\s*/', '', $title);
        $title = preg_replace('/^[-*]\s*/', '', $title);

        // Clean special characters
        $title = trim($title);

        if (empty($title)) {
            return 'Untitled Memory';
        }

        return $title;
    }

    /**
     * Extract and clean description from raw data.
     */
    public function extractDescription(array $raw): string
    {
        $description = $raw['description'] ?? $raw['body'] ?? $raw['content'] ?? '';

        // Clean markdown
        $description = preg_replace('/^#+\s*/m', '', $description);
        $description = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $description);
        $description = preg_replace('/`{1,3}[^`]*`{1,3}/', '', $description);
        $description = trim($description);

        if (empty($description)) {
            return $raw['title'] ?? 'No description available';
        }

        // Truncate if too long (4000 chars max for DB text)
        if (strlen($description) > 4000) {
            $description = substr($description, 0, 3997) . '...';
        }

        return $description;
    }

    /**
     * Infer stack from raw data.
     */
    public function inferStack(array $raw): ?string
    {
        // Check explicit stack field
        if (! empty($raw['stack'])) {
            return $raw['stack'];
        }

        // Check tags
        $tags = $raw['tags'] ?? $raw['stack'] ?? [];
        if (is_string($tags)) {
            $tags = array_filter(array_map('trim', explode(',', $tags)));
        }

        $knownStacks = ['Laravel', 'PHP', 'Docker', 'Filament', 'Livewire', 'TypeScript',
                        'Node', 'MySQL', 'PostgreSQL', 'Redis', 'Vite', 'Tailwind',
                        'Coreops', 'MCP', 'Context7', 'devorq', 'SQLite'];

        foreach ($knownStacks as $stack) {
            if (in_array($stack, $tags, true)) {
                return $stack;
            }
        }

        // Infer from source file path
        $file = $raw['_source_file'] ?? '';

        $stackPatterns = [
            'Laravel' => ['laravel', 'livewire', 'filament'],
            'PHP' => ['.php', '/app/'],
            'Docker' => ['docker', 'container'],
            'TypeScript' => ['typescript', '.ts'],
            'Node' => ['node', 'npm', 'package.json'],
            'MySQL' => ['mysql', 'database'],
            'devorq' => ['devorq'],
            'Coreops' => ['coreops'],
            'MCP' => ['mcp', '.mcp.json'],
        ];

        $file = strtolower($file);
        foreach ($stackPatterns as $stack => $patterns) {
            foreach ($patterns as $pattern) {
                if (str_contains($file, $pattern)) {
                    return $stack;
                }
            }
        }

        return null;
    }

    /**
     * Infer scope from raw data.
     */
    public function inferScope(array $raw): string
    {
        if (! empty($raw['scope'])) {
            return $raw['scope'];
        }

        // If source_project is set and is a specific project, it's project scope
        $project = $raw['_source_project'] ?? '';
        if (! empty($project) && $project !== 'global') {
            return 'project';
        }

        return 'global';
    }
}

/**
 * Value object for normalized memory data.
 */
class MemoryData
{
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly MemoryType $type,
        public readonly ?string $stack,
        public readonly string $scope,
        public readonly ?string $source_system,
        public readonly ?string $source_file,
        public readonly ?string $source_project,
        public readonly ?string $original_id,
        public readonly ?Severity $severity,
        public readonly int $recurrence_count,
        public readonly ?string $official_reference,
        public readonly ?string $external_reference,
        public readonly ValidationStatus $validation_status,
    ) {}

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type->value,
            'stack' => $this->stack,
            'scope' => $this->scope,
            'source_system' => $this->source_system,
            'source_file' => $this->source_file,
            'source_project' => $this->source_project,
            'original_id' => $this->original_id,
            'severity' => $this->severity?->value,
            'recurrence_count' => $this->recurrence_count,
            'official_reference' => $this->official_reference,
            'external_reference' => $this->external_reference,
            'validation_status' => $this->validation_status->value,
        ];
    }
}
