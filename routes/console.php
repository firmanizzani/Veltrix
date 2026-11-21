<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

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
