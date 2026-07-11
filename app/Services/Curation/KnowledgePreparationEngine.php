<?php

namespace App\Services\Curation;

interface KnowledgePreparationEngine
{
    /**
     * Transform a raw capture into a validated LessonDraft.
     *
     * @throws CurationFailedException when the engine cannot produce a valid draft
     */
    public function prepare(string $capture): LessonDraft;

    /**
     * Audit metadata about the last prepare() call: provider, model,
     * prompt_version, temperature, attempts and token usage.
     */
    public function lastMeta(): array;
}
