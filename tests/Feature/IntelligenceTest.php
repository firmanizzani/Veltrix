<?php

namespace Tests\Feature;

use App\Jobs\AnswerBusinessQuestion;
use App\Jobs\GenerateBusinessInsights;
use App\Models\AiMessage;
use App\Models\AiThread;
use App\Models\Branch;
use App\Models\BusinessInsight;
use App\Models\CashierShift;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Ingredient;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\RecipeVersion;
use App\Models\Refund;
use App\Models\User;
use App\Services\BusinessAnalyticsTool;
use App\Services\BusinessAssistant;
use App\Services\BusinessInsights;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Ai\Responses\Data\ToolCall;
use Tests\TestCase;

class IntelligenceTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Branch $branch;
    private User $cashier;
    private CashierShift $shift;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo(now()->setDate(2026, 11, 25)->setTime(12, 0));
        config(['intelligence.enabled' => true, 'ai.providers.gemini.key' => 'test-key']);
        Queue::fake();
        $this->owner = User::factory()->create();
        $this->branch = Branch::create([
            'code' => 'AI',
            'name' => 'Kedai AI',
            'city' => 'Depok',
            'address' => 'Margonda',
        ]);
        $this->cashier = User::factory()->create([
            'role' => 'cashier',
            'branch_id' => $this->branch->id,
        ]);
        $this->shift = CashierShift::create([
            'branch_id' => $this->branch->id,
            'cashier_id' => $this->cashier->id,
            'opening_cash' => 0,
            'opened_at' => now(),
        ]);
    }

    private function question(array $overrides = []): array
    {
        return $overrides + [
            'message' => 'Bagaimana penjualan?',
            'start' => '2026-11-01',
            'end' => '2026-11-25',
        ];
    }

    private function product(string $name): Product
    {
        return Product::create([
            'name' => $name,
            'sku' => Str::upper(Str::random(12)),
            'base_price' => 22000,
            'category_id' => Category::firstOrCreate(['name' => 'Coffee'])->id,
        ]);
    }

    private function productOrder(Product $product, int $quantity, string $date): Order
    {
        $order = $this->order($quantity * 22000, $date);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'recipe_version_id' => RecipeVersion::firstOrCreate([
                'product_id' => $product->id,
                'version' => 1,
            ])->id,
            'unit_price' => 22000,
            'quantity' => $quantity,
            'total_price' => 22000 * $quantity,
            'cost_amount' => 2000,
        ]);
        return $order;
    }

    private function switchBranch(Branch $branch): void
    {
        $this->branch = $branch;
        $this->cashier = User::factory()->create([
            'role' => 'cashier',
            'branch_id' => $branch->id,
        ]);
        $this->shift = CashierShift::create([
            'branch_id' => $branch->id,
            'cashier_id' => $this->cashier->id,
            'opening_cash' => 0,
            'opened_at' => now(),
        ]);
    }

    private function order(int $amount, string $date): Order
    {
        return Order::create([
            'order_number' => 'AI-' . Str::uuid(),
            'idempotency_key' => Str::uuid(),
            'request_hash' => str_repeat('a', 64),
            'branch_id' => $this->branch->id,
            'cashier_id' => $this->cashier->id,
            'shift_id' => $this->shift->id,
            'subtotal' => $amount,
            'total_amount' => $amount,
            'cost_amount' => 2000,
            'completed_at' => $date . ' 12:00:00',
            'customer_name' => 'Private customer',
        ]);
    }

    public function test_cashier_cannot_access_chat_and_only_owner_can_run_global_analysis(): void
    {
        $manager = User::factory()->create([
            'role' => 'manager',
            'branch_id' => $this->branch->id,
        ]);
        foreach ([$manager, $this->cashier] as $user) {
            $this->actingAs($user);
            if ($user === $this->cashier) {
                $this->get('/intelligence')->assertForbidden();
                $this->post(
                    '/intelligence/messages',
                    $this->question(),
                )->assertForbidden();
            }
            $this->post('/intelligence/generate')->assertForbidden();
        }
        $this->assertDatabaseCount('ai_messages', 0);
        Queue::assertNothingPushed();
    }

    public function test_manager_chat_is_bound_to_branch_and_shown_on_its_own_page(): void
    {
        $manager = User::factory()->create([
            'role' => 'manager',
            'branch_id' => $this->branch->id,
        ]);
        $this->actingAs($manager)
            ->post('/intelligence/messages', $this->question())
            ->assertRedirect('/intelligence?thread=1');
        $thread = AiThread::firstOrFail();
        $this->assertSame($this->branch->id, $thread->branch_id);
        Queue::assertPushed(AnswerBusinessQuestion::class);
        $this->get('/intelligence?thread=' . $thread->id)->assertInertia(
            fn(Assert $page) => $page
                ->component('Intelligence')
                ->where('available', true)
                ->where('canManageInsights', false)
                ->where('usedToday', 1)
                ->where('conversation.id', $thread->id)
                ->where('conversation.messages.1.status', 'pending')
                ->has('branches', 1)
                ->where('branches.0.id', $this->branch->id)
                ->where('filters.branch_id', $this->branch->id)
                ->has('insights.data', 0),
        );
        $this->get('/dashboard')->assertInertia(
            fn(Assert $page) => $page->missing('assistant'),
        );
        $this->get('/intelligence?tab=insights')->assertInertia(
            fn(Assert $page) => $page->where('tab', 'assistant'),
        );
        $other = User::factory()->create([
            'role' => 'manager',
            'branch_id' => $this->branch->id,
        ]);
        $this->actingAs($other)
            ->get('/intelligence?thread=' . $thread->id)
            ->assertNotFound();
        $this->post(
            '/intelligence/messages',
            $this->question(['thread_id' => $thread->id]),
        )->assertNotFound();
        $this->actingAs($manager)
            ->post('/intelligence/messages', $this->question(['branch_id' => 99999]))
            ->assertSessionHasErrors('branch_id');
    }

    public function test_manager_tool_filters_private_metrics_and_rejects_other_branches(): void
    {
        $manager = User::factory()->create([
            'role' => 'manager',
            'branch_id' => $this->branch->id,
        ]);
        $this->order(22000, '2026-11-24');
        $tool = new BusinessAnalyticsTool(
            $manager,
            '2026-11-01',
            '2026-11-25',
            $this->branch->id,
        );
        $summary = $tool->read(['metric' => 'summary']);
        $this->assertSame(22000, $summary['data']['net']);
        $this->assertArrayNotHasKey('payroll', $summary['data']);
        $this->assertArrayNotHasKey('profit', $summary['data']);
        $branches = $tool->read(['metric' => 'branch_comparison']);
        $this->assertCount(1, $branches['data']);
        $this->assertArrayNotHasKey('payroll', $branches['data'][0]);
        $this->assertArrayNotHasKey('profit', $branches['data'][0]);
        foreach (['profitability', 'insights'] as $metric) {
            try {
                $tool->read(['metric' => $metric]);
                $this->fail('Owner-only data accepted.');
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
        $other = Branch::create([
            'code' => 'OTHER',
            'name' => 'Other',
            'city' => 'Bogor',
            'address' => 'Bogor',
        ]);
        $this->actingAs($manager)
            ->post('/intelligence/messages', $this->question(['branch_id' => $other->id]))
            ->assertForbidden();
        $this->expectException(
            \Symfony\Component\HttpKernel\Exception\HttpException::class,
        );
        $tool->read(['metric' => 'summary', 'branch_id' => $other->id]);
    }

    public function test_manager_can_receive_answer_and_follow_up_without_private_evidence(): void
    {
        $manager = User::factory()->create([
            'role' => 'manager',
            'branch_id' => $this->branch->id,
        ]);
        $this->actingAs($manager)->post('/intelligence/messages', $this->question());
        BusinessAssistant::fake([
            new ToolCall('manager-summary', 'business_analytics', [
                'metric' => 'summary',
            ]),
            'Ringkasan cabang berdasarkan data.',
        ])->preventStrayPrompts();
        $answer = AiMessage::where('status', 'pending')->firstOrFail();
        (new AnswerBusinessQuestion($answer->id))->handle();
        $this->assertSame('completed', $answer->fresh()->status);
        foreach ($answer->fresh()->evidence as $source) {
            $this->assertArrayNotHasKey('payroll', $source['data']);
            $this->assertArrayNotHasKey('profit', $source['data']);
        }
        $this->post(
            '/intelligence/messages',
            $this->question(['thread_id' => $answer->ai_thread_id]),
        )->assertRedirect();
        $manager->update(['branch_id' => null]);
        $next = AiMessage::where('status', 'pending')->firstOrFail();
        (new AnswerBusinessQuestion($next->id))->handle();
        $this->assertSame('failed', $next->fresh()->status);
    }

    public function test_manager_budget_disabled_provider_and_stale_pending_are_handled(): void
    {
        $manager = User::factory()->create([
            'role' => 'manager',
            'branch_id' => $this->branch->id,
        ]);
        $this->actingAs($manager);
        config(['intelligence.enabled' => false]);
        $this->post('/intelligence/messages', $this->question())->assertSessionHasErrors(
            'message',
        );
        config(['intelligence.enabled' => true]);
        $this->post('/intelligence/messages', $this->question());
        $thread = AiThread::firstOrFail();
        $this->post(
            '/intelligence/messages',
            $this->question(['thread_id' => $thread->id]),
        )->assertSessionHasErrors('message');
        config(['intelligence.daily_limit' => 1]);
        $this->post('/intelligence/messages', $this->question())->assertSessionHasErrors(
            'message',
        );
        AiMessage::where('status', 'pending')->update([
            'created_at' => now()->subMinutes(6),
        ]);
        $this->get('/intelligence?thread=' . $thread->id)->assertInertia(
            fn(Assert $page) => $page->where('conversation.messages.1.status', 'failed'),
        );
    }

    public function test_manager_cannot_read_owner_history_after_a_role_change(): void
    {
        $this->actingAs($this->owner)->post(
            '/intelligence/messages',
            $this->question(['branch_id' => $this->branch->id]),
        );
        $thread = AiThread::firstOrFail();
        $this->owner->update(['role' => 'manager', 'branch_id' => $this->branch->id]);
        $this->actingAs($this->owner->fresh());
        $this->get('/intelligence?thread=' . $thread->id)->assertNotFound();
        $this->get('/intelligence')->assertInertia(
            fn(Assert $page) => $page->has('threads', 0),
        );
        $this->post(
            '/intelligence/messages',
            $this->question(['thread_id' => $thread->id]),
        )->assertForbidden();
    }

    public function test_manager_cannot_reopen_history_from_their_previous_branch(): void
    {
        $manager = User::factory()->create([
            'role' => 'manager',
            'branch_id' => $this->branch->id,
        ]);
        $this->actingAs($manager)->post('/intelligence/messages', $this->question());
        $thread = AiThread::firstOrFail();
        $other = Branch::create([
            'code' => 'OTHER',
            'name' => 'Other',
            'city' => 'Bogor',
            'address' => 'Bogor',
        ]);
        $manager->update(['branch_id' => $other->id]);
        $this->get('/intelligence?thread=' . $thread->id)->assertNotFound();
        $this->get('/intelligence')->assertInertia(
            fn(Assert $page) => $page
                ->has('threads', 0)
                ->where('branches.0.id', $other->id),
        );
        $this->get('/intelligence?branch_id=' . $this->branch->id)->assertForbidden();
    }

    public function test_chat_is_queued_private_and_never_exposes_credentials(): void
    {
        $this->actingAs($this->owner)
            ->post('/intelligence/messages', $this->question())
            ->assertRedirect();
        $thread = AiThread::firstOrFail();
        $this->assertDatabaseHas('ai_messages', [
            'role' => 'assistant',
            'status' => 'pending',
        ]);
        Queue::assertPushed(AnswerBusinessQuestion::class);
        $this->get('/intelligence?thread=' . $thread->id)->assertInertia(
            fn(Assert $page) => $page
                ->component('Intelligence')
                ->where('available', true)
                ->has('conversation.messages', 2),
        );
        $otherOwner = User::factory()->create();
        $this->actingAs($otherOwner)
            ->get('/intelligence?thread=' . $thread->id)
            ->assertNotFound();
        $this->post(
            '/intelligence/messages',
            $this->question(['thread_id' => $thread->id]),
        )->assertNotFound();
    }

    public function test_disabled_provider_pending_turn_and_daily_budget_are_enforced(): void
    {
        config(['intelligence.enabled' => false]);
        $this->actingAs($this->owner)
            ->post('/intelligence/messages', $this->question())
            ->assertSessionHasErrors('message');
        $this->assertDatabaseCount('ai_threads', 0);
        config(['intelligence.enabled' => true]);
        $this->post('/intelligence/messages', $this->question());
        $thread = AiThread::firstOrFail();
        $this->post(
            '/intelligence/messages',
            $this->question(['thread_id' => $thread->id]),
        )->assertSessionHasErrors('message');
        config(['intelligence.daily_limit' => 1]);
        $this->post('/intelligence/messages', $this->question())->assertSessionHasErrors(
            'message',
        );
        $this->assertDatabaseCount('ai_messages', 2);
    }

    public function test_tool_uses_actual_aggregates_and_rejects_out_of_scope_queries(): void
    {
        $this->order(22000, '2026-11-24');
        $tool = new BusinessAnalyticsTool(
            $this->owner,
            '2026-11-01',
            '2026-11-25',
            $this->branch->id,
        );
        $result = $tool->read(['metric' => 'summary']);
        $this->assertSame(22000, $result['data']['net']);
        $this->assertSame(1, $result['data']['transactions']);
        $this->assertStringNotContainsString('Private customer', json_encode($result));
        $this->assertStringNotContainsString($this->cashier->email, json_encode($result));
        $this->expectException(ValidationException::class);
        $tool->read(['metric' => 'sql', 'query' => 'SELECT * FROM users']);
    }

    public function test_tool_enforces_period_limits_and_branch_binding(): void
    {
        $tool = new BusinessAnalyticsTool(
            $this->owner,
            '2026-11-01',
            '2026-11-25',
            $this->branch->id,
        );
        try {
            $tool->read(['metric' => 'summary', 'start' => '2025-01-01']);
            $this->fail('Unbounded date range accepted.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
        $other = Branch::create([
            'code' => 'OTHER',
            'name' => 'Other',
            'city' => 'Bogor',
            'address' => 'Bogor',
        ]);
        $this->expectException(
            \Symfony\Component\HttpKernel\Exception\HttpException::class,
        );
        $tool->read(['metric' => 'summary', 'branch_id' => $other->id]);
    }

    public function test_sdk_tool_call_and_follow_up_are_saved_with_evidence(): void
    {
        $this->order(22000, '2026-11-24');
        $this->actingAs($this->owner)->post('/intelligence/messages', $this->question());
        BusinessAssistant::fake([
            new ToolCall('call-1', 'business_analytics', ['metric' => 'summary']),
            'Penjualan bersih Rp 22.000 pada 1–25 November 2026, seluruh cabang.',
        ])->preventStrayPrompts();
        $answer = AiMessage::where('role', 'assistant')->firstOrFail();
        (new AnswerBusinessQuestion($answer->id))->handle();
        $answer->refresh();
        $this->assertSame('completed', $answer->status);
        $this->assertCount(2, $answer->evidence);
        $this->assertSame(22000, $answer->evidence[1]['data']['net']);
        $this->post(
            '/intelligence/messages',
            $this->question([
                'thread_id' => $answer->ai_thread_id,
                'message' => 'Bagaimana dengan laba?',
            ]),
        )->assertRedirect();
        BusinessAssistant::fake([
            'Estimasi laba operasional Rp 20.000.',
        ])->preventStrayPrompts();
        $next = AiMessage::where('status', 'pending')->firstOrFail();
        (new AnswerBusinessQuestion($next->id))->handle();
        $this->assertSame('completed', $next->fresh()->status);
    }

    public function test_provider_failure_and_revoked_owner_access_fail_without_fake_answer(): void
    {
        $this->actingAs($this->owner)->post('/intelligence/messages', $this->question());
        BusinessAssistant::fake(
            fn() => throw new \RuntimeException('secret-provider-error'),
        )->preventStrayPrompts();
        $answer = AiMessage::where('role', 'assistant')->firstOrFail();
        (new AnswerBusinessQuestion($answer->id))->handle();
        $this->assertSame('failed', $answer->fresh()->status);
        $this->assertStringNotContainsString(
            'secret-provider-error',
            $answer->fresh()->content,
        );
        $this->post(
            '/intelligence/messages',
            $this->question(['thread_id' => $answer->ai_thread_id]),
        );
        $this->owner->update(['is_active' => false]);
        $next = AiMessage::where('status', 'pending')->firstOrFail();
        (new AnswerBusinessQuestion($next->id))->handle();
        $this->assertSame('failed', $next->fresh()->status);
    }

    public function test_insights_are_deterministic_deduplicated_and_preserve_review_status(): void
    {
        for ($index = 0; $index < 10; $index++) {
            $this->order(20000, '2026-11-12');
            $this->order(10000, '2026-11-20');
        }
        $service = app(BusinessInsights::class);
        $this->assertSame(1, $service->generate('2026-11-24'));
        $insight = BusinessInsight::firstOrFail();
        $this->assertSame('sales_decline', $insight->type);
        $this->assertSame(-50.0, (float) $insight->evidence['change_percent']);
        $this->actingAs($this->owner)
            ->patch('/intelligence/insights/' . $insight->id, ['status' => 'read'])
            ->assertRedirect();
        $this->assertSame(0, $service->generate('2026-11-24'));
        $this->assertSame('read', $insight->fresh()->status);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_insufficient_sales_data_does_not_create_a_trend_but_stock_uses_actual_ledger(): void
    {
        $ingredient = Ingredient::create([
            'name' => 'Biji kopi',
            'unit' => 'g',
            'unit_cost' => 100,
        ]);
        $stock = Inventory::create([
            'branch_id' => $this->branch->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => 40,
            'minimum' => 10,
            'average_unit_cost' => 100,
        ]);
        InventoryMovement::create([
            'inventory_id' => $stock->id,
            'actor_id' => $this->owner->id,
            'type' => 'sales',
            'quantity' => -140,
            'balance' => 40,
            'reason' => 'Konsumsi resep',
            'created_at' => '2026-11-22 12:00:00',
        ]);
        $this->assertSame(1, app(BusinessInsights::class)->generate('2026-11-24'));
        $insight = BusinessInsight::firstOrFail();
        $this->assertSame('stock_risk', $insight->type);
        $this->assertSame(2.0, (float) $insight->evidence['estimated_days']);
        $this->actingAs($this->owner)->post('/intelligence/generate')->assertRedirect();
        Queue::assertPushed(GenerateBusinessInsights::class);
    }

    public function test_explaining_an_insight_uses_its_branch_and_reserves_the_chat_budget(): void
    {
        $insight = BusinessInsight::create([
            'fingerprint' => hash('sha256', 'explain'),
            'branch_id' => $this->branch->id,
            'type' => 'stock_risk',
            'priority' => 'high',
            'title' => 'Periksa stok kopi',
            'summary' => 'Stok mencapai minimum.',
            'recommendation' => 'Periksa stok fisik.',
            'evidence' => ['quantity' => 10],
            'start' => '2026-11-18',
            'end' => '2026-11-24',
        ]);
        $this->actingAs($this->owner)
            ->post('/intelligence/insights/' . $insight->id . '/explain')
            ->assertRedirect();
        $thread = AiThread::firstOrFail();
        $this->assertSame($this->branch->id, $thread->branch_id);
        $this->assertSame('2026-11-18', $thread->start);
        $tool = new BusinessAnalyticsTool(
            $this->owner,
            $thread->start,
            $thread->end,
            $thread->branch_id,
        );
        $result = $tool->read(['metric' => 'insights']);
        $this->assertSame('Periksa stok kopi', $result['data']->first()->title);
        Queue::assertPushed(AnswerBusinessQuestion::class);
        config(['intelligence.daily_limit' => 1]);
        $this->post(
            '/intelligence/insights/' . $insight->id . '/explain',
        )->assertSessionHasErrors('message');
        $this->assertDatabaseCount('ai_threads', 1);
    }

    public function test_expired_pending_reply_is_visible_as_failure_and_never_overwritten(): void
    {
        $this->actingAs($this->owner)->post('/intelligence/messages', $this->question());
        $answer = AiMessage::where('role', 'assistant')->firstOrFail();
        $this->travel(6)->minutes();
        $this->get('/intelligence?thread=' . $answer->ai_thread_id)->assertOk();
        $this->assertSame('failed', $answer->fresh()->status);
        BusinessAssistant::fake([])->preventStrayPrompts();
        (new AnswerBusinessQuestion($answer->id))->handle();
        $this->assertSame('failed', $answer->fresh()->status);
    }

    public function test_growth_expense_anomaly_and_branch_comparison_use_distinct_evidence(): void
    {
        for ($index = 0; $index < 10; $index++) {
            $this->order(20000, '2026-11-12');
            $this->order(30000, '2026-11-20');
        }
        foreach (['2026-11-12' => 100000, '2026-11-20' => 200000] as $date => $amount) {
            Expense::create([
                'branch_id' => $this->branch->id,
                'created_by' => $this->owner->id,
                'category' => 'Utilities',
                'amount' => $amount,
                'expense_date' => $date,
                'description' => 'Biaya operasional',
                'approval_status' => 'approved',
            ]);
        }
        $this->branch = Branch::create([
            'code' => 'AI2',
            'name' => 'Kedai AI 2',
            'city' => 'Bogor',
            'address' => 'Bogor',
        ]);
        $this->cashier = User::factory()->create([
            'role' => 'cashier',
            'branch_id' => $this->branch->id,
        ]);
        $this->shift = CashierShift::create([
            'branch_id' => $this->branch->id,
            'cashier_id' => $this->cashier->id,
            'opening_cash' => 0,
            'opened_at' => now(),
        ]);
        for ($index = 0; $index < 10; $index++) {
            $this->order(5000, '2026-11-20');
        }
        app(BusinessInsights::class)->generate('2026-11-24');
        $this->assertDatabaseHas('business_insights', ['type' => 'sales_growth']);
        $this->assertDatabaseHas('business_insights', ['type' => 'expense_anomaly']);
        $this->assertDatabaseHas('business_insights', ['type' => 'branch_comparison']);
        $comparison = BusinessInsight::where('type', 'branch_comparison')->firstOrFail();
        $this->assertCount(2, $comparison->evidence['branches']);
        $this->assertSame(300000, $comparison->evidence['branches'][0]['net']);
        $this->assertSame(50000, $comparison->evidence['branches'][1]['net']);
    }

    public function test_named_product_includes_every_branch_and_reconciles_with_total(): void
    {
        $product = $this->product('Matcha Latte');
        $depok = $this->branch;
        $this->productOrder($product, 3, '2026-11-20');
        $bogor = Branch::create([
            'code' => 'BGR',
            'name' => 'Bogor',
            'city' => 'Bogor',
            'address' => 'Bogor',
        ]);
        $this->switchBranch($bogor);
        $this->productOrder($product, 6, '2026-11-21');
        Branch::create([
            'code' => 'JKT',
            'name' => 'Jakarta',
            'city' => 'Jakarta',
            'address' => 'Jakarta',
        ]);
        $tool = new BusinessAnalyticsTool($this->owner, '2026-11-01', '2026-11-25', null);
        $result = $tool->read([
            'metric' => 'product_sales',
            'product_query' => '  MATCHA   LATTE ',
            'group_by' => 'branch',
        ]);
        $this->assertSame('ok', $result['status']);
        $this->assertCount(3, $result['data']);
        $this->assertSame([3, 6, 0], array_column($result['data'], 'quantity'));
        $this->assertSame(9, $result['totals']['quantity']);
        $this->assertSame(198000, array_sum(array_column($result['data'], 'amount')));
        $scoped = new BusinessAnalyticsTool(
            $this->owner,
            '2026-11-01',
            '2026-11-25',
            $depok->id,
        );
        $onlyDepok = $scoped->read([
            'metric' => 'product_sales',
            'product_id' => $product->id,
            'group_by' => 'branch',
        ]);
        $this->assertCount(1, $onlyDepok['data']);
        $this->assertSame(3, $onlyDepok['totals']['quantity']);
    }

    public function test_product_outside_top_five_is_still_found_and_unsold_is_distinct_from_unknown(): void
    {
        $matcha = $this->product('Matcha Latte');
        $this->productOrder($matcha, 1, '2026-11-20');
        for ($index = 0; $index < 6; $index++) {
            $this->productOrder(
                $this->product('Coffee ' . $index),
                10 + $index,
                '2026-11-20',
            );
        }
        $tool = new BusinessAnalyticsTool($this->owner, '2026-11-01', '2026-11-25', null);
        $top = $tool->read(['metric' => 'top_products']);
        $this->assertNotContains($matcha->id, $top['data']->pluck('product_id')->all());
        $result = $tool->read([
            'metric' => 'product_sales',
            'product_query' => 'Matcha Latte',
        ]);
        $this->assertSame(1, $result['totals']['quantity']);
        $this->product('Unsold tea');
        $zero = $tool->read([
            'metric' => 'product_sales',
            'product_query' => 'Unsold tea',
        ]);
        $this->assertSame('ok', $zero['status']);
        $this->assertSame(0, $zero['totals']['quantity']);
        $unknown = $tool->read([
            'metric' => 'product_sales',
            'product_query' => "%' OR 1=1 --",
        ]);
        $this->assertSame('product_not_found', $unknown['status']);
        $this->assertNull($unknown['totals']);
        $literal = $tool->read([
            'metric' => 'product_sales',
            'product_query' => 'Matcha%',
        ]);
        $this->assertSame('product_not_found', $literal['status']);
        $matcha->update(['name' => 'Green bar special']);
        $renamed = $tool->read([
            'metric' => 'product_sales',
            'product_query' => 'Matcha Latte',
        ]);
        $this->assertSame(1, $renamed['totals']['quantity']);
        $this->assertSame('Green bar special', $renamed['data'][0]['product_name']);
    }

    public function test_product_daily_sales_subtract_refunds_by_processing_date_including_prior_sales(): void
    {
        $matcha = $this->product('Matcha Latte');
        foreach (['2026-11-20' => 5, '2026-10-31' => 2] as $date => $quantity) {
            $order = $this->productOrder($matcha, $quantity, $date);
            Refund::create([
                'order_id' => $order->id,
                'requested_by' => $this->owner->id,
                'approved_by' => $this->owner->id,
                'amount' => $order->total_amount,
                'reason' => 'Refund penuh',
                'status' => 'approved',
                'processed_at' => '2026-11-24 12:00:00',
            ]);
        }
        $tool = new BusinessAnalyticsTool($this->owner, '2026-11-01', '2026-11-25', null);
        $result = $tool->read([
            'metric' => 'product_sales',
            'product_query' => 'Matcha Latte',
            'group_by' => 'day',
        ]);
        $this->assertCount(25, $result['data']);
        $this->assertSame(5, $result['totals']['sold_quantity']);
        $this->assertSame(7, $result['totals']['refunded_quantity']);
        $this->assertSame(-2, $result['totals']['quantity']);
        $this->assertSame(-44000, $result['totals']['amount']);
        $this->assertSame(
            5,
            collect($result['data'])->firstWhere('date', '2026-11-20')['quantity'],
        );
        $this->assertSame(
            -7,
            collect($result['data'])->firstWhere('date', '2026-11-24')['quantity'],
        );
        $this->assertSame(
            0,
            collect($result['data'])->firstWhere('date', '2026-11-25')['quantity'],
        );
    }

    public function test_product_comparison_returns_backend_changes_and_no_percentage_for_zero_baseline(): void
    {
        $matcha = $this->product('Matcha Latte');
        $this->productOrder($matcha, 6, '2026-11-12');
        $this->productOrder($matcha, 9, '2026-11-20');
        $tool = new BusinessAnalyticsTool($this->owner, '2026-11-18', '2026-11-24', null);
        $result = $tool->read([
            'metric' => 'product_sales',
            'product_query' => 'Matcha Latte',
            'group_by' => 'branch',
            'compare_previous' => true,
        ]);
        $this->assertSame(
            ['start' => '2026-11-11', 'end' => '2026-11-17'],
            $result['comparison']['period'],
        );
        $this->assertSame(6, $result['comparison']['totals']['quantity']);
        $this->assertSame(3, $result['comparison']['changes']['quantity_change']);
        $this->assertSame(
            50.0,
            $result['comparison']['changes']['quantity_change_percent'],
        );
        $this->assertSame(3, $result['comparison']['rows'][0]['quantity_change']);
        $newProduct = $this->product('New tea');
        $this->productOrder($newProduct, 2, '2026-11-20');
        $zero = $tool->read([
            'metric' => 'product_sales',
            'product_id' => $newProduct->id,
            'compare_previous' => true,
        ]);
        $this->assertNull($zero['comparison']['changes']['quantity_change_percent']);
    }

    public function test_scope_can_change_in_same_thread_without_rewriting_previous_answers(): void
    {
        $this->actingAs($this->owner)->post('/intelligence/messages', $this->question());
        BusinessAssistant::fake(['Jawaban periode pertama.'])->preventStrayPrompts();
        $first = AiMessage::where('role', 'assistant')->firstOrFail();
        (new AnswerBusinessQuestion($first->id))->handle();
        $oldEvidence = $first->fresh()->evidence;
        $this->post(
            '/intelligence/messages',
            $this->question([
                'thread_id' => $first->ai_thread_id,
                'message' => 'Bagaimana pada periode ini?',
                'start' => '2026-11-18',
                'end' => '2026-11-24',
                'branch_id' => $this->branch->id,
            ]),
        )->assertRedirect('/intelligence?thread=' . $first->ai_thread_id);
        $second = AiMessage::where('status', 'pending')->firstOrFail();
        $this->assertSame('2026-11-18', $second->scope['start']);
        $this->assertSame($this->branch->id, $second->scope['branch_id']);
        BusinessAssistant::fake(['Jawaban periode kedua.'])->preventStrayPrompts();
        (new AnswerBusinessQuestion($second->id))->handle();
        $this->assertSame('2026-11-18', $second->fresh()->evidence[0]['period']['start']);
        $this->assertSame($this->branch->name, $second->fresh()->evidence[0]['branch']);
        $this->assertSame($oldEvidence, $first->fresh()->evidence);
        $this->assertSame('2026-11-01', $first->fresh()->scope['start']);
        $this->assertDatabaseCount('ai_threads', 1);
        $this->assertDatabaseCount('ai_messages', 4);
    }

    public function test_follow_up_cannot_silently_reuse_old_dates_but_explicit_date_requests_work(): void
    {
        $matcha = $this->product('Matcha Latte');
        $this->productOrder($matcha, 5, '2026-11-12');
        $this->productOrder($matcha, 2, '2026-11-20');
        foreach (
            [
                [
                    'question' =>
                        'Pada periode yang baru dipilih, tiap cabangnya berapa?',
                    'expected' => 2,
                ],
                [
                    'question' => 'Berapa dari 2026-11-01 sampai 2026-11-25?',
                    'expected' => 7,
                ],
            ]
            as $case
        ) {
            $this->actingAs($this->owner)->post(
                '/intelligence/messages',
                $this->question([
                    'start' => '2026-11-18',
                    'end' => '2026-11-24',
                    'message' => $case['question'],
                ]),
            );
            BusinessAssistant::fake([
                new ToolCall('product-call', 'business_analytics', [
                    'metric' => 'product_sales',
                    'product_query' => 'Matcha Latte',
                    'group_by' => 'branch',
                    'start' => '2026-11-01',
                    'end' => '2026-11-25',
                ]),
                'Jawaban dari data produk.',
            ])->preventStrayPrompts();
            $answer = AiMessage::where('status', 'pending')->firstOrFail();
            (new AnswerBusinessQuestion($answer->id))->handle();
            $this->assertSame('completed', $answer->fresh()->status);
            $this->assertSame(
                $case['expected'],
                $answer->fresh()->evidence[1]['totals']['quantity'],
            );
        }
    }
}
