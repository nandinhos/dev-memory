<?php

namespace Tests\Feature;

use App\Enums\KnowledgeEdgeOrigin;
use App\Enums\KnowledgeEdgeStatus;
use App\Enums\KnowledgeRelationType;
use App\Enums\MemoryScope;
use App\Enums\MemoryType;
use App\Enums\ValidationStatus;
use App\Livewire\Admin\RelationProposalsReview;
use App\Livewire\MemoryDetail;
use App\Models\ApiToken;
use App\Models\KnowledgeEdge;
use App\Models\KnowledgeNode;
use App\Models\Memory;
use App\Models\User;
use App\Services\HubBriefingService;
use App\Services\KnowledgeGraph\KnowledgeGraphProjector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regra de Curadoria v2.0 (§5.2, §5.3, §5.4, §3) — `deprecated` da spec
 * mapeado para o estado existente `superseded` (decisão do desenvolvedor).
 */
class CurationV2Test extends TestCase
{
    use RefreshDatabase;

    private string $token;

    private string $projectId;

    protected function setUp(): void
    {
        parent::setUp();
        [$token, $this->token] = ApiToken::issue(User::factory()->create(), 'curation-v2');
        $this->projectId = $token->project_id;
    }

    private function callTool(string $tool, array $args = []): array
    {
        $response = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
            ->postJson('/api/mcp', [
                'jsonrpc' => '2.0',
                'method' => 'tools/call',
                'params' => ['name' => $tool, 'arguments' => $args],
                'id' => 1,
            ]);

        return json_decode($response->json('result.content.0.text'), true);
    }

    private function nodeFor(Memory $memory): KnowledgeNode
    {
        // Convenção dos testes de grafo: nó nasce pelo projector
        // (chave namespace=memory/canonical_key=id; outra chave colide no UNIQUE).
        return app(KnowledgeGraphProjector::class)
            ->projectMemory($memory->fresh());
    }

    public function test_memory_search_projects_validation_status_and_scope(): void
    {
        Memory::create([
            'title' => 'Lição pesquisável curadoria',
            'description' => 'Conteúdo sobre curadoria determinística.',
            'type' => MemoryType::LESSON,
            'validation_status' => ValidationStatus::PENDING,
            'scope' => MemoryScope::PROJECT,
            'project_id' => $this->projectId,
        ]);

        $result = $this->callTool('memory_search', ['query' => 'curadoria', 'limit' => 5]);

        $first = collect($result['results'])->firstWhere('title', 'Lição pesquisável curadoria');
        $this->assertNotNull($first);
        $this->assertSame('pending', $first['validation_status']);
        $this->assertSame('project', $first['scope']);
    }

    public function test_hub_briefing_excludes_supersedes_target_still_validated(): void
    {
        $old = Memory::create([
            'title' => 'Regra antiga de cache',
            'description' => 'Cache obsoleto.',
            'type' => MemoryType::LESSON,
            'stack' => 'Laravel',
            'validation_status' => ValidationStatus::VALIDATED,
            'recurrence_count' => 9,
        ]);
        $new = Memory::create([
            'title' => 'Regra nova de cache',
            'description' => 'Cache vigente.',
            'type' => MemoryType::LESSON,
            'stack' => 'Laravel',
            'validation_status' => ValidationStatus::VALIDATED,
            'recurrence_count' => 2,
        ]);

        KnowledgeEdge::create([
            'source_node_id' => $this->nodeFor($new)->id,
            'target_node_id' => $this->nodeFor($old)->id,
            'relation_type' => KnowledgeRelationType::SUPERSEDES,
            'status' => KnowledgeEdgeStatus::VALIDATED,
            'origin' => KnowledgeEdgeOrigin::HUMAN,
        ]);

        $briefing = app(HubBriefingService::class)->briefing('Laravel');
        $titles = collect($briefing['relevant_lessons'])->pluck('title');

        $this->assertTrue($titles->contains('Regra nova de cache'));
        $this->assertFalse($titles->contains('Regra antiga de cache'));
    }

