<?php

namespace Tests\Unit;

use App\Services\Curation\SkillGroupProposal;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SkillGroupProposalTest extends TestCase
{
    private array $validIds = ['id-1', 'id-2', 'id-3', 'id-4', 'id-5'];

    private function validPayload(): array
    {
        return [
            'groups' => [
                [
                    'name' => 'Integração Alpine + Livewire',
                    'slug' => 'alpine-livewire-interop',
                    'purpose' => 'Evitar conflitos entre Alpine e o morph do Livewire.',
                    'rationale' => 'Todas tratam do mesmo momento: componentes reativos com Alpine dentro do Livewire.',
                    'cohesion' => 0.9,
                    'memory_ids' => ['id-1', 'id-2'],
                ],
            ],
            'standalone' => [
                ['memory_id' => 'id-3', 'reason' => 'Habilidade completa por si só.'],
            ],
            'excluded' => [
                ['memory_id' => 'id-4', 'reason' => 'Fato isolado sem workflow.'],
            ],
        ];
    }

    public function test_parses_valid_proposal(): void
    {
        $proposal = SkillGroupProposal::fromArray($this->validPayload(), $this->validIds);

        $this->assertCount(1, $proposal->groups);
        $this->assertCount(1, $proposal->standalone);
        $this->assertSame('alpine-livewire-interop', $proposal->groups[0]['slug']);
    }

    public function test_rejects_unknown_memory_id(): void
    {
        $payload = $this->validPayload();
        $payload['groups'][0]['memory_ids'] = ['id-1', 'id-inexistente'];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/não pertence ao conjunto/');

        SkillGroupProposal::fromArray($payload, $this->validIds);
    }

    public function test_rejects_id_appearing_twice(): void
    {
        $payload = $this->validPayload();
        $payload['standalone'][] = ['memory_id' => 'id-1', 'reason' => 'duplicado'];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/mais de um lugar/');

        SkillGroupProposal::fromArray($payload, $this->validIds);
    }

    public function test_rejects_singleton_group(): void
    {
        $payload = $this->validPayload();
        $payload['groups'][0]['memory_ids'] = ['id-1'];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/ao menos 2 membros/');

        SkillGroupProposal::fromArray($payload, $this->validIds);
    }

    public function test_rejects_non_kebab_slug(): void
    {
        $payload = $this->validPayload();
        $payload['groups'][0]['slug'] = 'Alpine Livewire!';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/kebab-case/');

        SkillGroupProposal::fromArray($payload, $this->validIds);
    }
}
