<?php

namespace App\Services;

use App\Enums\MemoryScope;
use App\Models\ApiToken;
use App\Models\HarnessProfile;
use App\Models\Memory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;

/**
 * Centraliza a fronteira de dados do transporte MCP.
 *
 * O transporte stdio local não carrega token e é tratado como operação do
 * operador do hub. O transporte HTTP sempre recebe um ApiToken pelo
 * middleware e, para usuários não administradores, só enxerga o projeto do
 * próprio token mais memórias globais.
 */
class McpAccessPolicy
{
    public function memories(?ApiToken $token): Builder
    {
        if ($this->isOperator($token)) {
            return Memory::query();
        }

        $projectId = $this->projectId($token);

        return Memory::query()->where(function (Builder $query) use ($projectId) {
            $query->where('scope', MemoryScope::GLOBAL->value)
                ->orWhere(function (Builder $query) use ($projectId) {
                    $query->where('scope', MemoryScope::PROJECT->value)
                        ->where('project_id', $projectId);
                });
        });
    }

    public function memory(?ApiToken $token, string $id): ?Memory
    {
        return $this->memories($token)->find($id);
    }

    /**
     * Lookup usado por operações de mutação (update/validate/delete).
     *
     * Para operadores retorna tudo; para tokens de projeto retorna SÓ memórias
     * do próprio projeto. Memórias globais continuam visíveis para leitura
     * (`memories`/`memory`), mas não podem ser alteradas por quem não é admin —
     * isso evita que um token de projeto vazado sequestre conhecimento global.
     */
    public function writableMemory(?ApiToken $token, string $id): ?Memory
    {
        if ($this->isOperator($token)) {
            return Memory::query()->find($id);
        }

        $projectId = $this->projectId($token);

        return Memory::query()
            ->where('scope', MemoryScope::PROJECT->value)
            ->where('project_id', $projectId)
            ->find($id);
    }

    public function canPublishGlobal(?ApiToken $token): bool
    {
        return $this->isOperator($token);
    }

    public function projectId(?ApiToken $token): string
    {
        if ($token?->project_id === null) {
            throw new AuthorizationException('Token MCP sem projeto associado. Emita um novo token vinculado a um projeto.');
        }

        return $token->project_id;
    }

    public function harnessProfiles(?ApiToken $token): Builder
    {
        if ($this->isOperator($token)) {
            return HarnessProfile::query();
        }

        if ($token?->user_id === null) {
            throw new AuthorizationException('Token MCP inválido para acesso a perfis de harness.');
        }

        return HarnessProfile::query()->where('user_id', $token->user_id);
    }

    public function isOperator(?ApiToken $token): bool
    {
        return $token === null || ($token->is_global && $token->user?->is_admin === true);
    }
}
