<?php

namespace App\Http\Controllers;

use App\Enums\HarnessType;
use App\Models\HarnessProfile;
use App\Services\HarnessInstallerGenerator;
use Illuminate\Http\Response;

class HarnessInstallerController extends Controller
{
    public function __construct(
        private HarnessInstallerGenerator $generator,
    ) {}

    public function download(string $harness, string $name = 'default'): Response
    {
        $harnessEnum = HarnessType::tryFrom($harness);

        if (! $harnessEnum) {
            return response("Harness inválido: {$harness}\n", 404, ['Content-Type' => 'text/plain']);
        }

        $profile = HarnessProfile::where('harness', $harnessEnum->value)
            ->where('name', $name)
            ->first();

        if (! $profile) {
            return response("Perfil de harness não encontrado: {$harness}/{$name}\n", 404, ['Content-Type' => 'text/plain']);
        }

        $script = $this->generator->generateScript($profile);

        return response($script, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Content-Disposition' => "inline; filename=\"install-{$harness}-{$name}.sh\"",
        ]);
    }
}
