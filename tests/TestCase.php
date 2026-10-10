<?php

namespace Tests;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    use LazilyRefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new RuntimeException('Pengujian hanya boleh memakai SQLite :memory:, bukan database aplikasi.');
        }
    }

    /** Database uji selalu baru di memori; tidak perlu perintah penghapusan data. */
    protected function migrateDatabases(): void
    {
        $this->artisan('migrate', ['--force' => true]);
    }
}
