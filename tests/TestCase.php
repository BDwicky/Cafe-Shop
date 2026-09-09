<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * The parameters that should be used when running "migrate:fresh".
     *
     * @return array<string, mixed>
     */
    protected function migrateFreshUsing(): array
    {
        $path = file_exists('C:/laragon/www/cafeshop/database/migrations')
            ? 'C:/laragon/www/cafeshop/database/migrations'
            : database_path('migrations');

        return [
            '--path' => $path,
            '--realpath' => true,
            '--drop-views' => $this->shouldDropViews(),
            '--drop-types' => $this->shouldDropTypes(),
            '--seed' => $this->shouldSeed(),
        ];
    }
}
