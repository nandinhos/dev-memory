<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        $viewsPath = '/tmp/laravel-test-views';

        if (! is_dir($viewsPath)) {
            mkdir($viewsPath, 0775, true);
        }

        parent::setUp();
    }
}
