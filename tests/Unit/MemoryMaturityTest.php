<?php

namespace Tests\Unit;

use App\Enums\MemoryMaturity;
use Tests\TestCase;

class MemoryMaturityTest extends TestCase
{
    public function test_memory_maturity_cases_and_colors(): void
    {
        $this->assertEquals('Solução de Contorno (Workaround)', MemoryMaturity::WORKAROUND->label());
        $this->assertEquals('Provisório', MemoryMaturity::PROVISIONAL->label());
        $this->assertEquals('Recomendado', MemoryMaturity::RECOMMENDED->label());
        $this->assertEquals('Canônico', MemoryMaturity::CANONICAL->label());
        $this->assertEquals('Consolidado', MemoryMaturity::CONSOLIDATED->label());

        $this->assertNotEmpty(MemoryMaturity::WORKAROUND->color());
    }

    public function test_maturity_transition_rules(): void
    {
        $this->assertTrue(MemoryMaturity::PROVISIONAL->canTransitionTo(MemoryMaturity::RECOMMENDED));
        $this->assertTrue(MemoryMaturity::RECOMMENDED->canTransitionTo(MemoryMaturity::CANONICAL));
        $this->assertTrue(MemoryMaturity::CANONICAL->canTransitionTo(MemoryMaturity::CONSOLIDATED));

        // Transição direta inválida (ex.: provisional para consolidated)
        $this->assertFalse(MemoryMaturity::PROVISIONAL->canTransitionTo(MemoryMaturity::CONSOLIDATED));
    }
}
