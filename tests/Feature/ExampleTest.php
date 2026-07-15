<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_landing_page_is_public(): void
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertSee('DEV')
            ->assertSee('ENTRAR NO HUB');
    }
}
