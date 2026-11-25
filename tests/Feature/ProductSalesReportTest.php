<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\RecipeVersion;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductSalesReportTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $manager;
    private User $cashier;
    private Branch $branch;
    private Branch $other;
    private Category $coffee;
    private Category $tea;
    private Product $matcha;
    private string $period = 'start=2026-11-01&end=2026-11-25';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo(now()->setDate(2026, 11, 25)->setTime(12, 0));
        $this->owner = User::factory()->create();
        $this->branch = Branch::create([
            'code' => 'DPK',
            'name' => 'Kedai Depok',
            'city' => 'Depok',
            'address' => 'Margonda',
        ]);
        $this->other = Branch::create([
            'code' => 'BGR',
            'name' => 'Kedai Bogor',
            'city' => 'Bogor',
            'address' => 'Bogor',
        ]);
        Branch::create([
            'code' => 'JKT',
            'name' => 'Kedai Jakarta',
            'city' => 'Jakarta',
            'address' => 'Jakarta',
        ]);
        $this->manager = User::factory()->create([
            'role' => 'manager',
            'branch_id' => $this->branch->id,
        ]);
        $this->cashier = User::factory()->create([
            'role' => 'cashier',
            'branch_id' => $this->branch->id,
        ]);
        $this->coffee = Category::create(['name' => 'Coffee']);
        $this->tea = Category::create(['name' => 'Tea']);
        for ($index = 0; $index < 30; $index++) {
            $product = Product::create([
                'name' =>
                    $index === 0
                        ? 'Matcha Latte'
                        : 'Menu ' . str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'sku' => 'P' . str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                'category_id' => $index < 25 ? $this->coffee->id : $this->tea->id,
                'base_price' => 20000,
                'is_active' => $index !== 29,
            ]);
            if ($index === 0) {
                $this->matcha = $product;
            }
        }
        $this->sale($this->branch, 3, 20000, '2026-11-20');
        $this->sale($this->other, 2, 22000, '2026-11-21');
        $previous = $this->sale($this->branch, 1, 20000, '2026-10-31');
        Refund::create([
            'order_id' => $previous->id,
            'requested_by' => $this->owner->id,
            'approved_by' => $this->owner->id,
            'amount' => 20000,
            'status' => 'approved',
            'reason' => 'Refund penuh',
            'processed_at' => '2026-11-24 12:00:00',
        ]);
    }

    private function sale(Branch $branch, int $quantity, int $price, string $date): Order
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
            'branch_id' => $branch->id,
        ]);
        $shift = CashierShift::create([
            'branch_id' => $branch->id,
            'cashier_id' => $cashier->id,
            'opening_cash' => 0,
            'opened_at' => now(),
        ]);
        $order = Order::create([
            'order_number' => 'REPORT-' . Str::uuid(),
            'idempotency_key' => Str::uuid(),
            'request_hash' => str_repeat('a', 64),
            'branch_id' => $branch->id,
            'cashier_id' => $cashier->id,
            'shift_id' => $shift->id,
            'subtotal' => $quantity * $price,
            'total_amount' => $quantity * $price,
            'cost_amount' => 1000,
            'completed_at' => $date . ' 12:00:00',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->matcha->id,
            'recipe_version_id' => RecipeVersion::firstOrCreate([
                'product_id' => $this->matcha->id,
                'version' => 1,
            ])->id,
            'product_name_snapshot' => $this->matcha->name,
            'unit_price' => $price,
            'quantity' => $quantity,
            'total_price' => $quantity * $price,
            'cost_amount' => 1000,
        ]);
        return $order;
    }

    public function test_complete_catalog_is_paginated_and_totals_cover_all_results(): void
    {
        $this->actingAs($this->owner)
            ->get('/reports/products?' . $this->period)
            ->assertInertia(
                fn(Assert $page) => $page
                    ->component('ProductSalesReport')
                    ->has('products.data', 25)
                    ->where('products.total', 30)
                    ->where('totals.menus', 30)
                    ->where('totals.sold_quantity', 5)
                    ->where('totals.refunded_quantity', 1)
                    ->where('totals.quantity', 4)
                    ->where('totals.amount', 84000)
                    ->where('products.data.0.name', 'Matcha Latte')
                    ->where('products.data.0.quantity', 4),
            );
        $this->get('/reports/products?' . $this->period . '&page=2')->assertInertia(
            fn(Assert $page) => $page
                ->has('products.data', 5)
                ->where('products.total', 30)
                ->where('totals.menus', 30),
        );
    }

    public function test_category_search_sort_and_branch_details_follow_the_applied_scope(): void
    {
        $this->actingAs($this->owner)
            ->get('/reports/products?' . $this->period . '&category_id=' . $this->tea->id)
            ->assertInertia(
                fn(Assert $page) => $page
                    ->has('products.data', 5)
                    ->where('totals.menus', 5)
                    ->where('totals.amount', 0),
            );
        $this->get(
            '/reports/products?' .
                $this->period .
                '&search=P000&sort=amount_desc&product_id=' .
                $this->matcha->id,
        )->assertInertia(
            fn(Assert $page) => $page
                ->has('products.data', 1)
                ->where('totals.amount', 84000)
                ->has('detail.rows', 3)
                ->where('detail.rows.0.quantity', 2)
                ->where('detail.rows.1.quantity', 2)
                ->where('detail.rows.2.quantity', 0),
        );
        $this->get(
            '/reports/products?' . $this->period . '&branch_id=' . $this->other->id,
        )->assertInertia(
            fn(Assert $page) => $page
                ->where('totals.quantity', 2)
                ->where('totals.amount', 44000),
        );
    }

    public function test_manager_scope_is_enforced_for_list_details_and_export_and_cashier_is_denied(): void
    {
        $this->actingAs($this->manager)
            ->get(
                '/reports/products?' . $this->period . '&product_id=' . $this->matcha->id,
            )
            ->assertInertia(
                fn(Assert $page) => $page
                    ->where('totals.quantity', 2)
                    ->where('totals.amount', 40000)
                    ->has('branches', 0)
                    ->has('detail.rows', 1)
                    ->where('detail.rows.0.branch_id', $this->branch->id),
            );
        $this->get(
            '/reports/products?' . $this->period . '&branch_id=' . $this->other->id,
        )->assertForbidden();
        $this->get(
            '/reports/products/export?' .
                $this->period .
                '&branch_id=' .
                $this->other->id,
        )->assertForbidden();
        $csv = $this->get('/reports/products/export?' . $this->period)
            ->assertOk()
            ->streamedContent();
        $this->assertStringContainsString('40000', $csv);
        $this->assertStringNotContainsString('84000', $csv);
        $this->actingAs($this->cashier)->get('/reports/products')->assertForbidden();
        $this->get('/reports/products/export')->assertForbidden();
    }

    public function test_csv_includes_all_pages_honors_filters_and_escapes_formula_text(): void
    {
        Product::where('sku', 'P029')->update(['name' => '=1+1']);
        $csv = $this->actingAs($this->owner)
            ->get('/reports/products/export?' . $this->period . '&page=2')
            ->assertOk()
            ->streamedContent();
        $lines = array_values(array_filter(explode("\n", trim($csv))));
        $this->assertCount(33, $lines);
        $this->assertStringContainsString("'=1+1", $csv);
        $this->assertStringContainsString('84000', $csv);
        $filtered = $this->get(
            '/reports/products/export?' .
                $this->period .
                '&category_id=' .
                $this->tea->id,
        )
            ->assertOk()
            ->streamedContent();
        $this->assertCount(8, array_filter(explode("\n", trim($filtered))));
        $this->assertStringNotContainsString('Matcha Latte', $filtered);
    }

    public function test_empty_and_invalid_filters_are_handled_without_unsafe_queries(): void
    {
        $this->actingAs($this->owner)
            ->get(
                '/reports/products?' .
                    $this->period .
                    '&search=' .
                    urlencode("%' OR 1=1 --"),
            )
            ->assertInertia(
                fn(Assert $page) => $page
                    ->has('products.data', 0)
                    ->where('totals.menus', 0)
                    ->where('totals.quantity', 0),
            );
        $this->get(
            '/reports/products?start=2026-11-25&end=2026-11-01',
        )->assertSessionHasErrors('start');
        $this->get(
            '/reports/products?start=2025-01-01&end=2026-11-25',
        )->assertSessionHasErrors('start');
        $this->get(
            '/reports/products?' . $this->period . '&sort=DROP TABLE users',
        )->assertSessionHasErrors('sort');
    }
}
