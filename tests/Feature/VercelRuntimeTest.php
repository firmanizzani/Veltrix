<?php

namespace Tests\Feature;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Tests\TestCase;

class VercelRuntimeTest extends TestCase
{
    public function test_serverless_bootstrap_uses_writable_paths_and_preserves_https(): void
    {
        $originalEnvironment = $_ENV;
        $originalServer = $_SERVER;
        $runtimeKeys = [
            'VERCEL',
            'LARAVEL_STORAGE_PATH',
            'VIEW_COMPILED_PATH',
            'APP_CONFIG_CACHE',
            'APP_EVENTS_CACHE',
            'APP_PACKAGES_CACHE',
            'APP_SERVICES_CACHE',
            'APP_ROUTES_CACHE',
        ];
        $originalValues = [];
        foreach ($runtimeKeys as $key) {
            $originalValues[$key] = getenv($key);
        }

        try {
            require dirname(__DIR__, 2) . '/bootstrap/vercel.php';

            foreach (['LARAVEL_STORAGE_PATH', 'VIEW_COMPILED_PATH'] as $key) {
                $this->assertDirectoryIsWritable($_ENV[$key]);
                $this->assertStringStartsWith(sys_get_temp_dir(), $_ENV[$key]);
            }

            foreach (
                ['APP_CONFIG_CACHE', 'APP_PACKAGES_CACHE', 'APP_SERVICES_CACHE']
                as $key
            ) {
                $this->assertDirectoryIsWritable(dirname($_ENV[$key]));
                $this->assertStringStartsWith(sys_get_temp_dir(), $_ENV[$key]);
            }

            $_ENV['VERCEL'] = $_SERVER['VERCEL'] = '1';
            $app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
            $app->addAbsoluteCachePathPrefix(sys_get_temp_dir());
            $this->app = $app;
            $kernel = $app->make(Kernel::class);
            $kernel->bootstrap();
            $request = Request::create(
                'http://internal/up',
                server: [
                    'REMOTE_ADDR' => '10.0.0.1',
                    'HTTP_X_FORWARDED_PROTO' => 'https',
                    'HTTP_X_FORWARDED_HOST' => 'veltrix.example.com',
                    'HTTP_X_FORWARDED_PORT' => '443',
                ],
            );

            $response = $kernel->handle($request);

            $this->assertSame(200, $response->getStatusCode());
            $this->assertTrue($request->isSecure());
            $this->assertSame('https://veltrix.example.com/login', url('/login'));
            $this->assertSame($_ENV['LARAVEL_STORAGE_PATH'], $app->storagePath());
            $this->assertSame($_ENV['VIEW_COMPILED_PATH'], config('view.compiled'));

            $kernel->terminate($request, $response);
        } finally {
            $_ENV = $originalEnvironment;
            $_SERVER = $originalServer;
            foreach ($originalValues as $key => $value) {
                putenv($value === false ? $key : $key . '=' . $value);
            }
            Request::setTrustedProxies([], Request::HEADER_X_FORWARDED_FOR);
        }
    }
}
