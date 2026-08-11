<?php

namespace App\Services;

use App\Enums\MemoryMaturity;
use App\Models\Memory;
use App\Models\User;
use InvalidArgumentException;

class MaturityPolicy
{
    /**
     * Valida e executa a transição de maturidade de uma memória.
     */
    public function transition(Memory $memory, MemoryMaturity $targetMaturity, ?User $user = null): Memory
    {
        $currentMaturity = $memory->maturity ?? MemoryMaturity::PROVISIONAL;

        if ($currentMaturity === $targetMaturity) {
            return $memory;
        }

        if (! $currentMaturity->canTransitionTo($targetMaturity)) {
            throw new InvalidArgumentException(
                "Transição de maturidade inválida de '{$currentMaturity->label()}' para '{$targetMaturity->label()}'."
            );
        }

        // Transições para CANONICAL ou CONSOLIDATED exigem perfil administrativo
        if (in_array($targetMaturity, [MemoryMaturity::CANONICAL, MemoryMaturity::CONSOLIDATED], true)) {
            if ($user !== null && ! $user->is_admin) {
                throw new InvalidArgumentException('Apenas administradores podem promover memórias para Canônica ou Consolidada.');
            }
        }

        $memory->update([
            'maturity' => $targetMaturity,
        ]);

        return $memory;
    }
}
