<?php

namespace App\Livewire\Admin;

use App\Enums\SkillStatus;
use App\Models\Skill;
use App\Services\Curation\SkillPublisher;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Skills')]
class SkillsAdmin extends Component
{
    use WithPagination;

    public ?string $expandedId = null;

    public function toggle(string $id): void
    {
        $this->expandedId = $this->expandedId === $id ? null : $id;
    }

    public function approve(string $id): void
    {
        $skill = Skill::findOrFail($id);
        $skill->update(['status' => SkillStatus::APPROVED]);

        $this->dispatch('show-toast', message: "Skill \"{$skill->name}\" aprovada", type: 'sucesso');
    }

    public function publish(string $id, SkillPublisher $publisher): void
    {
        $skill = Skill::findOrFail($id);

        if ($skill->status === SkillStatus::DRAFT) {
            $this->dispatch('show-toast', message: 'Aprove a skill antes de publicar', type: 'erro');

            return;
        }

        $published = $publisher->publish($skill);

        $this->dispatch('show-toast', message: "Skill publicada (v{$published->version})", type: 'sucesso');
    }

    public function render()
    {
        return view('livewire.admin.skills-admin', [
            'skills' => Skill::with('skillGroup:id,name')
                ->orderBy('status')
                ->orderBy('name')
                ->paginate(20),
        ]);
    }
}
