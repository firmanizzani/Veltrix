<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::loginView(fn () => \Inertia\Inertia::render('Auth', ['mode' => 'login']));
        Fortify::requestPasswordResetLinkView(fn () => \Inertia\Inertia::render('Auth', ['mode' => 'forgot']));
        Fortify::resetPasswordView(fn (Request $request) => \Inertia\Inertia::render('Auth', ['mode' => 'reset', 'email' => $request->email, 'token' => $request->route('token')]));
        Fortify::authenticateUsing(function (Request $request) {
            $user = \App\Models\User::where('email', strtolower($request->string('email')))->first();
            if ($user && $user->is_active && ($user->isOwner() || $user->branch?->is_active) && \Illuminate\Support\Facades\Hash::check($request->string('password'), $user->password)) {
                $request->session()->put('session_version', $user->session_version);
                return $user;
            }
            return null;
        });
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            return Limit::perMinute(10)->by(
                ($credentialId ?: $request->session()->getId()).'|'.$request->ip()
            );
        });
    }
}
