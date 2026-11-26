<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchProduct;
use App\Models\CashierShift;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Ingredient;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Payroll;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\RecipeVersion;
use App\Models\Refund;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Analytics;
use App\Services\InventoryLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OperationsTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branch;
    protected Branch $otherBranch;
    protected User $owner;
    protected User $manager;
    protected User $cashier;
    protected Ingredient $ingredient;
    protected BranchProduct $menu;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->branch = Branch::create([
            'code' => 'DPK',
            'name' => 'Veltrix Depok',
            'city' => 'Depok',
            'address' => 'Margonda',
        ]);
        $this->otherBranch = Branch::create([
            'code' => 'BGR',
            'name' => 'Veltrix Bogor',
            'city' => 'Bogor',
            'address' => 'Pajajaran',
        ]);
        $this->owner = User::factory()->create();
        $this->manager = User::factory()->create([
            'role' => 'manager',
            'branch_id' => $this->branch->id,
        ]);
        $this->cashier = User::factory()->create([
            'role' => 'cashier',
            'branch_id' => $this->branch->id,
        ]);
        $category = Category::create(['name' => 'Coffee']);
        $product = Product::create([
            'name' => 'Americano',
            'sku' => 'COF-001',
            'category_id' => $category->id,
            'base_price' => 22000,
        ]);
        $this->menu = BranchProduct::create([
            'product_id' => $product->id,
            'branch_id' => $this->branch->id,
            'price' => 25000,
        ]);
        $this->ingredient = Ingredient::create([
            'name' => 'Biji kopi',
            'unit' => 'g',
            'unit_cost' => 100,
        ]);
        $recipe = RecipeVersion::create([
            'product_id' => $product->id,
            'version' => 1,
        ]);
        $recipe->items()->create([
            'ingredient_id' => $this->ingredient->id,
            'quantity' => 18,
        ]);
        app(InventoryLedger::class)->move(
            $this->owner,
            $this->branch->id,
            $this->ingredient->id,
            100,
            'opening',
            'Saldo awal pengujian',
        );
    }

    private function payload(): array
    {
        return [
            'idempotency_key' => (string) Str::uuid(),
            'items' => [
                [
                    'branch_product_id' => $this->menu->id,
                    'quantity' => 2,
                    'expected_price' => 25000,
                ],
            ],
            'method' => 'cash',
            'tendered' => 100000,
        ];
    }

    private function openShift(): void
    {
        $this->actingAs($this->cashier)
            ->post('/shifts/open', ['opening_cash' => 200000])
            ->assertSessionHasNoErrors();
    }

    public function test_readonly_records_do_not_load_unrelated_form_options(): void
    {
        \Illuminate\Support\Facades\DB::enableQueryLog();
        \Illuminate\Support\Facades\DB::flushQueryLog();
        try {
            $options = app(\App\Services\ResourceCatalog::class)->options(
                $this->owner,
                'orders',
            );
            $queries = \Illuminate\Support\Facades\DB::getQueryLog();
            $this->assertLessThanOrEqual(
                1,
                count($queries),
                'Unrelated option queries: ' . count($queries),
            );
            $this->assertSame(['branches'], array_keys($options));
        } finally {
            \Illuminate\Support\Facades\DB::disableQueryLog();
        }
    }

    public function test_inertia_does_not_contact_an_unconfigured_render_server(): void
    {
        \Illuminate\Support\Facades\Http::fake();
        config(['inertia.ssr.ensure_bundle_exists' => false]);
        $result = app(\Inertia\Ssr\Gateway::class)->dispatch([
            'component' => 'Auth',
            'props' => [],
        ]);
        $this->assertNull($result);
        \Illuminate\Support\Facades\Http::assertNothingSent();
    }

    public function test_dashboard_summary_skips_report_queries_without_changing_metrics(): void
    {
        $this->openShift();
        $this->post('/pos/checkout', $this->payload())->assertSessionHasNoErrors();
        $analytics = app(Analytics::class);
        $day = now()->toDateString();
        Inventory::where('branch_id', $this->branch->id)->update(['minimum' => 100]);
        \Illuminate\Support\Facades\DB::enableQueryLog();
        try {
            \Illuminate\Support\Facades\DB::flushQueryLog();
            $report = $analytics->report($day, $day, $this->branch->id);
            $reportQueries = count(\Illuminate\Support\Facades\DB::getQueryLog());
            \Illuminate\Support\Facades\DB::flushQueryLog();
            $summary = $analytics->report($day, $day, $this->branch->id, false);
            $summaryQueries = count(\Illuminate\Support\Facades\DB::getQueryLog());
            $this->assertGreaterThanOrEqual(10, $reportQueries - $summaryQueries);
            $this->assertSame($report['metrics'], $summary['metrics']);
            $this->assertSame($report['trend'], $summary['trend']);
            $this->assertSame(
                $report['topProducts']->toJson(),
                $summary['topProducts']->toJson(),
            );
            $this->assertSame(
                $report['branches']->first()['amount'],
                $summary['branches']->first()['amount'],
            );
            $this->assertSame([], $summary['inventory']);
            $this->assertSame([], $summary['payments']);
            $this->assertSame(
                $this->cashier->name,
                $summary['recentOrders']->first()['cashier']['name'],
            );
            $this->assertSame(
                'cash',
                $summary['recentOrders']->first()['payment']['method'],
            );
            $this->assertSame(
                $this->ingredient->name,
                $summary['lowStock']->first()['ingredient']['name'],
            );
        } finally {
            \Illuminate\Support\Facades\DB::disableQueryLog();
        }
    }

    public function test_form_options_keep_required_sources_and_branch_isolation(): void
    {
        $catalog = app(\App\Services\ResourceCatalog::class);
        foreach (
            [
                'recipes',
                'purchases',
                'transfers',
                'employees',
                'schedules',
                'payroll',
                'price-requests',
            ]
            as $resource
        ) {
            $fields = $catalog->definition($resource)['fields'];
            $options = $catalog->options($this->manager, $resource);
            foreach ($fields as $field) {
                if ($field['source']) {
                    $this->assertArrayHasKey($field['source'], $options);
                }
            }
            if (isset($options['branches'])) {
                $this->assertSame(
                    [$this->branch->id],
                    $options['branches']->pluck('value')->all(),
                );
            }
        }
    }

    public function test_all_protected_pages_require_authentication(): void
    {
        foreach (
            ['/dashboard', '/pos', '/reports', '/profile', '/manage/users']
            as $path
        ) {
            $this->get($path)->assertRedirect('/login');
        }
    }

    public function test_owner_can_save_product_photos_from_local_catalog_or_https(): void
    {
        $product = $this->menu->product;

        foreach (
            ['/images/menu/americano.jpg', 'https://example.com/coffee.jpg']
            as $imageUrl
        ) {
            $this->actingAs($this->owner)
                ->put('/manage/products/' . $product->id, [
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'category_id' => $product->category_id,
                    'base_price' => 23000,
                    'is_active' => true,
                    'image_url' => $imageUrl,
                ])
                ->assertSessionHasNoErrors();

            $this->assertDatabaseHas('products', [
                'id' => $product->id,
                'base_price' => 23000,
                'image_url' => $imageUrl,
            ]);
        }
    }

    public function test_product_photos_reject_missing_local_files_and_unsafe_urls(): void
    {
        $product = $this->menu->product;

        foreach (
            [
                '/images/menu/missing-menu-photo.jpg',
                '/images/menu/../../../.env',
                '//example.com/coffee.jpg',
                'http://example.com/coffee.jpg',
                'javascript:alert(1)',
            ]
            as $imageUrl
        ) {
            $this->actingAs($this->owner)
                ->put('/manage/products/' . $product->id, [
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'category_id' => $product->category_id,
                    'base_price' => $product->base_price,
                    'is_active' => true,
                    'image_url' => $imageUrl,
                ])
                ->assertSessionHasErrors('image_url');
        }
    }

    public function test_owner_can_render_every_implemented_resource(): void
    {
        foreach (
            [
                'branches',
                'users',
                'categories',
                'products',
                'branch-prices',
                'ingredients',
                'recipes',
                'inventory',
                'movements',
                'suppliers',
                'purchases',
                'transfers',
                'employees',
                'schedules',
                'expenses',
                'payroll',
                'orders',
                'shifts',
                'refunds',
                'price-requests',
                'audit',
            ]
            as $resource
        ) {
            $this->actingAs($this->owner)
                ->get('/manage/' . $resource)
                ->assertOk();
        }
        $this->get('/dashboard')->assertOk();
        $this->get('/reports')->assertOk();
        $this->get('/alerts')->assertOk();
    }

    public function test_manager_cannot_read_owner_pages_or_other_branch(): void
    {
        foreach (
            ['/manage/payroll', '/manage/users', '/manage/branches', '/manage/audit']
            as $path
        ) {
            $this->actingAs($this->manager)->get($path)->assertForbidden();
        }
        $this->get('/dashboard?branch_id=' . $this->otherBranch->id)->assertForbidden();
        $this->get('/pos?branch_id=' . $this->otherBranch->id)->assertForbidden();
    }

    public function test_manager_report_does_not_disclose_payroll_or_operating_profit(): void
    {
        $this->actingAs($this->manager)
            ->get('/reports')
            ->assertInertia(
                fn(Assert $page) => $page
                    ->component('Reports', false)
                    ->missing('report.metrics.payroll')
                    ->missing('report.metrics.profit')
                    ->missing('report.branches.0.payroll')
                    ->missing('report.branches.0.profit'),
            );
    }

    public function test_manager_dashboard_defaults_to_month_and_remembers_applied_dates(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 26)->setTime(12, 0));
        $this->actingAs($this->manager)
            ->get('/dashboard')
            ->assertInertia(
                fn(Assert $page) => $page
                    ->where('filters.start', '2026-11-01')
                    ->where('filters.end', '2026-11-26'),
            );
        $this->get('/dashboard?start=2026-10-01&end=2026-10-31')->assertOk();
        $this->get('/reports?start=2026-11-01&end=2026-11-20')->assertOk();
        $this->get('/dashboard')->assertInertia(
            fn(Assert $page) => $page
                ->where('filters.start', '2026-10-01')
                ->where('filters.end', '2026-10-31'),
        );
        $this->actingAs($this->owner)
            ->get('/dashboard')
            ->assertInertia(
                fn(Assert $page) => $page
                    ->where('filters.start', '2026-11-01')
                    ->where('filters.end', '2026-11-26'),
            );
        $this->actingAs($this->manager)
            ->get('/dashboard?start=2026-11-20&end=2026-11-01')
            ->assertSessionHasErrors('end');
        $this->get('/dashboard')->assertInertia(
            fn(Assert $page) => $page
                ->where('filters.start', '2026-10-01')
                ->where('filters.end', '2026-10-31'),
        );
    }

    public function test_dashboard_tasks_follow_branch_scope_and_reports_use_a_separate_page(): void
    {
        foreach ([$this->branch, $this->otherBranch] as $branch) {
            Expense::create([
                'branch_id' => $branch->id,
                'created_by' => $this->manager->id,
                'category' => 'Operasional',
                'amount' => 10000,
                'expense_date' => now()->subMonth()->toDateString(),
                'description' => 'Pengeluaran menunggu persetujuan',
                'approval_status' => 'pending',
            ]);
        }

        $this->actingAs($this->owner)
            ->get('/dashboard')
            ->assertInertia(
                fn(Assert $page) => $page
                    ->component('Dashboard', false)
                    ->where('report.tasks.2.count', 2),
            );
        $this->get('/dashboard?branch_id=' . $this->branch->id)->assertInertia(
            fn(Assert $page) => $page->where('report.tasks.2.count', 1),
        );
        $this->actingAs($this->manager)
            ->get('/dashboard')
            ->assertInertia(fn(Assert $page) => $page->where('report.tasks.2.count', 1));
        $this->get('/reports')->assertInertia(
            fn(Assert $page) => $page
                ->component('Reports', false)
                ->missing('report.tasks'),
        );
    }

    public function test_cashier_is_restricted_to_pos_and_own_sales(): void
    {
        $this->actingAs($this->cashier)->get('/dashboard')->assertRedirect('/pos');
        foreach (
            [
                '/manage/products',
                '/manage/inventory',
                '/manage/employees',
                '/manage/payroll',
                '/reports',
                '/reports/export',
            ]
            as $path
        ) {
            $this->get($path)->assertForbidden();
        }
        $this->get('/pos')->assertOk();
        $this->get('/manage/orders')->assertOk();
    }

    public function test_inactive_account_is_rejected_on_next_request(): void
    {
        $this->actingAs($this->manager);
        $this->manager->update(['is_active' => false]);
        $this->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_active_login_and_logout_use_fortify(): void
    {
        $this->post('/login', [
            'email' => $this->owner->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->owner);
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $this->owner->update(['is_active' => false]);
        $this->post('/login', [
            'email' => $this->owner->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_checkout_is_atomic_and_uses_branch_price_and_recipe(): void
    {
        $this->openShift();
        $this->post('/pos/checkout', $this->payload())->assertSessionHasNoErrors();
        $order = Order::firstOrFail();
        $this->assertSame(50000, (int) $order->total_amount);
        $this->assertSame(3600, (int) $order->cost_amount);
        $this->assertSame(64.0, (float) Inventory::first()->quantity);
        $this->assertSame(64.0, (float) InventoryMovement::sum('quantity'));
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'amount' => 50000,
        ]);
        $this->get('/orders/' . $order->id)->assertOk();
    }

    public function test_repeated_checkout_key_does_not_repeat_sale_or_stock_use(): void
    {
        $this->openShift();
        $payload = $this->payload();
        $this->post('/pos/checkout', $payload)->assertSessionHasNoErrors();
        $this->post('/pos/checkout', $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(64.0, (float) Inventory::first()->quantity);
        $payload['items'][0]['quantity'] = 1;
        $this->post('/pos/checkout', $payload)->assertSessionHasErrors('cart');
    }

    public function test_insufficient_stock_rolls_back_order_items_and_payment(): void
    {
        $this->openShift();
        $payload = $this->payload();
        $payload['items'][0]['quantity'] = 8;
        $payload['tendered'] = 500000;
        $this->post('/pos/checkout', $payload)->assertSessionHasErrors('quantity');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame(100.0, (float) Inventory::first()->quantity);
    }

    public function test_closed_shift_and_changed_price_and_short_cash_are_rejected(): void
    {
        $this->actingAs($this->cashier)
            ->post('/pos/checkout', $this->payload())
            ->assertSessionHasErrors('cart');
        $this->openShift();
        $payload = $this->payload();
        $payload['items'][0]['expected_price'] = 1;
        $this->post('/pos/checkout', $payload)->assertSessionHasErrors('cart');
        $payload = $this->payload();
        $payload['tendered'] = 1;
        $this->post('/pos/checkout', $payload)->assertSessionHasErrors('tendered');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_cannot_target_another_branch_menu(): void
    {
        $this->openShift();
        $this->menu->update(['branch_id' => $this->otherBranch->id]);
        $this->post('/pos/checkout', $this->payload())->assertForbidden();
    }

    public function test_duplicate_open_shift_is_rejected_and_closing_cash_reconciles(): void
    {
        $this->openShift();
        $this->post('/shifts/open', ['opening_cash' => 1])->assertSessionHasErrors(
            'opening_cash',
        );
        $this->post('/pos/checkout', $this->payload())->assertSessionHasNoErrors();
        $this->post('/shifts/close', [
            'actual_cash' => 249000,
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('cashier_shifts', [
            'expected_cash' => 250000,
            'actual_cash' => 249000,
            'status' => 'closed',
        ]);
    }

    public function test_manager_cannot_adjust_another_branch_stock(): void
    {
        $stock = Inventory::create([
            'branch_id' => $this->otherBranch->id,
            'ingredient_id' => $this->ingredient->id,
        ]);
        $this->actingAs($this->manager)
            ->post('/manage/inventory/' . $stock->id . '/adjust', [
                'quantity' => 10,
                'reason' => 'Manipulasi cabang lain',
            ])
            ->assertNotFound();
    }

    public function test_purchase_is_not_stock_until_received_and_overreceipt_rolls_back(): void
    {
        $supplier = Supplier::create(['name' => 'Roastery']);
        $this->actingAs($this->manager)
            ->post('/manage/purchases', [
                'branch_id' => $this->branch->id,
                'supplier_id' => $supplier->id,
                'items' => [
                    [
                        'ingredient_id' => $this->ingredient->id,
                        'quantity' => 50,
                        'unit_price' => 100,
                    ],
                ],
            ])
            ->assertSessionHasNoErrors();
        $purchase = PurchaseOrder::with('items')->firstOrFail();
        $item = $purchase->items->first();
        $this->assertSame(100.0, (float) Inventory::first()->quantity);
        $this->post('/manage/purchases/' . $purchase->id . '/receive', [
            'idempotency_key' => (string) Str::uuid(),
            'quantities' => [$item->id => 20],
        ])->assertSessionHasNoErrors();
        $this->assertSame('partial', $purchase->fresh()->status);
        $this->post('/manage/purchases/' . $purchase->id . '/receive', [
            'idempotency_key' => (string) Str::uuid(),
            'quantities' => [$item->id => 31],
        ])->assertSessionHasErrors('quantities');
        $this->assertSame(120.0, (float) Inventory::first()->quantity);
        $this->post('/manage/purchases/' . $purchase->id . '/receive', [
            'idempotency_key' => (string) Str::uuid(),
            'quantities' => [$item->id => 30],
        ])->assertSessionHasNoErrors();
        $this->assertSame('received', $purchase->fresh()->status);
        $this->assertSame(150.0, (float) Inventory::first()->quantity);
    }

    public function test_transfer_cannot_increase_destination_before_shipment(): void
    {
        $this->actingAs($this->manager)
            ->post('/manage/transfers', [
                'source_branch_id' => $this->branch->id,
                'destination_branch_id' => $this->otherBranch->id,
                'ingredient_id' => $this->ingredient->id,
                'quantity' => 25,
            ])
            ->assertSessionHasNoErrors();
        $transfer = StockTransfer::firstOrFail();
        $this->actingAs($this->owner)
            ->post('/manage/transfers/' . $transfer->id . '/receive')
            ->assertUnprocessable();
        $this->post(
            '/manage/transfers/' . $transfer->id . '/ship',
        )->assertSessionHasNoErrors();
        $this->assertSame(
            75.0,
            (float) Inventory::where('branch_id', $this->branch->id)->first()->quantity,
        );
        $this->post(
            '/manage/transfers/' . $transfer->id . '/receive',
        )->assertSessionHasNoErrors();
        $this->post(
            '/manage/transfers/' . $transfer->id . '/receive',
        )->assertUnprocessable();
        $this->assertSame(
            25.0,
            (float) Inventory::where('branch_id', $this->otherBranch->id)->first()
                ->quantity,
        );
    }

    public function test_refund_requires_manager_approval_and_does_not_restore_consumed_ingredients(): void
    {
        $this->openShift();
        $this->post('/pos/checkout', $this->payload())->assertSessionHasNoErrors();
        $order = Order::firstOrFail();
        $this->post('/orders/' . $order->id . '/refund', [
            'reason' => 'Pelanggan membatalkan pesanan',
        ])->assertSessionHasNoErrors();
        $refund = Refund::firstOrFail();
        $this->post('/manage/refunds/' . $refund->id . '/approve')->assertForbidden();
        $this->actingAs($this->manager)
            ->post('/manage/refunds/' . $refund->id . '/approve')
            ->assertSessionHasNoErrors();
        $this->assertSame('refunded', $order->fresh()->status);
        $this->assertSame(64.0, (float) Inventory::first()->quantity);
        $report = app(Analytics::class)->report(
            now()->toDateString(),
            now()->toDateString(),
            $this->branch->id,
        );
        $this->assertSame(0, $report['metrics']['net']);
        $this->assertSame(0, $report['metrics']['units']);
    }

    public function test_owner_can_create_account_but_cannot_edit_owner_as_staff(): void
    {
        $data = [
            'name' => 'Kasir baru',
            'email' => 'new@example.test',
            'password' => 'SecretPass12345',
            'password_confirmation' => 'SecretPass12345',
            'role' => 'cashier',
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ];
        $this->actingAs($this->owner)
            ->post('/manage/users', $data)
            ->assertSessionHasNoErrors();
        $this->put('/manage/users/' . $this->owner->id, $data + [])->assertForbidden();
    }

    public function test_manager_cannot_set_price_directly(): void
    {
        $this->actingAs($this->manager)
            ->put('/manage/branch-prices/' . $this->menu->id, [
                'price' => 1,
            ])
            ->assertForbidden();
        $this->post(
            '/manage/branch-prices/' . $this->menu->id . '/availability',
        )->assertSessionHasNoErrors();
        $this->assertFalse($this->menu->fresh()->is_available);
    }

    public function test_payroll_calculation_and_approval_states_are_enforced(): void
    {
        $employee = Employee::create([
            'code' => 'EMP-1',
            'name' => 'Barista',
            'branch_id' => $this->branch->id,
            'position' => 'Barista',
            'joined_at' => '2026-01-01',
        ]);
        $data = [
            'employee_id' => $employee->id,
            'period' => now()->startOfMonth()->toDateString(),
            'base_salary' => 3500000,
            'allowance' => 300000,
            'bonus' => 200000,
            'deduction' => 100000,
        ];
        $this->actingAs($this->owner)
            ->post('/manage/payroll', $data)
            ->assertSessionHasNoErrors();
        $payroll = Payroll::firstOrFail();
        $this->assertSame(3900000, (int) $payroll->net_pay);
        $this->post('/manage/payroll/' . $payroll->id . '/pay')->assertUnprocessable();
        $this->post(
            '/manage/payroll/' . $payroll->id . '/approve',
        )->assertSessionHasNoErrors();
        $this->put('/manage/payroll/' . $payroll->id, $data)->assertSessionHasErrors(
            'record',
        );
        $this->post(
            '/manage/payroll/' . $payroll->id . '/pay',
        )->assertSessionHasNoErrors();
        $this->assertSame('paid', $payroll->fresh()->status);
    }

    public function test_analytics_reconciles_sales_and_approved_expenses(): void
    {
        $this->openShift();
        $this->post('/pos/checkout', $this->payload())->assertSessionHasNoErrors();
        Expense::create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->manager->id,
            'category' => 'Listrik',
            'amount' => 10000,
            'expense_date' => now()->toDateString(),
            'description' => 'Tagihan',
            'approval_status' => 'approved',
        ]);
        $report = app(Analytics::class)->report(
            now()->toDateString(),
            now()->toDateString(),
            $this->branch->id,
        );
        $this->assertSame(50000, $report['metrics']['net']);
        $this->assertSame(36400, $report['metrics']['profit']);
        $this->assertSame(50000, array_sum(array_column($report['trend'], 'amount')));
        $branch = $report['branches']->first();
        $this->assertSame(36400, $branch['profit']);
        $this->assertSame(50000, $branch['average']);
        $this->assertSame(2, $branch['units']);
        $this->assertNull($branch['growth']);
        $this->assertSame(10000, (int) $report['expenseCategories']->first()->amount);
        $this->assertSame(6400, $report['inventory']->first()['value']);
    }

    public function test_branch_growth_compares_equal_periods_and_excludes_other_branches(): void
    {
        $this->openShift();
        $payload = $this->payload();
        $payload['items'][0]['quantity'] = 1;
        $this->post('/pos/checkout', $payload)->assertSessionHasNoErrors();
        Order::firstOrFail()->update(['completed_at' => now()->subDay()]);
        $this->post('/pos/checkout', $this->payload())->assertSessionHasNoErrors();
        $report = app(Analytics::class)->report(
            now()->toDateString(),
            now()->toDateString(),
            $this->branch->id,
        );
        $this->assertSame(100.0, $report['branches']->first()['growth']);
        $this->assertSame(25000, $report['branches']->first()['previous_sales']);
        $this->assertCount(1, $report['branches']);
        $this->assertSame(
            now()->subDay()->toDateString(),
            $report['comparison']['start'],
        );
    }

    public function test_csv_breakdowns_export_scalar_values_and_escape_spreadsheet_formulas(): void
    {
        Expense::create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->manager->id,
            'category' => '=SUM(1,2)',
            'amount' => 10000,
            'expense_date' => now()->toDateString(),
            'description' => 'Tagihan',
            'approval_status' => 'approved',
        ]);
        $response = $this->actingAs($this->manager)
            ->get(
                '/reports/export?' .
                    http_build_query([
                        'start' => now()->toDateString(),
                        'end' => now()->toDateString(),
                    ]),
            )
            ->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString("'=SUM(1,2)", $csv);
        $this->assertStringContainsString('10000', $csv);
        $this->assertStringNotContainsString('Array', $csv);
        $this->assertStringNotContainsString('payroll', $csv);
        $this->assertStringNotContainsString('veltrix_test', $csv);
    }

    public function test_expense_history_filters_dates_category_and_authorized_branch(): void
    {
        foreach (
            [
                ['Listrik', now()->toDateString()],
                ['Air', now()->toDateString()],
                ['Listrik', now()->subDay()->toDateString()],
            ]
            as [$category, $date]
        ) {
            Expense::create([
                'branch_id' => $this->branch->id,
                'created_by' => $this->manager->id,
                'category' => $category,
                'amount' => 10000,
                'expense_date' => $date,
                'description' => 'Tagihan',
            ]);
        }
        $this->actingAs($this->manager)
            ->get(
                '/manage/expenses?' .
                    http_build_query([
                        'start' => now()->toDateString(),
                        'end' => now()->toDateString(),
                        'category' => 'Listrik',
                    ]),
            )
            ->assertInertia(
                fn(Assert $page) => $page
                    ->component('Records', false)
                    ->has('records.data', 1)
                    ->where('records.data.0.category', 'Listrik'),
            );
        $this->get(
            '/manage/expenses?branch_id=' . $this->otherBranch->id,
        )->assertForbidden();
    }

    public function test_replayed_partial_receipt_does_not_duplicate_stock(): void
    {
        $supplier = Supplier::create(['name' => 'Roastery']);
        $purchase = PurchaseOrder::create([
            'branch_id' => $this->branch->id,
            'supplier_id' => $supplier->id,
            'number' => 'PO-RETRY',
            'total_amount' => 10000,
        ]);
        $item = $purchase->items()->create([
            'ingredient_id' => $this->ingredient->id,
            'quantity' => 100,
            'unit_price' => 100,
        ]);
        $payload = [
            'idempotency_key' => (string) Str::uuid(),
            'quantities' => [$item->id => 20],
        ];
        $this->actingAs($this->manager);
        foreach ([1, 2] as $attempt) {
            $this->post(
                '/manage/purchases/' . $purchase->id . '/receive',
                $payload,
            )->assertSessionHasNoErrors();
        }
        $this->assertSame(120.0, (float) Inventory::first()->quantity);
        $this->assertDatabaseCount('stock_receipts', 1);
        $this->get('/manage/receipts')->assertOk();
    }

    public function test_password_reset_revokes_old_sessions_and_requires_a_valid_token(): void
    {
        Notification::fake();
        $this->post('/forgot-password', [
            'email' => $this->cashier->email,
        ])->assertSessionHasNoErrors();
        Notification::assertSentTo(
            $this->cashier,
            \Illuminate\Auth\Notifications\ResetPassword::class,
        );
        $data = [
            'email' => $this->cashier->email,
            'password' => 'NewSecretPassword2026',
            'password_confirmation' => 'NewSecretPassword2026',
            'token' => 'invalid',
        ];
        $this->post('/reset-password', $data)->assertSessionHasErrors('email');
        $data['token'] = Password::createToken($this->cashier);
        $this->post('/reset-password', $data)->assertSessionHasNoErrors();
        $this->assertSame(2, $this->cashier->fresh()->session_version);
    }

    public function test_settings_are_owner_only_and_receipt_configuration_is_persisted(): void
    {
        $this->actingAs($this->manager)->get('/settings')->assertForbidden();
        $this->actingAs($this->owner)
            ->put('/settings', [
                'business_name' => 'Veltrix Coffee',
                'contact_email' => 'contact@example.test',
                'receipt_footer' => 'Terima kasih atas kunjungan Anda.',
            ])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('settings', [
            'key' => 'receipt_footer',
            'value' => 'Terima kasih atas kunjungan Anda.',
        ]);
        $this->get('/settings')->assertOk();
    }

    public function test_notifications_are_branch_scoped_and_read_status_is_private(): void
    {
        $stock = Inventory::first();
        $stock->update(['minimum' => 90]);
        app(InventoryLedger::class)->move(
            $this->owner,
            $this->branch->id,
            $this->ingredient->id,
            -20,
            'adjustment',
            'Pengujian notifikasi stok',
        );
        $this->assertDatabaseCount('notifications', 2);
        $this->actingAs($this->manager)->post('/alerts/read')->assertSessionHasNoErrors();
        $this->assertSame(
            1,
            \App\Models\OperationalNotification::whereNull('read_at')->count(),
        );
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->owner->id,
            'read_at' => null,
        ]);
    }

    public function test_weighted_average_cost_is_snapshotted_at_checkout(): void
    {
        app(InventoryLedger::class)->move(
            $this->owner,
            $this->branch->id,
            $this->ingredient->id,
            100,
            'receiving',
            'Penerimaan dengan harga baru',
            null,
            200,
        );
        $stock = Inventory::first();
        $this->assertSame(150.0, (float) $stock->average_unit_cost);
        $this->assertSame(30000, $stock->stock_value);
        $this->openShift();
        $this->post('/pos/checkout', $this->payload())->assertSessionHasNoErrors();
        $order = Order::firstOrFail();
        $this->assertSame(5400, (int) $order->cost_amount);
        $this->ingredient->update(['unit_cost' => 999]);
        $this->assertSame(5400, (int) $order->fresh()->cost_amount);
    }

    public function test_search_and_category_filters_apply_to_server_results(): void
    {
        $this->actingAs($this->manager)
            ->get('/manage/inventory?q=BIJI')
            ->assertInertia(
                fn(Assert $page) => $page
                    ->component('Records', false)
                    ->has('records.data', 1),
            );
        $this->get('/manage/inventory?q=tidak-ada')->assertInertia(
            fn(Assert $page) => $page->has('records.data', 0),
        );
        $this->get('/manage/products?sort=invalid-column')->assertSessionHasErrors(
            'sort',
        );
    }

    public function test_pdf_report_is_downloadable_and_cashier_cannot_export(): void
    {
        $url =
            '/reports/pdf?start=' .
            now()->toDateString() .
            '&end=' .
            now()->toDateString();
        $response = $this->actingAs($this->owner)->get($url);
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->actingAs($this->cashier)->get($url)->assertForbidden();
    }

    public function test_stock_identity_and_used_ingredient_units_cannot_be_rewritten(): void
    {
        $stock = Inventory::first();
        $this->actingAs($this->owner)
            ->put('/manage/inventory/' . $stock->id, [
                'branch_id' => $this->otherBranch->id,
                'ingredient_id' => $this->ingredient->id,
                'minimum' => 10,
            ])
            ->assertSessionHasErrors('record');
        $this->put('/manage/ingredients/' . $this->ingredient->id, [
            'name' => $this->ingredient->name,
            'unit' => 'ml',
            'unit_cost' => 100,
        ])->assertSessionHasErrors('unit');
        $this->assertSame($this->branch->id, $stock->fresh()->branch_id);
        $this->assertSame('g', $this->ingredient->fresh()->unit);
    }

    public function test_audit_preserves_change_snapshots_without_nested_secrets(): void
    {
        \App\Services\Audit::record(
            $this->owner,
            'test.security',
            'user',
            $this->cashier->id,
            [
                'before' => ['name' => 'Before', 'password' => 'private-secret'],
                'after' => ['name' => 'After', 'remember_token' => 'private-token'],
            ],
        );
        $changes = \App\Models\AuditLog::latest('id')->first()->changes;
        $this->assertSame('Before', $changes['before']['name']);
        $this->assertSame('After', $changes['after']['name']);
        $this->assertArrayNotHasKey('password', $changes['before']);
        $this->assertArrayNotHasKey('remember_token', $changes['after']);
    }

    public function test_cashier_cannot_read_a_colleagues_receipt(): void
    {
        $this->openShift();
        $this->post('/pos/checkout', $this->payload())->assertSessionHasNoErrors();
        $colleague = User::factory()->create([
            'role' => 'cashier',
            'branch_id' => $this->branch->id,
        ]);
        $this->actingAs($colleague)
            ->get('/orders/' . Order::first()->id)
            ->assertForbidden();
    }

    public function test_empty_list_filters_are_serialized_as_an_object(): void
    {
        $response = $this->actingAs($this->owner)->get('/manage/orders', [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => app(
                \App\Http\Middleware\HandleInertiaRequests::class,
            )->version(\Illuminate\Http\Request::create('/manage/orders')),
        ]);
        $response->assertOk();
        $page = json_decode($response->getContent());
        $this->assertInstanceOf(\stdClass::class, $page->props->filters);
    }
}
