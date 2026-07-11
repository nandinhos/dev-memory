<?php

namespace Tests\Unit;

use App\Enums\DocumentationValidationStatus;
use App\Services\Curation\DocumentationVerdict;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DocumentationVerdictTest extends TestCase
{
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'status' => 'confirmed',
            'claims' => [
                ['claim' => 'ILIKE é específico do PostgreSQL', 'verdict' => 'supported', 'notes' => null],
            ],
            'version_constraints' => ['Laravel 13'],
            'confidence' => 0.9,
        ], $overrides);
    }

    public function test_creates_verdict_from_valid_payload(): void
    {
        $verdict = DocumentationVerdict::fromArray($this->validPayload());

        $this->assertSame(DocumentationValidationStatus::CONFIRMED, $verdict->status);
        $this->assertSame(0.9, $verdict->confidence);
        $this->assertCount(1, $verdict->claims);
    }

    public function test_rejects_pending_status_from_model(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/status/');

        DocumentationVerdict::fromArray($this->validPayload(['status' => 'pending']));
    }

    public function test_rejects_invalid_claim_verdict(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/claims\[0\]/');

        DocumentationVerdict::fromArray($this->validPayload([
            'claims' => [['claim' => 'algo', 'verdict' => 'talvez']],
        ]));
    }

    public function test_rejects_confidence_out_of_range(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/confidence/');

        DocumentationVerdict::fromArray($this->validPayload(['confidence' => 1.5]));
    }

    public function test_round_trip_to_array(): void
    {
        $payload = $this->validPayload();

        $this->assertSame($payload, DocumentationVerdict::fromArray($payload)->toArray());
    }
}
