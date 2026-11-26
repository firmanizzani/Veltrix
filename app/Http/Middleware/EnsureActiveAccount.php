<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (
            $user &&
            (!$user->is_active ||
                (!$user->isOwner() && !$user->branch?->is_active) ||
                $request->session()->get('session_version', $user->session_version) !==
                    $user->session_version)
        ) {
            return $this->endSession(
                $request,
                'Akses akun berubah atau dinonaktifkan. Silakan hubungi owner.',
            );
        }

        if ($user) {
            $loginAt = $request->session()->get('auth.login_at');
            if ($loginAt === null && !Auth::viaRemember()) {
                $loginAt = now()->timestamp;
                $request->session()->put('auth.login_at', $loginAt);
            }
            if (
                Auth::viaRemember() ||
                !is_int($loginAt) ||
                $loginAt > now()->timestamp ||
                now()->timestamp - $loginAt >= config('session.absolute_lifetime') * 60
            ) {
                return $this->endSession(
                    $request,
                    'Sesi login 7 hari telah berakhir. Silakan masuk kembali.',
                );
            }
        }

        return $next($request);
    }

    private function endSession(Request $request, string $message): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->withErrors(['email' => $message]);
    }
}
