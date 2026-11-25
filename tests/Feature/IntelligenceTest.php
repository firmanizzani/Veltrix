<?php

namespace Tests\Feature;

use App\Jobs\AnswerBusinessQuestion;
use App\Jobs\GenerateBusinessInsights;
use App\Models\AiMessage;
use App\Models\AiThread;
use App\Models\Branch;
use App\Models\BusinessInsight;
use App\Models\CashierShift;
use App\Models\Expense;
use App\Models\Ingredient;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Order;
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

    public function test_only_owner_can_access_chat_or_run_analysis(): void
    {
        $manager = User::factory()->create([
            'role' => 'manager',
            'branch_id' => $this->branch->id,
        ]);
        foreach ([$manager, $this->cashier] as $user) {
            $this->actingAs($user)->get('/intelligence')->assertForbidden();
            $this->post('/intelligence/messages', $this->question())->assertForbidden();
            $this->post('/intelligence/generate')->assertForbidden();
        }
        $this->assertDatabaseCount('ai_messages', 0);
        Queue::assertNothingPushed();
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
}
