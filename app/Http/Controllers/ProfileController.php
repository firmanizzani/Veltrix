<?php

namespace App\Http\Controllers;

use App\Services\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Profile');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'lowercase',
                Rule::unique('users')->ignore($request->user()->id),
            ],
            'current_password' => 'required|current_password',
        ]);
        DB::transaction(function () use ($request, $data) {
            $request->user()->update(
                collect($data)
                    ->only(['name', 'email'])
                    ->all(),
            );
            Audit::record(
                $request->user(),
                'profile.updated',
                'user',
                $request->user()->id,
            );
        });

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => 'required|current_password',
            'password' => [
                'required',
                'confirmed',
                Password::min(12)->letters()->numbers(),
            ],
        ]);
        DB::transaction(function () use ($request, $data) {
            $request->user()->update([
                'password' => $data['password'],
                'remember_token' => Str::random(60),
                'session_version' => $request->user()->session_version + 1,
            ]);
            $request
                ->session()
                ->put('session_version', $request->user()->session_version);
            DB::table('sessions')
                ->where('user_id', $request->user()->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
            Audit::record(
                $request->user(),
                'password.changed',
                'user',
                $request->user()->id,
            );
        });
        $request->session()->regenerate();

        return back()->with(
            'success',
            'Password diperbarui. Sesi perangkat lain dicabut.',
        );
    }
}
