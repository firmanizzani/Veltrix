<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchProduct;
use App\Models\CashierShift;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\RecipeVersion;
use App\Models\User;
use App\Services\InventoryLedger;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PostgresConcurrencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (
            config('database.default') !== 'pgsql' ||
            config('database.connections.pgsql.database') !== 'veltrix_test' ||
            config('database.connections.pgsql.host') !== '127.0.0.1'
        ) {
            $this->markTestSkipped(
                'Gunakan phpunit.pgsql.xml dan PostgreSQL lokal terisolasi.',
            );
        }
        $this->artisan('migrate:fresh', ['--force' => true])->assertExitCode(0);
    }

    public function test_two_cashiers_cannot_sell_the_same_last_portion(): void
    {
        $this->runSimultaneousCheckouts(false);
    }

    public function test_simultaneous_replays_create_only_one_order(): void
    {
        $this->runSimultaneousCheckouts(true);
    }

    private function runSimultaneousCheckouts(bool $duplicate): void
    {
        $branch = Branch::create([
            'code' => 'TEST',
            'name' => 'Concurrent test',
            'city' => 'Depok',
            'address' => 'Test only',
        ]);
        $owner = User::factory()->create();
        $cashiers = User::factory()
            ->count(2)
            ->create([
                'role' => 'cashier',
                'branch_id' => $branch->id,
            ]);
        foreach ($cashiers as $cashier) {
            CashierShift::create([
                'cashier_id' => $cashier->id,
                'branch_id' => $branch->id,
                'opening_cash' => 0,
                'opened_at' => now(),
            ]);
        }
        $category = Category::create(['name' => 'Coffee']);
        $product = Product::create([
            'name' => 'Americano',
            'sku' => 'TEST-1',
            'category_id' => $category->id,
            'base_price' => 22000,
        ]);
        $menu = BranchProduct::create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'price' => 22000,
        ]);
        $ingredient = Ingredient::create([
            'name' => 'Last beans',
            'unit' => 'g',
            'unit_cost' => 100,
        ]);
        $recipe = RecipeVersion::create(['product_id' => $product->id, 'version' => 1]);
        $recipe->items()->create(['ingredient_id' => $ingredient->id, 'quantity' => 18]);
        app(InventoryLedger::class)->move(
            $owner,
            $branch->id,
            $ingredient->id,
            18,
            'opening',
            'One portion only',
        );
        $key = (string) Str::uuid();
        $start = (string) (microtime(true) + 1.5);
        $processes = [];
        foreach ([0, 1] as $index) {
            $process = new Process(
                [
                    PHP_BINARY,
                    base_path('tests/Support/checkout-worker.php'),
                    (string) $cashiers[$duplicate ? 0 : $index]->id,
                    (string) $menu->id,
                    $duplicate ? $key : (string) Str::uuid(),
                    $start,
                ],
                base_path(),
            );
            $process->setTimeout(20);
            $process->start();
            $processes[] = $process;
        }
        $results = [];
        foreach ($processes as $process) {
            $process->wait();
            $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
            $results[] = json_decode(
                $process->getOutput(),
                true,
                flags: JSON_THROW_ON_ERROR,
            );
        }
        $this->assertSame(1, Order::count());
        $this->assertSame(0.0, (float) Inventory::first()->quantity);
        $completed = array_filter(
            $results,
            fn($result) => $result['result'] === 'completed',
        );
        $this->assertCount($duplicate ? 2 : 1, $completed);
    }
}
