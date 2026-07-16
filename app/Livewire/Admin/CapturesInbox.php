<?php

namespace App\Livewire\Admin;

use App\Models\Capture;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Capturas')]
class CapturesInbox extends Component
{
    use WithPagination;

    public ?string $expandedId = null;

    public function toggle(string $id): void
    {
        $this->expandedId = $this->expandedId === $id ? null : $id;
    }

    public function render()
    {
        return view('livewire.admin.captures-inbox', [
            'captures' => Capture::with('memory:id,title')
                ->latest()
                ->paginate(15),
        ]);
    }
}
