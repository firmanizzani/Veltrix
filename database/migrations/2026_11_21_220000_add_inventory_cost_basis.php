<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->decimal('average_unit_cost', 18, 6)->default(0);
        });
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->decimal('unit_cost', 18, 6)->default(0)->change();
        });
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->decimal('unit_cost_snapshot', 18, 6)->nullable();
        });
        $costs = DB::table('ingredients')->pluck('unit_cost', 'id');
        DB::table('inventories')
            ->orderBy('id')
            ->chunkById(100, function ($stocks) use ($costs) {
                foreach ($stocks as $stock) {
                    DB::table('inventories')
                        ->where('id', $stock->id)
                        ->update([
                            'average_unit_cost' => $costs[$stock->ingredient_id] ?? 0,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table(
            'inventories',
            fn(Blueprint $table) => $table->dropColumn('average_unit_cost'),
        );
        Schema::table(
            'stock_transfers',
            fn(Blueprint $table) => $table->dropColumn('unit_cost_snapshot'),
        );
    }
};
