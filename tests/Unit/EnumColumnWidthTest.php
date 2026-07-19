<?php

namespace Tests\Unit;

use App\Enums\MemoryScope;
use App\Enums\MemoryType;
use App\Enums\ValidationStatus;
use PHPUnit\Framework\TestCase;

/**
 * Guarda o contrato largura-da-coluna vs valor-do-enum. Postgres enforça
 * varchar(N); SQLite (usado nos testes) não — então um insert-test nunca pega
 * o overflow. Este teste, DB-agnóstico, falha se alguém adicionar um valor de
 * enum maior que a coluna que o armazena (foi exatamente o bug do
 * 'architecture_decision' (21) numa coluna varchar(20)).
 */
class EnumColumnWidthTest extends TestCase
{
    /** memories.type é varchar(50) (migration widen_memories_type_column). */
    public function test_memory_type_values_fit_column(): void
    {
        foreach (MemoryType::cases() as $case) {
            $this->assertLessThanOrEqual(
                50,
                strlen($case->value),
                "MemoryType::{$case->name} ('{$case->value}') excede varchar(50) de memories.type"
            );
        }
    }

    /** memories.validation_status e memories.scope são varchar(20). */
    public function test_status_and_scope_values_fit_column(): void
    {
        foreach (ValidationStatus::cases() as $case) {
            $this->assertLessThanOrEqual(20, strlen($case->value), "ValidationStatus '{$case->value}' > varchar(20)");
        }

        foreach (MemoryScope::cases() as $case) {
            $this->assertLessThanOrEqual(20, strlen($case->value), "MemoryScope '{$case->value}' > varchar(20)");
        }
    }
}
