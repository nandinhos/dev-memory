<?php

namespace App\Services\Curation;

use App\Enums\SkillStatus;
use App\Models\Skill;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Publishes approved skills into a versioned git repository — git is
 * the source of truth for distribution (F6). Each publish writes
 * SKILL.md + manifest.json, refreshes the index and commits.
 * Republishing an already-published skill bumps the patch version.
 */
class SkillPublisher
{
    private string $repoPath;

    public function __construct(
        private SkillMarkdownRenderer $renderer,
        ?string $repoPath = null,
    ) {
        $this->repoPath = $repoPath ?? config('services.skills_repo.path');
    }

    public function publish(Skill $skill): Skill
    {
        $this->ensureRepo();

        $version = $skill->status === SkillStatus::PUBLISHED
            ? $this->bumpPatch($skill->version)
            : $skill->version;

        $directory = "{$this->repoPath}/skills/{$skill->slug}";
        File::ensureDirectoryExists($directory);

        File::put("{$directory}/SKILL.md", $this->renderer->render($skill->manifest));
        File::put("{$directory}/manifest.json", json_encode(
            $skill->manifest,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        ));

        $skill->update(['status' => SkillStatus::PUBLISHED, 'version' => $version]);

        File::put("{$this->repoPath}/README.md", $this->renderIndex());

        $this->git('add', '-A');
        $this->commit("feat (skills): publica {$skill->slug} v{$version}");

        return $skill->fresh();
    }

    private function ensureRepo(): void
    {
        File::ensureDirectoryExists($this->repoPath);

        if (! File::isDirectory("{$this->repoPath}/.git")) {
            $this->git('init', '-b', 'main');
        }
    }

    private function renderIndex(): string
    {
        $rows = Skill::where('status', SkillStatus::PUBLISHED)
            ->orderBy('slug')
            ->get()
            ->map(function (Skill $skill) {
                $sources = count($skill->manifest['evidence']['official_sources'] ?? []);
                $memories = count($skill->manifest['evidence']['lesson_ids'] ?? []);

                return "| [`{$skill->slug}`](skills/{$skill->slug}/SKILL.md) | {$skill->name} | {$skill->version} | {$memories} | {$sources} |";
            })
            ->implode("\n");

        return "# DEVORQ Skills\n\n"
            ."Skills compiladas do dev-memory a partir de memórias validadas, com fontes oficiais rastreáveis.\n\n"
            ."| Skill | Nome | Versão | Memórias | Fontes |\n"
            ."|-------|------|--------|----------|--------|\n"
            .$rows."\n";
    }

    private function commit(string $message): void
    {
        $result = Process::path($this->repoPath)->run(['git', 'commit', '-m', $message]);

        if ($result->failed() && ! str_contains($result->output().$result->errorOutput(), 'nothing to commit')) {
            throw new RuntimeException('git commit falhou: '.$result->errorOutput());
        }
    }

    private function git(string ...$arguments): void
    {
        $result = Process::path($this->repoPath)->run(array_merge(['git'], $arguments));

        if ($result->failed()) {
            throw new RuntimeException("git {$arguments[0]} falhou: ".$result->errorOutput());
        }
    }

    private function bumpPatch(string $version): string
    {
        $parts = explode('.', $version);
        $parts[2] = (string) (((int) ($parts[2] ?? 0)) + 1);

        return implode('.', array_pad($parts, 3, '0'));
    }
}
