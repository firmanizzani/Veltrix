<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryLedger
{
    public function move(
        User $actor,
        int $branchId,
        int $ingredientId,
        float $quantity,
        string $type,
        string $reason,
        ?string $reference = null,
        ?int $unitCost = null,
    ): Inventory {
        return DB::transaction(function () use (
            $actor,
            $branchId,
            $ingredientId,
            $quantity,
            $type,
            $reason,
            $reference,
            $unitCost,
        ) {
            Inventory::firstOrCreate([
                'branch_id' => $branchId,
                'ingredient_id' => $ingredientId,
            ]);
            $stock = Inventory::where('branch_id', $branchId)
                ->where('ingredient_id', $ingredientId)
                ->lockForUpdate()
                ->firstOrFail();
            $balance = round((float) $stock->quantity + $quantity, 3);
            if ($balance < 0) {
                throw ValidationException::withMessages([
                    'quantity' =>
                        'Stok ' . $stock->ingredient->name . ' tidak mencukupi.',
                ]);
            }
            $stock->update(['quantity' => $balance]);
            InventoryMovement::create([
                'inventory_id' => $stock->id,
                'actor_id' => $actor->id,
                'type' => $type,
                'quantity' => $quantity,
                'balance' => $balance,
                'unit_cost' => $unitCost ?? $stock->ingredient->unit_cost,
                'reason' => $reason,
                'reference' => $reference,
            ]);

            return $stock;
        });
    }
}
