<?php

namespace App\Livewire\Admin;

use App\Models\HarnessProfile;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Harness')]
class HarnessProfiles extends Component
{
    public ?string $expandedId = null;

    public function toggle(string $id): void
    {
        $this->expandedId = $this->expandedId === $id ? null : $id;
    }

    public function delete(string $id): void
    {
        HarnessProfile::whereKey($id)->delete();

        $this->dispatch('show-toast', message: 'Perfil removido', type: 'aviso');
    }

    public function render()
    {
        return view('livewire.admin.harness-profiles', [
            'profiles' => HarnessProfile::orderBy('harness')->orderBy('name')->get(),
        ]);
    }
}
