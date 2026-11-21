<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code', 12)->unique();
            $table->string('name');
            $table->string('city');
            $table->text('address');
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 16)->default('cashier');
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('session_version')->default(1);
        });
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('actor_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('action');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('changes')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('sku', 32)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->unsignedBigInteger('base_price');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('branch_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('price');
            $table->boolean('is_available')->default(true);
            $table->unique(['branch_id', 'product_id']);
            $table->timestamps();
        });
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('unit', 10);
            $table->unsignedBigInteger('unit_cost')->default(0);
            $table->timestamps();
        });
        Schema::create('recipe_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->unique(['product_id', 'version']);
            $table->timestamps();
        });
        Schema::create('recipe_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_version_id')->constrained()->restrictOnDelete();
            $table->foreignId('ingredient_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 16, 3);
            $table->unique(['recipe_version_id', 'ingredient_id']);
        });
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('ingredient_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 16, 3)->default(0);
            $table->decimal('minimum', 16, 3)->default(0);
            $table->unique(['branch_id', 'ingredient_id']);
            $table->timestamps();
        });
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('type');
            $table->decimal('quantity', 16, 3);
            $table->decimal('balance', 16, 3);
            $table->unsignedBigInteger('unit_cost')->default(0);
            $table->text('reason');
            $table->string('reference')->nullable()->index();
            $table->timestamps();
        });
        Schema::create('cashier_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('cashier_id')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('opening_cash');
            $table->bigInteger('expected_cash')->nullable();
            $table->unsignedBigInteger('actual_cash')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->string('status')->default('open');
            $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->uuid('idempotency_key')->unique();
            $table->string('request_hash', 64);
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('cashier_id')->constrained('users')->restrictOnDelete();
            $table
                ->foreignId('shift_id')
                ->constrained('cashier_shifts')
                ->restrictOnDelete();
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('total_amount');
            $table->unsignedBigInteger('cost_amount');
            $table->string('status')->default('completed');
            $table->string('customer_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->index();
            $table->timestamps();
            $table->index(['branch_id', 'completed_at']);
        });
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_name_snapshot');
            $table->foreignId('recipe_version_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('unit_price');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('total_price');
            $table->unsignedBigInteger('cost_amount');
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->restrictOnDelete();
            $table->string('method');
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('tendered');
            $table->string('reference_number')->nullable();
            $table->timestamp('paid_at');
        });
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table
                ->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->text('reason');
            $table->string('status')->default('pending');
            $table->timestamp('processed_at')->nullable()->index();
            $table->timestamps();
        });
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->string('number')->unique();
            $table->string('status')->default('ordered');
            $table->unsignedBigInteger('total_amount');
            $table->timestamps();
        });
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('ingredient_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 16, 3);
            $table->decimal('received', 16, 3)->default(0);
            $table->unsignedBigInteger('unit_price');
        });
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('source_branch_id')
                ->constrained('branches')
                ->restrictOnDelete();
            $table
                ->foreignId('destination_branch_id')
                ->constrained('branches')
                ->restrictOnDelete();
            $table->foreignId('ingredient_id')->constrained()->restrictOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->decimal('quantity', 16, 3);
            $table->string('status')->default('pending');
            $table->timestamps();
        });
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('position');
            $table->string('phone')->nullable();
            $table->date('joined_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->date('work_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unique(['employee_id', 'work_date']);
            $table->timestamps();
        });
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('category');
            $table->unsignedBigInteger('amount');
            $table->date('expense_date')->index();
            $table->text('description');
            $table->string('receipt_path')->nullable();
            $table->string('approval_status')->default('pending');
            $table->timestamps();
        });
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->date('period');
            $table->unsignedBigInteger('base_salary');
            $table->unsignedBigInteger('allowance')->default(0);
            $table->unsignedBigInteger('bonus')->default(0);
            $table->unsignedBigInteger('deduction')->default(0);
            $table->unsignedBigInteger('net_pay');
            $table->string('status')->default('draft');
            $table->unique(['employee_id', 'period']);
            $table->timestamps();
        });
        Schema::create('price_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_product_id')->constrained()->restrictOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('price');
            $table->text('reason');
            $table->string('status')->default('pending');
            $table->timestamps();
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "ALTER TABLE users ADD CONSTRAINT users_role_branch CHECK ((role = 'owner' AND branch_id IS NULL) OR (role IN ('manager', 'cashier') AND branch_id IS NOT NULL))",
            );
            DB::statement(
                'ALTER TABLE inventories ADD CONSTRAINT nonnegative_stock CHECK (quantity >= 0 AND minimum >= 0)',
            );
            DB::statement(
                'CREATE UNIQUE INDEX one_open_shift_per_cashier ON cashier_shifts (cashier_id) WHERE closed_at IS NULL',
            );
        }
    }

    public function down(): void
    {
        foreach (
            [
                'price_requests',
                'payrolls',
                'expenses',
                'work_schedules',
                'employees',
                'stock_transfers',
                'purchase_order_items',
                'purchase_orders',
                'suppliers',
                'refunds',
                'payments',
                'order_items',
                'orders',
                'cashier_shifts',
                'inventory_movements',
                'inventories',
                'recipe_items',
                'recipe_versions',
                'ingredients',
                'branch_products',
                'products',
                'categories',
                'audit_logs',
            ]
            as $table
        ) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropColumn(['role', 'is_active', 'session_version']);
        });
        Schema::dropIfExists('branches');
    }
};
