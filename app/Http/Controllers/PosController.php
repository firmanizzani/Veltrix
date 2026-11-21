<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\CheckoutRequest;
use App\Models\Branch;
use App\Models\BranchProduct;
use App\Models\CashierShift;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Services\Audit;
use App\Services\BranchAccess;
use App\Services\Checkout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    public function index(Request $request, BranchAccess $access): Response
    {
        $branchId =
            $access->selected($request) ?? Branch::where('is_active', true)->value('id');

        return Inertia::render('Pos', [
            'products' => BranchProduct::with('product.category')
                ->where('branch_id', $branchId)
                ->whereHas('product', fn($q) => $q->where('is_active', true))
                ->get(),
            'shift' => CashierShift::where('cashier_id', $request->user()->id)
                ->whereNull('closed_at')
                ->first(),
            'branch' => Branch::find($branchId),
            'branches' => $request->user()->isOwner()
                ? Branch::where('is_active', true)->get()
                : [],
            'canOperate' => $request->user()->role === Role::Cashier,
        ]);
    }

    public function checkout(
        CheckoutRequest $request,
        Checkout $checkout,
    ): RedirectResponse {
        $order = $checkout->complete($request->user(), $request->validated());

        return to_route('orders.show', $order)->with(
            'success',
            'Pembayaran berhasil dicatat.',
        );
    }

    public function openShift(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === Role::Cashier, 403);
        $data = $request->validate([
            'opening_cash' => 'required|integer|min:0|max:100000000',
        ]);
        DB::transaction(function () use ($request, $data) {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            if (
                CashierShift::where('cashier_id', $request->user()->id)
                    ->whereNull('closed_at')
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'opening_cash' => 'Masih ada shift yang terbuka.',
                ]);
            }
            $shift = CashierShift::create([
                'branch_id' => $request->user()->branch_id,
                'cashier_id' => $request->user()->id,
                'opening_cash' => $data['opening_cash'],
                'opened_at' => now(),
            ]);
            Audit::record($request->user(), 'shift.opened', 'shift', $shift->id);
        });

        return back()->with('success', 'Shift kasir dibuka.');
    }

    public function closeShift(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === Role::Cashier, 403);
        $data = $request->validate([
            'actual_cash' => 'required|integer|min:0|max:100000000',
        ]);
        DB::transaction(function () use ($request, $data) {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $shift = CashierShift::where('cashier_id', $request->user()->id)
                ->whereNull('closed_at')
                ->lockForUpdate()
                ->firstOrFail();
            $cash = Payment::where('method', 'cash')
                ->whereHas('order', fn($q) => $q->where('shift_id', $shift->id))
                ->sum('amount');
            $refund = Refund::where('status', 'approved')
                ->whereHas(
                    'order',
                    fn($q) => $q
                        ->where('shift_id', $shift->id)
                        ->whereHas('payment', fn($p) => $p->where('method', 'cash')),
                )
                ->sum('amount');
            $shift->update([
                'actual_cash' => $data['actual_cash'],
                'expected_cash' => $shift->opening_cash + $cash - $refund,
                'closed_at' => now(),
                'status' => 'closed',
            ]);
            Audit::record($request->user(), 'shift.closed', 'shift', $shift->id, [
                'actual_cash' => $data['actual_cash'],
            ]);
        });

        return back()->with('success', 'Shift ditutup dan kas direkonsiliasi.');
    }

    public function show(Request $request, Order $order): Response
    {
        Gate::authorize('view', $order);

        return Inertia::render('Receipt', [
            'order' => $order->load('items', 'payment', 'branch', 'cashier', 'refund'),
        ]);
    }
}
