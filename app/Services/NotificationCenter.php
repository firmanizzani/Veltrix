<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\OperationalNotification;
use App\Models\User;

class NotificationCenter
{
    public function lowStock(Inventory $stock, bool $reopen = false): void
    {
        $recipients = User::where('is_active', true)
            ->where(function ($query) use ($stock) {
                $query->where('role', 'owner')->orWhere(function ($query) use ($stock) {
                    $query
                        ->where('role', 'manager')
                        ->where('branch_id', $stock->branch_id);
                });
            })
            ->get();
        foreach ($recipients as $user) {
            $identity = [
                'user_id' => $user->id,
                'event_key' => 'stock-low:' . $stock->id,
            ];
            $content = [
                'title' => 'Stok ' . $stock->ingredient->name . ' menipis',
                'message' =>
                    $stock->branch->city .
                    ': tersisa ' .
                    $stock->quantity .
                    ' ' .
                    $stock->ingredient->unit .
                    ', minimum ' .
                    $stock->minimum .
                    '.',
                'link' => '/manage/inventory',
                'read_at' => null,
            ];
            if ($reopen) {
                OperationalNotification::updateOrCreate($identity, $content);
            } else {
                OperationalNotification::firstOrCreate($identity, $content);
            }
        }
    }
}
