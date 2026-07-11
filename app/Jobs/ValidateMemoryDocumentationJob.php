<?php

namespace App\Jobs;

use App\Enums\ValidationStatus;
use App\Models\Memory;
use App\Services\Curation\CurationFailedException;
use App\Services\Curation\DocumentationValidator;
use App\Services\Curation\DocValidationOutcome;
use App\Services\Curation\PromotionPolicy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ValidateMemoryDocumentationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(
        public Memory $memory,
    ) {}

    public function handle(DocumentationValidator $validator, PromotionPolicy $policy): void
    {
        try {
            $outcome = $validator->validate($this->memory);
        } catch (CurationFailedException $e) {
            $outcome = DocValidationOutcome::inconclusive('falha do motor: '.$e->getMessage());
        }

        $this->memory->update([
            'doc_validation_status' => $outcome->status,
            'doc_validation_report' => $outcome->toReport(),
            'doc_validated_at' => now(),
        ]);

        if (
            $outcome->verdict !== null
            && $this->memory->validation_status === ValidationStatus::PENDING
            && $policy->shouldAutoValidate($outcome->verdict)
        ) {
            $this->memory->update([
                'validation_status' => ValidationStatus::VALIDATED,
                'validated_at' => now(),
                'validated_by' => 'doc-pipeline (Context7 + MiniMax)',
            ]);
        }
    }
}
