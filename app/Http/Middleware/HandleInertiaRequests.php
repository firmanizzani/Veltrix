<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return [
            'business' => fn() => $request->user() ? \App\Models\Setting::business() : [],
            ...parent::share($request),
            'auth' => ['user' => $request->user()?->loadMissing('branch')],
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'status' => fn() => $request->session()->get('status'),
            ],
        ];
    }
}
