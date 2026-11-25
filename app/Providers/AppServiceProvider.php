<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
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
        RateLimiter::for(
            'business-chat',
            fn(Request $request) => Limit::perMinute(6)->by(
                'business-chat:' . $request->user()->id,
            ),
        );
        RateLimiter::for(
            'business-insights',
            fn(Request $request) => Limit::perMinutes(5, 1)->by(
                'business-insights:' . $request->user()->id,
            ),
        );
        \Illuminate\Validation\Rules\Password::defaults(
            fn() => \Illuminate\Validation\Rules\Password::min(12)->letters()->numbers(),
        );
    }
}