    public function test_memory_create_fast_track_validates_with_complete_probe(): void
    {
        $result = $this->callTool('memory_create', [
            'title' => 'Fast-track completa',
            'description' => 'Comportamento provado por probe determinístico.',
            'type' => 'lesson',
            'scope' => 'project',
            'probe_exit_code' => 0,
            'probe_platform' => 'Linux x86_64 php 8.3',
            'probe_output_hash' => 'sha256:abc123',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('validated', $result['validation_status']);
        $this->assertSame(
            ValidationStatus::VALIDATED,
            Memory::find($result['id'])->validation_status,
        );
    }

    public function test_memory_create_without_probe_stays_pending(): void
    {
        $result = $this->callTool('memory_create', [
            'title' => 'Sem probe',
            'description' => 'Heurística sem comprovação.',
            'type' => 'lesson',
            'scope' => 'project',
        ]);

        $this->assertSame(ValidationStatus::PENDING, Memory::find($result['id'])->validation_status);
    }

    public function test_memory_create_fast_track_fails_closed_on_secret(): void
    {
        $result = $this->callTool('memory_create', [
            'title' => 'Probe com vazamento',
            'description' => 'Authorization: Bearer abcdefgh12345678 vazou no texto.',
            'type' => 'lesson',
            'scope' => 'project',
            'probe_exit_code' => 0,
            'probe_platform' => 'Linux x86_64 php 8.3',
            'probe_output_hash' => 'sha256:abc123',
        ]);

        $this->assertSame(ValidationStatus::PENDING, Memory::find($result['id'])->validation_status);
    }

    public function test_fast_track_persists_probe_audit_trail(): void
    {
        $result = $this->callTool('memory_create', [
            'title' => 'Fast-track auditada',
            'description' => 'Evidência de probe deve ficar persistida.',
            'type' => 'lesson',
            'scope' => 'project',
            'probe_exit_code' => 0,
            'probe_platform' => 'Linux x86_64 php 8.3 abc1234',
            'probe_output_hash' => 'sha256:deadbeef',
        ]);

        $memory = Memory::find($result['id']);
        $this->assertSame('probe', $memory->validation_evidence['track']);
        $this->assertSame(0, $memory->validation_evidence['probe_exit_code']);
        $this->assertSame('Linux x86_64 php 8.3 abc1234', $memory->validation_evidence['probe_platform']);
        $this->assertSame('sha256:deadbeef', $memory->validation_evidence['probe_output_hash']);
        $this->assertSame('fast-track:probe', $memory->validated_by);
        $this->assertNotNull($memory->validated_at);

        $fetched = $this->callTool('memory_get', ['id' => $result['id']]);
        $this->assertSame('probe', $fetched['validation_evidence']['track']);
    }

    public function test_track_a_reference_with_pin_and_probe_validates_and_audits(): void
    {
        $result = $this->callTool('memory_create', [
            'title' => 'Fato externo pinado',
            'description' => 'Comportamento confirmado contra a API real.',
            'type' => 'best_practice',
            'scope' => 'project',
            'official_reference' => 'https://example.com/docs/streamable-http',
            'reference_version' => '2025-03-26',
            'probe_exit_code' => 0,
            'probe_output_hash' => 'sha256:cafe',
        ]);

        $this->assertSame('validated', $result['validation_status']);
        $memory = Memory::find($result['id']);
        $this->assertSame('reference', $memory->validation_evidence['track']);
        $this->assertSame('2025-03-26', $memory->validation_evidence['reference_version']);
        $this->assertSame('https://example.com/docs/streamable-http', $memory->official_reference);
        $this->assertSame('fast-track:reference', $memory->validated_by);
    }

    public function test_track_a_without_version_pin_stays_pending(): void
    {
        $result = $this->callTool('memory_create', [
            'title' => 'Referência sem pin',
            'description' => 'Doc sem versão não endossa fast-track.',
            'type' => 'best_practice',
            'scope' => 'project',
            'official_reference' => 'https://example.com/docs',
            'probe_exit_code' => 0,
            'probe_output_hash' => 'sha256:cafe',
        ]);

        $this->assertSame(ValidationStatus::PENDING, Memory::find($result['id'])->validation_status);
        $this->assertNull(Memory::find($result['id'])->validation_evidence);
    }

    public function test_approving_supersedes_via_proposals_page_also_marks_superseded(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $old = Memory::create([
            'title' => 'Antiga (propostas)',
            'description' => 'x',
            'type' => MemoryType::LESSON,
            'validation_status' => ValidationStatus::VALIDATED,
            'scope' => MemoryScope::GLOBAL,
        ]);
        $new = Memory::create([
            'title' => 'Nova (propostas)',
            'description' => 'y',
            'type' => MemoryType::LESSON,
            'validation_status' => ValidationStatus::VALIDATED,
            'scope' => MemoryScope::GLOBAL,
        ]);

        $edge = KnowledgeEdge::create([
            'source_node_id' => $this->nodeFor($new)->id,
            'target_node_id' => $this->nodeFor($old)->id,
            'relation_type' => KnowledgeRelationType::SUPERSEDES,
            'status' => KnowledgeEdgeStatus::PROPOSED,
            'origin' => KnowledgeEdgeOrigin::HUMAN,
        ]);

        Livewire::test(RelationProposalsReview::class)
            ->call('approve', $edge->id);

        $this->assertSame(ValidationStatus::SUPERSEDED, $old->fresh()->validation_status);
    }

    public function test_approving_supersedes_relation_marks_old_memory_superseded(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $old = Memory::create([
            'title' => 'Antiga',
            'description' => 'x',
            'type' => MemoryType::LESSON,
            'validation_status' => ValidationStatus::VALIDATED,
            'scope' => MemoryScope::GLOBAL,
        ]);
        $new = Memory::create([
            'title' => 'Nova',
            'description' => 'y',
            'type' => MemoryType::LESSON,
            'validation_status' => ValidationStatus::VALIDATED,
            'scope' => MemoryScope::GLOBAL,
        ]);

        $edge = KnowledgeEdge::create([
            'source_node_id' => $this->nodeFor($new)->id,
            'target_node_id' => $this->nodeFor($old)->id,
            'relation_type' => KnowledgeRelationType::SUPERSEDES,
            'status' => KnowledgeEdgeStatus::PROPOSED,
            'origin' => KnowledgeEdgeOrigin::HUMAN,
        ]);

        Livewire::test(MemoryDetail::class, ['memory' => $new])
            ->call('approveRelation', $edge->id);

        $this->assertSame(ValidationStatus::SUPERSEDED, $old->fresh()->validation_status);
        $this->assertNotNull(Memory::withTrashed()->find($old->id)); // nunca deletada
        $this->assertSame(ValidationStatus::VALIDATED, $new->fresh()->validation_status);
    }
}
