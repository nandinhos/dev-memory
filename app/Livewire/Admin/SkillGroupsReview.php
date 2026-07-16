<?php

namespace App\Livewire\Admin;

use App\Enums\SkillGroupStatus;
use App\Models\SkillGroup;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Grupos de Skills')]
class SkillGroupsReview extends Component
{
    public function approve(string $id): void
    {
        $group = SkillGroup::findOrFail($id);
        $group->update(['status' => SkillGroupStatus::APPROVED]);

        $this->dispatch('show-toast', message: "Grupo \"{$group->name}\" aprovado", type: 'sucesso');
    }

    public function reject(string $id): void
    {
        $group = SkillGroup::findOrFail($id);
        $group->update(['status' => SkillGroupStatus::REJECTED]);

        $this->dispatch('show-toast', message: "Grupo \"{$group->name}\" rejeitado", type: 'aviso');
    }

    public function render()
    {
        return view('livewire.admin.skill-groups-review', [
            'groups' => SkillGroup::with('memories:id,title,type')
                ->orderByDesc('cohesion')
                ->get(),
        ]);
    }
}
