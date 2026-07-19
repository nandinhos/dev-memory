<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Prompts\Prompt;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        $viewsPath = '/tmp/laravel-test-views';

        if (! is_dir($viewsPath)) {
            mkdir($viewsPath, 0775, true);
        }

        parent::setUp();

        // O Laravel Prompts decide como se comportar olhando se há terminal interativo.
        // Isso tornava os testes de comando dependentes de ONDE rodam: no host (com pty)
        // passavam; dentro do container via `exec -T` (sem pty) quebravam com
        // NonInteractiveValidationException, e com pty ficavam PENDURADOS esperando digitação.
        // Forçar o fallback faz o Prompts usar o QuestionHelper do Symfony, que é o que o
        // `expectsQuestion()` intercepta — teste determinístico em host, container e CI.
        Prompt::fallbackWhen(true);
    }
}
