<?php

namespace App\Services\Curation;

use App\Enums\DocumentationValidationStatus;

/**
 * Deterministic decisions about what happens to a curated draft.
 * The LLM prepares; this class decides. Auto-validation never happens
 * here — validation belongs to humans and to the documentation pipeline.
 */
class PromotionPolicy
{
    /**
     * Below this confidence a draft is discarded instead of persisted.
     * Calibrated on the P1 pilot: junk content scored 0.10-0.25 while
     * genuine knowledge scored 0.80-0.95.
     */
    public const CONFIDENCE_FLOOR = 0.5;

    /**
     * Minimum verdict confidence for a doc-confirmed memory to be
     * auto-labeled as validated (the only automatic validation allowed —
     * grounded in official docs, never in the model's own opinion).
     */
    public const AUTO_VALIDATE_CONFIDENCE = 0.8;

    public function shouldAutoValidate(DocumentationVerdict $verdict): bool
    {
        return $verdict->status === DocumentationValidationStatus::CONFIRMED
            && $verdict->confidence >= self::AUTO_VALIDATE_CONFIDENCE;
    }

    public function evaluate(LessonDraft $draft): PromotionDecision
    {
        if ($draft->confidence < self::CONFIDENCE_FLOOR) {
            return new PromotionDecision(
                persist: false,
                reasons: [sprintf(
                    'confidence %.2f abaixo do piso %.2f',
                    $draft->confidence,
                    self::CONFIDENCE_FLOOR,
                )],
            );
        }

        return new PromotionDecision(persist: true, reasons: []);
    }
}

class PromotionDecision
{
    public function __construct(
        public bool $persist,
        public array $reasons,
    ) {}
}
