<?php

namespace App\Http\Controllers;

use App\Models\OperationalNotification;
use App\Models\Setting;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function edit(Request $request): \Inertia\Response
    {
        abort_unless($request->user()->isOwner(), 403);
        return Inertia::render('Settings', ['settings' => Setting::business()]);
    }

    public function update(Request $request): \Illuminate\Http\RedirectResponse
    {
        abort_unless($request->user()->isOwner(), 403);
        $data = $request->validate([
            'business_name' => 'required|string|max:100',
            'contact_email' => 'nullable|email|max:255',
            'receipt_footer' => 'required|string|max:300',
        ]);
        DB::transaction(function () use ($request, $data) {
            foreach ($data as $key => $value) {
                Setting::updateOrCreate(['key' => $key], ['value' => $value ?? '']);
            }
            Audit::record($request->user(), 'settings.updated', 'settings', null, $data);
        });
        return back()->with('success', 'Pengaturan bisnis disimpan.');
    }

    public function readNotifications(Request $request): \Illuminate\Http\RedirectResponse
    {
        abort_if($request->user()->role === \App\Enums\Role::Cashier, 403);
        OperationalNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        return back()->with('success', 'Notifikasi ditandai sudah dibaca.');
    }
}
