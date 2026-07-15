<?php

namespace App\Services;

use App\Enums\HarnessType;
use App\Models\HarnessProfile;
use App\Services\Curation\CaptureSanitizer;

/**
 * Stores harness configuration profiles (the developer's environment for a
 * given CLI) and produces provisioning plans to replicate them on a clean
 * machine. Secrets are scrubbed on capture — the hub never stores API keys;
 * the provision plan flags files that need manual secret entry on the target.
 */
class HarnessProfileService
{
    private const CONFIG_MAX_LENGTH = 200_000;

    public function __construct(
        private CaptureSanitizer $sanitizer,
    ) {}

    /**
     * Capture (upload) a set of config files into a profile. Each file is
     * sanitized; existing profiles are updated with a bumped patch version.
     *
     * @param  list<array{path: string, content: string}>  $files
     */
    public function capture(HarnessType $harness, array $files, string $name = 'default', ?string $description = null): HarnessProfile
    {
        $stored = [];

        foreach ($files as $file) {
            $result = $this->sanitizer->sanitize($file['content'] ?? '', self::CONFIG_MAX_LENGTH);

            $stored[] = [
                'path' => $file['path'],
                'content' => $result->text,
                'redactions' => $result->redactions,
            ];
        }

        $existing = HarnessProfile::where('harness', $harness->value)->where('name', $name)->first();

        return HarnessProfile::updateOrCreate(
            ['harness' => $harness->value, 'name' => $name],
            [
                'files' => $stored,
                'description' => $description ?? $existing?->description,
                'version' => $existing ? $this->bumpPatch($existing->version) : '1.0.0',
            ],
        );
    }

    /**
     * Build an actionable provisioning plan for the agent (or installer)
     * to apply on a clean machine.
     */
    public function provisionPlan(HarnessProfile $profile): array
    {
        $steps = [];
        $order = 1;
        $needsSecrets = false;

        foreach ($profile->files as $file) {
            $hadSecrets = ! empty($file['redactions']) && array_diff_key($file['redactions'], ['truncated' => true]) !== [];
            $needsSecrets = $needsSecrets || $hadSecrets;

            $steps[] = [
                'order' => $order++,
                'action' => 'write_file',
                'path' => $file['path'],
                'content' => $file['content'],
                'had_secrets' => $hadSecrets,
            ];
        }

        $notes = [
            'Aplique cada passo escrevendo o conteúdo no caminho indicado (expanda ~ para o home).',
            'Confirme antes de sobrescrever arquivos existentes.',
            'A configuração inclui a conexão MCP do dev-memory — a partir daqui esta máquina tem o hub (bootstrap).',
        ];

        if ($needsSecrets) {
            $notes[] = 'Arquivos com had_secrets=true tiveram segredos redigidos ([REDACTED]) — insira as credenciais reais na máquina de destino.';
        }

        return [
            'harness' => $profile->harness->value,
            'name' => $profile->name,
            'version' => $profile->version,
            'steps' => $steps,
            'notes' => $notes,
        ];
    }

    private function bumpPatch(string $version): string
    {
        $parts = array_pad(explode('.', $version), 3, '0');
        $parts[2] = (string) (((int) $parts[2]) + 1);

        return implode('.', $parts);
    }
}
