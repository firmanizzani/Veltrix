<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && (! $user->is_active || (! $user->isOwner() && ! $user->branch?->is_active) || $request->session()->get('session_version', $user->session_version) !== $user->session_version)) {
            Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
            return redirect('/login')->withErrors(['email' => 'Akses akun berubah atau dinonaktifkan. Silakan hubungi owner.']);
        }
        return $next($request);
    }
}
