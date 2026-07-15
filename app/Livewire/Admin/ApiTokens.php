<?php

namespace App\Livewire\Admin;

use App\Models\ApiToken;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Title('Tokens MCP')]
class ApiTokens extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    public ?string $plaintext = null;

    public function create(): void
    {
        $this->validate();

        [, $plain] = ApiToken::issue(auth()->user(), $this->name);

        $this->plaintext = $plain;
        $this->name = '';

        $this->dispatch('show-toast', message: 'Token criado — copie agora, ele não será exibido novamente', type: 'sucesso');
    }

    public function revoke(string $id): void
    {
        ApiToken::where('id', $id)->where('user_id', auth()->id())->delete();

        $this->dispatch('show-toast', message: 'Token revogado', type: 'aviso');
    }

    public function render()
    {
        return view('livewire.admin.api-tokens', [
            'tokens' => ApiToken::where('user_id', auth()->id())->latest()->get(),
        ]);
    }
}
