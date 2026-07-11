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
}
