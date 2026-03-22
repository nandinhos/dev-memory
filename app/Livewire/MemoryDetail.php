<?php

namespace App\Livewire;

use App\Enums\ValidationStatus;
use App\Models\Memory;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Detalhes da Memória')]
class MemoryDetail extends Component
{
    public Memory $memory;

    public function mount(Memory $memory): void
    {
        $this->memory = $memory;
    }

    public function delete(): void
    {
        $this->memory->delete();
        session()->flash('success', 'Memória removida com sucesso!');
        $this->redirect('/memories', navigate: true);
    }

    public function incrementRecurrence(): void
    {
        $this->memory->increment('recurrence_count');
        $this->memory->refresh();
        $this->dispatch('show-toast',
            message: '+1 ocorrência registrada',
            type: 'sucesso'
        );
    }

    public function markAsValidated(): void
    {
        $this->memory->update(['validation_status' => ValidationStatus::VALIDATED]);
        $this->memory->refresh();
        $this->dispatch('show-toast',
            message: 'Memória validada com sucesso!',
            type: 'sucesso'
        );
    }

    public function render()
    {
        return view('livewire.memory-detail');
    }
}
