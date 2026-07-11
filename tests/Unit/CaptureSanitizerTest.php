<?php

namespace Tests\Unit;

use App\Services\Curation\CaptureSanitizer;
use PHPUnit\Framework\TestCase;

class CaptureSanitizerTest extends TestCase
{
    private CaptureSanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->sanitizer = new CaptureSanitizer;
    }

    public function test_zero_leak_against_pilot_eval_secrets(): void
    {
        $cases = json_decode(
            file_get_contents(__DIR__.'/../Fixtures/curation/eval-cases.json'),
            true,
        );

        foreach ($cases as $case) {
            if (($case['group'] ?? null) !== 'secrets') {
                continue;
            }

            $result = $this->sanitizer->sanitize($case['content']);

            foreach ($case['planted_secrets'] as $secret) {
                $this->assertStringNotContainsString(
                    $secret,
                    $result->text,
                    "Segredo '{$secret}' vazou no caso {$case['id']}",
                );
            }
        }
    }

    public function test_redacts_env_assignment(): void
    {
        $result = $this->sanitizer->sanitize('config: DB_PASSWORD=SuperSecreta123 no .env');

        $this->assertStringContainsString('DB_PASSWORD=[REDACTED]', $result->text);
        $this->assertStringNotContainsString('SuperSecreta123', $result->text);
        $this->assertArrayHasKey('env_assignment', $result->redactions);
    }

    public function test_redacts_jwt_and_bearer(): void
    {
        $result = $this->sanitizer->sanitize(
            'Header: Bearer eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxIn0.abc123def456',
        );

        $this->assertStringNotContainsString('eyJhbGciOiJIUzI1NiJ9', $result->text);
    }

    public function test_redacts_vendor_api_keys(): void
    {
        $result = $this->sanitizer->sanitize(
            'chaves: sk-mmx-abc123def456ghi789, ghp_abcdefghij1234567890, AKIAIOSFODNN7EXAMPLE',
        );

        $this->assertStringNotContainsString('sk-mmx-abc123def456ghi789', $result->text);
        $this->assertStringNotContainsString('ghp_abcdefghij1234567890', $result->text);
        $this->assertStringNotContainsString('AKIAIOSFODNN7EXAMPLE', $result->text);
    }

    public function test_redacts_url_credentials(): void
    {
        $result = $this->sanitizer->sanitize('postgres://usuario:senha123@db.host:5432/app');

        $this->assertStringNotContainsString('senha123', $result->text);
        $this->assertStringContainsString('usuario:[REDACTED]@', $result->text);
    }

    public function test_redacts_portuguese_password_phrase(): void
    {
        $result = $this->sanitizer->sanitize('a senha do postgres era hub_s3nh4_f0rte antes da troca');

        $this->assertStringNotContainsString('hub_s3nh4_f0rte', $result->text);
    }

    public function test_redacts_base64_app_key(): void
    {
        $result = $this->sanitizer->sanitize('APP_KEY=base64:bX9ednU1XUc8RISgyF3ZhtVvqHewZsGT63xYT8iG4Z4=');

        $this->assertStringNotContainsString('bX9ednU1XUc8RISgyF3Zht', $result->text);
    }

    public function test_masks_emails(): void
    {
        $result = $this->sanitizer->sanitize('reportado por fulano.dev@example.com ontem');

        $this->assertStringNotContainsString('fulano.dev@example.com', $result->text);
        $this->assertStringContainsString('f***@example.com', $result->text);
    }

    public function test_truncates_oversized_content(): void
    {
        $result = $this->sanitizer->sanitize(str_repeat('a', CaptureSanitizer::MAX_LENGTH + 500));

        $this->assertLessThan(CaptureSanitizer::MAX_LENGTH + 100, mb_strlen($result->text));
        $this->assertStringContainsString('[TRUNCADO', $result->text);
        $this->assertArrayHasKey('truncated', $result->redactions);
    }

    public function test_strips_control_characters(): void
    {
        $result = $this->sanitizer->sanitize("texto\x00com\x07controle\nmas preserva quebras");

        $this->assertStringNotContainsString("\x00", $result->text);
        $this->assertStringContainsString("\n", $result->text);
    }

    public function test_clean_content_passes_untouched(): void
    {
        $content = 'Migration falhou com duplicate column. Corrigido com Schema::hasColumn.';
        $result = $this->sanitizer->sanitize($content);

        $this->assertSame($content, $result->text);
        $this->assertSame(0, $result->totalRedactions());
    }
}
