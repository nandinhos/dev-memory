<?php

namespace App\Services;

use App\Enums\HarnessType;
use App\Models\HarnessProfile;
use App\Services\Curation\CaptureSanitizer;
use InvalidArgumentException;

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
    /**
     * Um path é seguro para provisionamento se: não faz traversal, não é
     * absoluto de sistema, não é um arquivo de login-shell (executa código),
     * e casa com um prefixo/nome de config conhecido.
     */
    public function isSafePath(string $path): bool
    {
        $path = trim($path);

        if ($path === '' || str_contains($path, '..') || str_contains($path, "\0")) {
            return false;
        }

        // Configs vivem em ~/ (home) ou relativas ao projeto — nunca em /etc, /usr…
        if (str_starts_with($path, '/')) {
            return false;
        }

        $basename = basename($path);

        // Arquivos que executam código no login/shell não são "config" segura.
        $dangerous = ['.bashrc', '.zshrc', '.bash_profile', '.zprofile', '.zshenv', '.profile', '.bash_login'];
        if (in_array($basename, $dangerous, true)) {
            return false;
        }

        $allowedPrefixes = ['~/.claude/', '.claude/', '~/.codex/', '.codex/', '~/.config/', '~/.serena/', '.serena/', '.agent/', '.devorq/'];
        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        $allowedNames = ['.mcp.json', 'CLAUDE.md', 'AGENTS.md', 'settings.json', 'settings.local.json', 'keybindings.json'];

        return in_array($basename, $allowedNames, true);
    }

    public function capture(HarnessType $harness, array $files, string $name = 'default', ?string $description = null): HarnessProfile
    {
        $stored = [];

        foreach ($files as $file) {
            $path = (string) ($file['path'] ?? '');

            // O path vira passo write_file no provisionamento — só aceita
            // caminhos de config seguros (rejeita traversal, absolutos de
            // sistema e arquivos que executam código no shell).
            if (! $this->isSafePath($path)) {
                throw new InvalidArgumentException("Caminho não permitido para captura de harness: {$path}");
            }

            $result = $this->sanitizer->sanitize($file['content'] ?? '', self::CONFIG_MAX_LENGTH);

            $stored[] = [
                'path' => $path,
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
