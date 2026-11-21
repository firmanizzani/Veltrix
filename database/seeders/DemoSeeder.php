<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\BranchProduct;
use App\Models\CashierShift;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Ingredient;
use App\Models\Inventory;
use App\Models\Payment;
use App\Models\Payroll;
use App\Models\Product;
use App\Models\RecipeVersion;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\Checkout;
use App\Services\InventoryLedger;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment(['local', 'testing'])) {
            throw new \RuntimeException(
                'Demo seeder hanya boleh dijalankan di local/testing.',
            );
        }
        if (User::exists() || Product::exists()) {
            $this->command?->warn(
                'Data sudah ada. Demo tidak diulang agar tidak menimpa operasional.',
            );

            return;
        }
        DB::transaction(function () {
            $password = 'VeltrixDemo2026!';
            $owner = User::create([
                'name' => 'Firman Izzani',
                'email' => 'owner@veltrix.test',
                'password' => $password,
                'role' => 'owner',
            ]);
            $categories = collect([
                'Coffee',
                'Non-Coffee',
                'Tea',
                'Food',
                'Pastry',
            ])->mapWithKeys(fn($name) => [$name => Category::create(['name' => $name])]);
            $ingredients = collect([
                ['Biji kopi espresso', 'g', 180],
                ['Susu segar', 'ml', 22],
                ['Gula aren', 'ml', 45],
                ['Bubuk matcha', 'g', 800],
                ['Cokelat', 'g', 250],
                ['Teh', 'g', 150],
                ['Sirup vanilla', 'ml', 80],
                ['Croissant siap panggang', 'pcs', 9500],
                ['Roti sandwich', 'pcs', 12000],
                ['Bahan makanan porsi', 'pcs', 16000],
            ])->map(
                fn($row) => Ingredient::create([
                    'name' => $row[0],
                    'unit' => $row[1],
                    'unit_cost' => $row[2],
                ]),
            );
            $coffee =
                'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=500&q=80';
            $latte =
                'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?auto=format&fit=crop&w=500&q=80';
            $tea =
                'https://images.unsplash.com/photo-1544787219-7f47ccb76574?auto=format&fit=crop&w=500&q=80';
            $pastry =
                'https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&w=500&q=80';
            $food =
                'https://images.unsplash.com/photo-1528735602780-2552fd46c7af?auto=format&fit=crop&w=500&q=80';
            $menu = [
                [
                    'Kopi Susu Gula Aren',
                    'Coffee',
                    22000,
                    [0 => 18, 1 => 150, 2 => 20],
                    $latte,
                ],
                ['Americano', 'Coffee', 20000, [0 => 18], $coffee],
                ['Caffè Latte', 'Coffee', 26000, [0 => 18, 1 => 180], $latte],
                ['Cappuccino', 'Coffee', 26000, [0 => 18, 1 => 120], $coffee],
                ['Espresso', 'Coffee', 18000, [0 => 18], $coffee],
                ['Vanilla Latte', 'Coffee', 29000, [0 => 18, 1 => 160, 6 => 15], $latte],
                ['Mocha', 'Coffee', 30000, [0 => 18, 1 => 150, 4 => 20], $coffee],
                ['Flat White', 'Coffee', 27000, [0 => 18, 1 => 100], $latte],
                ['Matcha Latte', 'Non-Coffee', 30000, [1 => 180, 3 => 5], $tea],
                ['Chocolate', 'Non-Coffee', 27000, [1 => 180, 4 => 30], $latte],
                ['Vanilla Milk', 'Non-Coffee', 24000, [1 => 200, 6 => 20], $latte],
                ['Matcha Aren', 'Non-Coffee', 32000, [1 => 160, 3 => 5, 2 => 15], $tea],
                ['Original Tea', 'Tea', 16000, [5 => 4], $tea],
                ['Milk Tea', 'Tea', 22000, [5 => 4, 1 => 120], $tea],
                ['Vanilla Tea', 'Tea', 22000, [5 => 4, 6 => 15], $tea],
                ['Chicken Sandwich', 'Food', 35000, [8 => 1], $food],
                ['Rice Bowl', 'Food', 38000, [9 => 1], $food],
                ['Toast', 'Food', 28000, [8 => 1], $food],
                ['Butter Croissant', 'Pastry', 24000, [7 => 1], $pastry],
                ['Chocolate Croissant', 'Pastry', 28000, [7 => 1, 4 => 10], $pastry],
            ];
            foreach ($menu as $index => [$name, $category, $price, $recipe, $image]) {
                $product = Product::create([
                    'name' => $name,
                    'sku' =>
                        'VTX-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'category_id' => $categories[$category]->id,
                    'base_price' => $price,
                    'image_url' => $image,
                    'description' => 'Menu demonstrasi Veltrix Coffee',
                ]);
                $version = RecipeVersion::create([
                    'product_id' => $product->id,
                    'version' => 1,
                ]);
                foreach ($recipe as $ingredientIndex => $quantity) {
                    $version->items()->create([
                        'ingredient_id' => $ingredients[$ingredientIndex]->id,
                        'quantity' => $quantity,
                    ]);
                }
            }
            $ledger = app(InventoryLedger::class);
            $checkout = app(Checkout::class);
            $today = Carbon::now();
            foreach (
                [
                    ['DPK', 'Depok', 'Jl. Margonda Raya No. 88'],
                    ['JKT', 'Jakarta', 'Jl. Tebet Raya No. 21'],
                    ['BGR', 'Bogor', 'Jl. Pajajaran No. 17'],
                ]
                as $branchIndex => [$code, $city, $address]
            ) {
                $branch = Branch::firstOrCreate(
                    ['code' => $code],
                    [
                        'name' => 'Veltrix Coffee — ' . $city,
                        'city' => $city,
                        'address' => $address,
                        'phone' => '021-555010' . ($branchIndex + 1),
                    ],
                );
                $manager = User::create([
                    'name' => ['Nadia Putri', 'Raka Pratama', 'Dinda Maharani'][
                        $branchIndex
                    ],
                    'email' => strtolower($city) . '.manager@veltrix.test',
                    'password' => $password,
                    'role' => 'manager',
                    'branch_id' => $branch->id,
                ]);
                $cashiers = [];
                for ($i = 1; $i <= 2; $i++) {
                    $cashiers[] = User::create([
                        'name' => $city . ' Kasir ' . $i,
                        'email' => strtolower($city) . '.kasir' . $i . '@veltrix.test',
                        'password' => $password,
                        'role' => 'cashier',
                        'branch_id' => $branch->id,
                    ]);
                }
                foreach (Product::all() as $product) {
                    BranchProduct::create([
                        'branch_id' => $branch->id,
                        'product_id' => $product->id,
                        'price' => $product->base_price + $branchIndex * 1000,
                    ]);
                }
                foreach ($ingredients as $ingredient) {
                    $stock = Inventory::create([
                        'branch_id' => $branch->id,
                        'ingredient_id' => $ingredient->id,
                        'minimum' => $ingredient->unit === 'pcs' ? 12 : 500,
                    ]);
                    $ledger->move(
                        $owner,
                        $branch->id,
                        $ingredient->id,
                        $ingredient->unit === 'pcs' ? 300 : 50000,
                        'opening',
                        'Saldo awal data demonstrasi',
                    );
                }
                foreach ([$manager, ...$cashiers] as $index => $person) {
                    $employee = Employee::create([
                        'code' => $code . '-' . ($index + 1),
                        'name' => $person->name,
                        'branch_id' => $branch->id,
                        'position' => $index === 0 ? 'Branch Manager' : 'Kasir / Barista',
                        'joined_at' => $today->copy()->subMonths(6)->toDateString(),
                    ]);
                    WorkSchedule::create([
                        'employee_id' => $employee->id,
                        'work_date' => $today->toDateString(),
                        'start_time' => '08:00',
                        'end_time' => '16:00',
                    ]);
                    Payroll::create([
                        'employee_id' => $employee->id,
                        'branch_id' => $branch->id,
                        'period' => $today->copy()->startOfMonth()->toDateString(),
                        'base_salary' => $index === 0 ? 5500000 : 3500000,
                        'allowance' => 400000,
                        'bonus' => 100000,
                        'deduction' => 0,
                        'net_pay' => $index === 0 ? 6000000 : 4000000,
                        'status' => 'draft',
                    ]);
                }
                Expense::create([
                    'branch_id' => $branch->id,
                    'created_by' => $manager->id,
                    'category' => 'Listrik',
                    'amount' => 650000 + $branchIndex * 120000,
                    'expense_date' => $today->toDateString(),
                    'description' => 'Tagihan listrik bulan berjalan (demonstrasi)',
                    'approval_status' => 'pending',
                ]);
                $days = array_unique([...range(0, 6), ...range(14, 84, 7)]);
                foreach ($days as $dayIndex => $daysAgo) {
                    Carbon::setTestNow(
                        $today
                            ->copy()
                            ->subDays($daysAgo)
                            ->setTime(10 + $branchIndex, 15),
                    );
                    $cashier = $cashiers[$dayIndex % 2];
                    $shift = CashierShift::create([
                        'branch_id' => $branch->id,
                        'cashier_id' => $cashier->id,
                        'opening_cash' => 200000,
                        'opened_at' => now(),
                    ]);
                    for ($sale = 0; $sale < 2; $sale++) {
                        $menus = BranchProduct::where('branch_id', $branch->id)
                            ->orderBy('id')
                            ->get();
                        $selected =
                            $menus[
                                ($dayIndex + $sale * 3 + $branchIndex) % $menus->count()
                            ];
                        $checkout->complete($cashier, [
                            'idempotency_key' => (string) Str::uuid(),
                            'items' => [
                                [
                                    'branch_product_id' => $selected->id,
                                    'quantity' => 1 + ($dayIndex % 3),
                                    'expected_price' => $selected->price,
                                ],
                            ],
                            'method' => $sale ? 'qris' : 'cash',
                            'tendered' => 200000,
                            'customer_name' => null,
                            'notes' => 'Data demonstrasi',
                        ]);
                    }
                    $cash = Payment::where('method', 'cash')
                        ->whereHas('order', fn($q) => $q->where('shift_id', $shift->id))
                        ->sum('amount');
                    $shift->update([
                        'expected_cash' => 200000 + $cash,
                        'actual_cash' => 200000 + $cash,
                        'status' => 'closed',
                        'closed_at' => now()->addHours(6),
                    ]);
                }
                Carbon::setTestNow();
                $stock = Inventory::where('branch_id', $branch->id)
                    ->where('ingredient_id', $ingredients[3]->id)
                    ->first();
                $ledger->move(
                    $manager,
                    $branch->id,
                    $ingredients[3]->id,
                    240 - (float) $stock->quantity,
                    'stocktake',
                    'Opname awal demonstrasi: batas pemesanan ulang',
                );
            }
            Supplier::create([
                'name' => 'Nusantara Coffee Roastery',
                'phone' => '021-5550201',
                'email' => 'order@example.test',
            ]);
            Supplier::create(['name' => 'Segar Dairy Supply', 'phone' => '021-5550202']);
        });
        Carbon::setTestNow();
        $this->command?->info(
            'Demo tersedia: owner@veltrix.test / VeltrixDemo2026! (khusus development).',
        );
    }
}
