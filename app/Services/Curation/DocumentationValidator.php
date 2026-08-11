<?php

namespace App\Services\Curation;

use App\Enums\DocumentationValidationStatus;
use App\Models\Memory;

/**
 * Grounded documentation check: retrieves official doc excerpts via
 * Context7 and asks the engine to compare the memory's claims AGAINST
 * the retrieved text only — never "is this correct?" from thin air.
 */
class DocumentationValidator
{
    public function __construct(
        private AnthropicCurationEngine $engine,
        private Context7Client $docs,
    ) {}

    /**
     * $technologyOverride: quando a IA aponta a biblioteca CORRETA após um
     * falso-negativo, re-resolvemos o Context7 por ela em vez do 1º termo do stack.
     * O veredito segue 100% ancorado nos trechos recuperados — a IA só escolhe ONDE
     * olhar, nunca decide o resultado.
     */
    public function validate(Memory $memory, ?string $technologyOverride = null): DocValidationOutcome
    {
        $technology = $technologyOverride !== null && trim($technologyOverride) !== ''
            ? trim($technologyOverride)
            : $this->primaryTechnology($memory);

        if ($technology === null) {
            return DocValidationOutcome::inconclusive('memória sem stack definida');
        }

        $libraryId = $this->docs->resolveLibrary($technology);

        if ($libraryId === null) {
            return DocValidationOutcome::inconclusive("documentação de '{$technology}' não encontrada no Context7");
        }

        $docText = $this->docs->fetchDocs($libraryId, $memory->title);

        if ($docText === null) {
            return DocValidationOutcome::inconclusive("trechos de documentação não recuperados para '{$memory->title}'");
        }

        $verdict = $this->engine->completeJson(
            $this->systemPrompt(),
            $this->userPrompt($memory, $libraryId, $docText),
            fn (array $json) => DocumentationVerdict::fromArray($json),
        );

        return DocValidationOutcome::fromVerdict(
            $verdict,
            $libraryId,
            $this->engine->lastMeta(),
            $this->extractSources($docText),
        );
    }

    /**
     * Deterministic source traceability: Context7 excerpts carry
     * "Source: https://..." lines pointing to the official page each
     * snippet came from. These URLs anchor the memory's evidence.
     */
    private function extractSources(string $docText): array
    {
        preg_match_all('/^Source:\s*(https?:\/\/\S+)/mi', $docText, $matches);

        return array_values(array_unique($matches[1]));
    }

    private function primaryTechnology(Memory $memory): ?string
    {
        $stack = trim((string) $memory->stack);

        if ($stack === '') {
            return null;
        }

        return trim(explode(',', $stack)[0]);
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
Você é um verificador de conformidade técnica.

Compare EXCLUSIVAMENTE as afirmações da MEMÓRIA com os trechos da DOCUMENTAÇÃO OFICIAL fornecidos. Não use conhecimento externo. Se a documentação fornecida não cobrir uma afirmação, o veredito dessa afirmação é "unsupported" — nunca invente suporte.

Responda APENAS com o objeto JSON, sem markdown nem texto extra:
{
  "status": "confirmed" | "partially_confirmed" | "contradicted" | "inconclusive",
  "claims": [{"claim": string, "verdict": "supported" | "unsupported" | "contradicted", "notes": string ou null}],
  "version_constraints": [string],
  "confidence": número entre 0 e 1
}

CRITÉRIOS DE STATUS:
- "confirmed": todas as afirmações centrais da memória são suportadas pelos trechos.
- "partially_confirmed": parte suportada, parte sem cobertura nos trechos.
- "contradicted": ao menos uma afirmação central contradiz os trechos.
- "inconclusive": os trechos não cobrem o tema da memória.
PROMPT;
    }

    private function userPrompt(Memory $memory, string $libraryId, string $docText): string
    {
        return "MEMÓRIA:\n"
            ."Título: {$memory->title}\n"
            ."Stack: {$memory->stack}\n"
            ."Conteúdo:\n{$memory->description}\n\n"
            ."DOCUMENTAÇÃO OFICIAL RECUPERADA (fonte: Context7 {$libraryId}):\n"
            .$docText."\n\n"
            .'TAREFA: Compare exclusivamente as afirmações da memória com os trechos fornecidos acima e produza o veredito JSON.';
    }
}

class DocValidationOutcome
{
    private function __construct(
        public DocumentationValidationStatus $status,
        public ?DocumentationVerdict $verdict,
        public ?string $libraryId,
        public ?string $note,
        public ?array $engineMeta,
        public array $sources = [],
    ) {}

    public static function inconclusive(string $note): self
    {
        return new self(
            status: DocumentationValidationStatus::INCONCLUSIVE,
            verdict: null,
            libraryId: null,
            note: $note,
            engineMeta: null,
        );
    }

    public static function fromVerdict(
        DocumentationVerdict $verdict,
        string $libraryId,
        array $engineMeta,
        array $sources = [],
    ): self {
        return new self(
            status: $verdict->status,
            verdict: $verdict,
            libraryId: $libraryId,
            note: null,
            engineMeta: $engineMeta,
            sources: $sources,
        );
    }

    public function toReport(): array
    {
        return array_filter([
            'verdict' => $this->verdict?->toArray(),
            'library' => $this->libraryId,
            'sources' => $this->sources !== [] ? $this->sources : null,
            'note' => $this->note,
            'engine' => $this->engineMeta,
        ], fn ($value) => $value !== null);
    }
}
