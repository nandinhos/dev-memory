<?php

namespace App\Console\Commands;

use App\Enums\HarnessType;
use App\Services\HarnessProfileService;
use Illuminate\Console\Command;

class HarnessCaptureLocalCommand extends Command
{
    protected $signature = 'harness:capture-local
                            {harness=claude-code : Harness a capturar}
                            {--name=default : Nome do perfil}';

    protected $description = 'Captura a configuração local de um harness para o hub (segredos redigidos)';

    public function handle(HarnessProfileService $service): int
    {
        $harness = HarnessType::tryFrom($this->argument('harness'));

        if ($harness === null) {
            $this->error("Harness inválido: {$this->argument('harness')}");

            return self::FAILURE;
        }

        $this->info("=== Capturando config de {$harness->label()} ===");

        $files = [];

        foreach ($harness->recommendedPaths() as $path) {
            $resolved = $this->resolvePath($path);

            if ($resolved !== null && is_file($resolved) && is_readable($resolved)) {
                $files[] = ['path' => $path, 'content' => file_get_contents($resolved)];
                $this->line("  <fg=green>✓</> {$path}");
            } else {
                $this->line("  <fg=gray>–</> {$path} (não encontrado)");
            }
        }

        if ($files === []) {
            $this->warn('Nenhum arquivo de configuração encontrado.');

            return self::FAILURE;
        }

        $profile = $service->capture($harness, $files, $this->option('name'));

        $this->newLine();
        $this->info("Perfil salvo: {$harness->value}/{$profile->name} v{$profile->version} — ".count($profile->files).' arquivo(s)');

        $redacted = collect($profile->files)->filter(fn ($f) => ! empty($f['redactions']))->pluck('path');
        if ($redacted->isNotEmpty()) {
            $this->warn('Segredos redigidos em: '.$redacted->implode(', '));
        }

        return self::SUCCESS;
    }

    private function resolvePath(string $path): ?string
    {
        if (str_starts_with($path, '~/')) {
            $home = getenv('HOME') ?: ($_SERVER['HOME'] ?? null);

            return $home ? rtrim($home, '/').'/'.substr($path, 2) : null;
        }

        return base_path($path);
    }
}
