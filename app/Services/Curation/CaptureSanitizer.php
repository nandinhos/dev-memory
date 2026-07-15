<?php

namespace App\Services\Curation;

/**
 * Deterministic secret/PII scrubbing applied BEFORE any content reaches
 * an LLM. Regex-based on purpose: the P1 pilot proved prompt-based
 * redaction leaks (4 of 5 planted secrets escaped the model).
 */
class CaptureSanitizer
{
    public const MAX_LENGTH = 20000;

    private const TRUNCATION_MARKER = "\n[TRUNCADO: conteúdo excedeu o limite de caracteres]";

    /**
     * Ordered patterns: label => [regex, replacement].
     * Order matters (e.g. JWT before generic bearer).
     */
    private const PATTERNS = [
        'url_credentials' => ['#(//[^\s:/@]+):[^\s@/]+@#', '$1:[REDACTED]@'],
        'jwt' => ['/\beyJ[A-Za-z0-9_\-]{4,}(?:\.[A-Za-z0-9_\-]+){0,2}/', '[REDACTED_JWT]'],
        'bearer_token' => ['/\bBearer\s+[A-Za-z0-9\-._~+\/=]{8,}/i', 'Bearer [REDACTED]'],
        'env_assignment' => [
            '/\b([A-Za-z_][A-Za-z0-9_]*(?:PASSWORD|SECRET|TOKEN|_KEY|APIKEY|AUTH)[A-Za-z0-9_]*)(\s*[=:]\s*)["\']?[^\s"\']{4,}["\']?/i',
            '$1$2[REDACTED]',
        ],
        'vendor_api_key' => ['/\b(?:sk|pk|rk|ghp|gho|ghs|glpat)[-_][A-Za-z0-9\-_]{8,}\b/', '[REDACTED_KEY]'],
        'github_pat' => ['/\bgithub_pat_[A-Za-z0-9_]{20,}\b/', '[REDACTED_KEY]'],
        'aws_access_key' => ['/\bAKIA[0-9A-Z]{16}\b/', '[REDACTED_KEY]'],
        'slack_token' => ['/\bxox[baprs]-[A-Za-z0-9\-]{8,}\b/', '[REDACTED_KEY]'],
        'base64_key' => ['/\bbase64:[A-Za-z0-9+\/]{16,}={0,2}/', 'base64:[REDACTED]'],
        'password_phrase' => [
            '/\b(senhas?|passwords?|passwd|pwd)\b([^\n]{0,30}?)(?:é|era|eh|is|was|foi|[=:])\s*["\']?[^\s"\']{4,}["\']?/iu',
            '$1$2: [REDACTED]',
        ],
        'email' => [
            '/\b([A-Za-z0-9._%+\-])[A-Za-z0-9._%+\-]*@([A-Za-z0-9.\-]+\.[A-Za-z]{2,})/',
            '$1***@$2',
        ],
    ];

    /**
     * @param  int|null  $maxLength  truncation cap; null disables truncation
     *                               (used for config files that must stay intact)
     */
    public function sanitize(string $raw, ?int $maxLength = self::MAX_LENGTH): SanitizationResult
    {
        // Strip control characters (keeps \n, \r, \t)
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $raw);

        $redactions = [];

        foreach (self::PATTERNS as $label => [$pattern, $replacement]) {
            $text = preg_replace($pattern, $replacement, $text, -1, $count);

            if ($count > 0) {
                $redactions[$label] = $count;
            }
        }

        if ($maxLength !== null && mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength).self::TRUNCATION_MARKER;
            $redactions['truncated'] = 1;
        }

        return new SanitizationResult($text, $redactions);
    }
}

class SanitizationResult
{
    public function __construct(
        public string $text,
        public array $redactions,
    ) {}

    public function totalRedactions(): int
    {
        return array_sum($this->redactions);
    }
}
