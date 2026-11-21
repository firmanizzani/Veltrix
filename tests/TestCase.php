<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): \Illuminate\Foundation\Application
    {
        $app = parent::createApplication();
        $driver = config('database.default');
        $safeMemory =
            $driver === 'sqlite' &&
            config('database.connections.sqlite.database') === ':memory:';
        $safePostgres =
            $driver === 'pgsql' &&
            config('database.connections.pgsql.host') === '127.0.0.1' &&
            config('database.connections.pgsql.database') === 'veltrix_test';
        if (!$safeMemory && !$safePostgres) {
            throw new \RuntimeException(
                'Pengujian ditolak: gunakan database pengujian terisolasi. Neon aplikasi tidak boleh dipakai oleh test runner.',
            );
        }
        return $app;
    }
}
