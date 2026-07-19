<?php

namespace App\Livewire;

use App\Enums\MemoryScope;
use App\Enums\MemoryType;
use App\Enums\ValidationStatus;
use App\Models\Memory;
use Illuminate\Validation\Rule;
use Livewire\Component;

class MemoryForm extends Component
{
    public ?string $memoryId = null;

    public string $title = '';

    public string $description = '';

    public string $type = 'error';

    public ?string $stack = null;

    public string $scope = 'project';

    public string $validation_status = 'pending';

    public ?string $official_reference = null;

    protected $listeners = ['saved' => 'onSaved'];

    public function mount(?Memory $memory = null)
    {
        if ($memory && $memory->exists) {
            $this->memoryId = $memory->id;
            $this->title = $memory->title;
            $this->description = $memory->description;
            $this->type = $memory->type->value;
            $this->stack = $memory->stack;
            $this->scope = $memory->scope->value;
            $this->validation_status = $memory->validation_status->value;
            $this->official_reference = $memory->official_reference;
        }
    }

    public function onSaved()
    {
        session()->flash('success', 'Memória salva com sucesso!');

        return $this->redirect('/memories', navigate: true);
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:500'],
            'description' => ['required', 'string'],
            'type' => ['required', Rule::enum(MemoryType::class)],
            'stack' => ['nullable', 'string', 'max:100'],
            'scope' => ['nullable', Rule::enum(MemoryScope::class)],
            'validation_status' => ['required', Rule::enum(ValidationStatus::class)],
            'official_reference' => ['nullable', 'url', 'max:1000'],
        ]);

        $data = [
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'stack' => $this->stack ?: null,
            'scope' => $this->scope,
            'validation_status' => $this->validation_status,
            'official_reference' => $this->official_reference ?: null,
        ];

        if ($this->memoryId) {
            $memory = Memory::findOrFail($this->memoryId);
            $memory->update($data);
        } else {
            Memory::create($data);
        }

        $this->dispatch('saved');
    }

    public function render()
    {
        return view('livewire.memory-form')
            ->title($this->memoryId ? 'Editar Memória' : 'Nova Memória');
    }
}
