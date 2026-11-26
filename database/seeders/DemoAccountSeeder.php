<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class DemoAccountSeeder extends Seeder
{
    public const PASSWORD = 'demo1234';

    public function run(): void
    {
        if (!app()->environment(['local', 'testing'])) {
            throw new RuntimeException(
                'Akun demo hanya boleh diperbarui di local/testing.',
            );
        }

        $accountNames = ['owner'];
        foreach (['depok', 'jakarta', 'bogor'] as $city) {
            $accountNames[] = $city . '.manager';
            $accountNames[] = $city . '.kasir1';
            $accountNames[] = $city . '.kasir2';
        }

        $updatedEmails = DB::transaction(function () use ($accountNames): array {
            $updatedEmails = [];

            foreach ($accountNames as $accountName) {
                $email = $accountName . '@veltrix.com';
                $accounts = User::whereIn('email', [
                    $accountName . '@veltrix.test',
                    $email,
                ])
                    ->lockForUpdate()
                    ->get();

                if ($accounts->count() > 1) {
                    throw new RuntimeException(
                        'Alamat email tujuan sudah digunakan: ' . $email,
                    );
                }

                $user = $accounts->first();
                if (!$user) {
                    continue;
                }

                $user->update([
                    'email' => $email,
                    'password' => self::PASSWORD,
                    'remember_token' => Str::random(60),
                    'session_version' => $user->session_version + 1,
                ]);

                if (!Hash::check(self::PASSWORD, $user->fresh()->password)) {
                    throw new RuntimeException(
                        'Password demo gagal diverifikasi: ' . $email,
                    );
                }

                $updatedEmails[] = $email;
            }

            return $updatedEmails;
        });

        $this->command?->info(
            count($updatedEmails) . ' akun demo diperbarui dan password terverifikasi.',
        );
        foreach ($updatedEmails as $email) {
            $this->command?->line($email);
        }
    }
}
