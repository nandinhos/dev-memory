<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class MakeAdminCommand extends Command
{
    protected $signature = 'memory:make-admin
                            {--email= : E-mail do admin}
                            {--name= : Nome do admin}';

    protected $description = 'Cria ou redefine o usuário administrador do hub (login de acesso)';

    public function handle(): int
    {
        $email = $this->option('email') ?: text('E-mail', required: true);
        $name = $this->option('name') ?: text('Nome', default: 'Admin');
        $plain = password('Senha (mínimo 8 caracteres)', required: true);

        $validator = Validator::make(
            ['email' => $email, 'name' => $name, 'password' => $plain],
            [
                'email' => 'required|email',
                'name' => 'required|string|max:255',
                'password' => 'required|string|min:8',
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make($plain)],
        );

        $this->info($user->wasRecentlyCreated
            ? "Admin criado: {$user->email}"
            : "Admin atualizado: {$user->email}");

        return self::SUCCESS;
    }
}
