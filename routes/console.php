<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new \App\Jobs\GenerateBusinessInsights())
    ->dailyAt('06:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();

Artisan::command('veltrix:insights', function () {
    $count = app(\App\Services\BusinessInsights::class)->generate();
    $this->info("Analisis selesai: {$count} insight baru.");
})->purpose('Menganalisis tujuh hari lengkap terakhir tanpa memanggil Gemini');

Artisan::command('veltrix:ai-check {--models} {--business}', function () {
    if (!config('intelligence.enabled') || !config('ai.providers.gemini.key')) {
        $this->error('Gemini belum dikonfigurasi.');
        return 1;
    }
    try {
        if ($this->option('models')) {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'x-goog-api-key' => config('ai.providers.gemini.key'),
            ])
                ->timeout(15)
                ->get('https://generativelanguage.googleapis.com/v1beta/models')
                ->throw();
            foreach ($response->json('models', []) as $model) {
                if (
                    in_array(
                        'generateContent',
                        $model['supportedGenerationMethods'] ?? [],
                        true,
                    )
                ) {
                    $this->line($model['name']);
                }
            }
            return 0;
        }
        if ($this->option('business')) {
            $owner = \App\Models\User::where('role', 'owner')
                ->where('is_active', true)
                ->firstOrFail();
            $tool = new \App\Services\BusinessAnalyticsTool(
                $owner,
                now()->startOfMonth()->toDateString(),
                now()->toDateString(),
                null,
            );
            $snapshot = $tool->read(['metric' => 'summary']);
            $response = (new \App\Services\BusinessAssistant(
                $tool,
                [],
                $snapshot,
            ))->prompt(
                'Panggil tool business_analytics dengan metric top_products untuk periode snapshot. ' .
                    'Lalu ringkas penjualan dan menu terlaris berdasarkan data yang tersedia. Sebutkan periode.',
                provider: 'gemini',
                model: config('intelligence.model'),
                timeout: 40,
            );
            if (count($tool->evidence) < 2 || trim($response->text) === '') {
                $this->error(
                    'Gemini belum mengembalikan jawaban dengan pemanggilan tool yang diperlukan.',
                );
                return 1;
            }
            $this->info(
                'Gemini berhasil membaca analytics tool dan mengembalikan jawaban berbasis data.',
            );
            return 0;
        }
        $agent = \Laravel\Ai\agent(instructions: 'Reply with OK only.');
        $agent->prompt(
            'Connection check.',
            provider: 'gemini',
            model: config('intelligence.model'),
            timeout: 15,
        );
        $this->info('Koneksi Gemini berhasil.');
        return 0;
    } catch (\Throwable $exception) {
        $cause = $exception;
        while ($cause->getPrevious()) {
            $cause = $cause->getPrevious();
        }
        if ($cause instanceof \Illuminate\Http\Client\RequestException) {
            $this->error('Google merespons HTTP ' . $cause->response->status() . '.');
            $reason = $cause->response->json('error.details.0.reason');
            if (
                in_array(
                    $reason,
                    [
                        'API_KEY_INVALID',
                        'API_KEY_SERVICE_BLOCKED',
                        'API_KEY_HTTP_REFERRER_BLOCKED',
                    ],
                    true,
                )
            ) {
                $this->error('Alasan: ' . $reason);
            }
        }
        $this->error(
            'Koneksi Gemini gagal (' .
                class_basename($exception) .
                '). Periksa key, model, dan kuota provider.',
        );
        return 1;
    }
})->purpose('Memeriksa koneksi Gemini tanpa mengirim data bisnis atau mencetak key');

Schedule::job(new \App\Jobs\RefreshOperationalNotifications())
    ->hourly()
    ->withoutOverlapping();

Artisan::command('veltrix:notifications', function () {
    app(\App\Jobs\RefreshOperationalNotifications::class)->handle(
        app(\App\Services\NotificationCenter::class),
    );
    $this->info('Notifikasi stok diperbarui.');
});

Artisan::command('veltrix:owner {email} {--name=Owner}', function () {
    if (\App\Models\User::where('role', 'owner')->exists()) {
        $this->error('Owner sudah tersedia. Perintah ini hanya untuk instalasi awal.');
        return 1;
    }
    $email = strtolower($this->argument('email'));
    $password = $this->secret('Password owner (minimal 12 karakter, huruf dan angka)');
    \Illuminate\Support\Facades\Validator::make(compact('email', 'password'), [
        'email' => 'required|email|unique:users,email',
        'password' => ['required', \Illuminate\Validation\Rules\Password::defaults()],
    ])->validate();
    \App\Models\User::create([
        'name' => $this->option('name'),
        'email' => $email,
        'password' => $password,
        'role' => 'owner',
    ]);
    $this->info('Akun owner berhasil dibuat.');
    return 0;
})->purpose('Membuat akun owner pertama tanpa registrasi publik');
