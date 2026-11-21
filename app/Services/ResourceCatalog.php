<?php
namespace App\Services;

use App\Enums\Role;
use App\Models\{AuditLog, Branch, BranchProduct, CashierShift, Category, Employee, Expense, Ingredient, Inventory, InventoryMovement, Order, Payroll, PriceRequest, Product, PurchaseOrder, RecipeVersion, Refund, StockTransfer, Supplier, User, WorkSchedule};
use Illuminate\Support\Facades\Gate;

class ResourceCatalog
{
    private function field(string $key, string $label, string $type = 'text', string $rules = 'required|string|max:255', ?string $source = null): array
    {
        return compact('key', 'label', 'type', 'rules', 'source');
    }

    public function definition(string $key): array
    {
        $f = fn (...$args) => $this->field(...$args);
        $branch = $f('branch_id', 'Cabang', 'select', 'required|integer|exists:branches,id', 'branches');
        $active = $f('is_active', 'Aktif', 'checkbox', 'required|boolean');
        $ingredient = $f('ingredient_id', 'Bahan', 'select', 'required|integer|exists:ingredients,id', 'ingredients');
        $definitions = [
            'branches' => [Branch::class, 'Cabang', 'Satu standar operasional, di setiap kedai.', ['name' => 'Nama cabang', 'city' => 'Kota', 'code' => 'Kode', 'users_count' => 'Akun', 'is_active' => 'Status'], [$f('code', 'Kode cabang', 'text', 'required|alpha_dash|max:12'), $f('name', 'Nama cabang'), $f('city', 'Kota'), $f('address', 'Alamat', 'textarea'), $f('phone', 'Telepon', 'text', 'nullable|string|max:30'), $active], [], 'owner', 'owner'],
            'users' => [User::class, 'Pengguna', 'Atur peran dan penugasan akses tim.', ['name' => 'Nama', 'email' => 'Email', 'role' => 'Peran', 'branch.name' => 'Cabang', 'is_active' => 'Status'], [$f('name', 'Nama lengkap'), $f('email', 'Email', 'email', 'required|email|max:255'), $f('password', 'Password awal / baru', 'password', 'nullable|string|min:12|confirmed'), $f('password_confirmation', 'Konfirmasi password', 'password', 'nullable|string'), $f('role', 'Peran', 'select', 'required|in:manager,cashier', 'roles'), $branch, $active], ['branch'], 'owner', 'owner'],
            'categories' => [Category::class, 'Kategori menu', 'Kelompokkan menu agar mudah ditemukan saat melayani.', ['name' => 'Kategori'], [$f('name', 'Nama kategori')], [], 'owner', 'owner'],
            'products' => [Product::class, 'Produk & menu', 'Katalog menu untuk seluruh kedai Veltrix.', ['name' => 'Produk', 'sku' => 'SKU', 'category.name' => 'Kategori', 'base_price' => 'Harga dasar', 'is_active' => 'Status'], [$f('name', 'Nama produk'), $f('sku', 'SKU', 'text', 'required|string|max:32'), $f('category_id', 'Kategori', 'select', 'required|integer|exists:categories,id', 'categories'), $f('base_price', 'Harga dasar (Rp)', 'number', 'required|integer|min:0|max:10000000'), $f('description', 'Deskripsi', 'textarea', 'nullable|string|max:2000'), $f('image_url', 'URL foto produk (HTTPS)', 'url', 'nullable|url:https|max:255'), $active], ['category'], 'operations', 'owner'],
            'branch-prices' => [BranchProduct::class, 'Harga & ketersediaan', 'Harga berlaku per cabang. Manager dapat mengatur ketersediaan.', ['product.name' => 'Produk', 'branch.name' => 'Cabang', 'price' => 'Harga', 'is_available' => 'Tersedia'], [$branch, $f('product_id', 'Produk', 'select', 'required|integer|exists:products,id', 'products'), $f('price', 'Harga (Rp)', 'number', 'required|integer|min:0|max:10000000'), $f('is_available', 'Tersedia', 'checkbox', 'required|boolean')], ['product', 'branch'], 'operations', 'owner'],
            'ingredients' => [Ingredient::class, 'Bahan baku', 'Satuan dasar yang konsisten untuk resep dan persediaan.', ['name' => 'Bahan', 'unit' => 'Satuan', 'unit_cost' => 'Biaya per satuan'], [$f('name', 'Nama bahan'), $f('unit', 'Satuan dasar', 'select', 'required|in:g,ml,pcs', 'units'), $f('unit_cost', 'Biaya per satuan dasar (Rp)', 'number', 'required|integer|min:0|max:10000000')], [], 'operations', 'owner'],
            'recipes' => [RecipeVersion::class, 'Resep menu', 'Versi resep lama tetap tersimpan untuk transaksi historis.', ['product.name' => 'Produk', 'version' => 'Versi', 'items_count' => 'Bahan', 'created_at' => 'Dibuat'], [$f('product_id', 'Produk', 'select', 'required|integer|exists:products,id', 'products'), $f('items', 'Komposisi bahan', 'recipe', 'required|array|min:1|max:30', 'ingredients')], ['product', 'items.ingredient'], 'operations', 'owner'],
            'inventory' => [Inventory::class, 'Persediaan cabang', 'Pantau bahan tersedia dan batas pemesanan ulang.', ['ingredient.name' => 'Bahan', 'branch.name' => 'Cabang', 'quantity' => 'Stok', 'ingredient.unit' => 'Satuan', 'minimum' => 'Batas minimum'], [$branch, $ingredient, $f('minimum', 'Batas minimum', 'number', 'required|numeric|min:0|max:100000000')], ['ingredient', 'branch'], 'operations', 'operations'],
            'movements' => [InventoryMovement::class, 'Riwayat stok', 'Setiap perubahan persediaan memiliki sumber dan penanggung jawab.', ['inventory.ingredient.name' => 'Bahan', 'inventory.branch.name' => 'Cabang', 'type' => 'Jenis', 'quantity' => 'Perubahan', 'balance' => 'Saldo', 'reason' => 'Alasan', 'created_at' => 'Waktu'], [], ['inventory.ingredient', 'inventory.branch', 'actor'], 'operations', 'none'],
            'suppliers' => [Supplier::class, 'Supplier', 'Mitra pemasok bahan baku kedai.', ['name' => 'Supplier', 'phone' => 'Telepon', 'email' => 'Email', 'is_active' => 'Status'], [$f('name', 'Nama supplier'), $f('phone', 'Telepon', 'text', 'nullable|string|max:30'), $f('email', 'Email', 'email', 'nullable|email|max:255'), $active], [], 'operations', 'operations'],
            'purchases' => [PurchaseOrder::class, 'Pembelian bahan', 'Stok bertambah setelah penerimaan dikonfirmasi.', ['number' => 'Nomor pembelian', 'branch.name' => 'Cabang', 'supplier.name' => 'Supplier', 'total_amount' => 'Total', 'status' => 'Status'], [$branch, $f('supplier_id', 'Supplier', 'select', 'required|integer|exists:suppliers,id', 'suppliers'), $f('items', 'Bahan yang dipesan', 'purchase', 'required|array|min:1|max:50', 'ingredients')], ['branch', 'supplier', 'items.ingredient'], 'operations', 'operations'],
            'transfers' => [StockTransfer::class, 'Transfer stok', 'Pengiriman dan penerimaan stok antar cabang dicatat terpisah.', ['source.name' => 'Dari', 'destination.name' => 'Tujuan', 'ingredient.name' => 'Bahan', 'quantity' => 'Jumlah', 'status' => 'Status'], [$f('source_branch_id', 'Cabang asal', 'select', 'required|integer|exists:branches,id', 'branches'), $f('destination_branch_id', 'Cabang tujuan', 'select', 'required|integer|different:source_branch_id|exists:branches,id', 'all_branches'), $ingredient, $f('quantity', 'Jumlah', 'number', 'required|numeric|gt:0|max:100000000')], ['source', 'destination', 'ingredient'], 'operations', 'operations'],
            'employees' => [Employee::class, 'Karyawan', 'Data tim kedai, terpisah dari akun login.', ['name' => 'Karyawan', 'code' => 'ID karyawan', 'position' => 'Jabatan', 'branch.name' => 'Cabang', 'is_active' => 'Status'], [$f('code', 'ID karyawan'), $f('name', 'Nama lengkap'), $branch, $f('position', 'Jabatan'), $f('phone', 'Telepon', 'text', 'nullable|string|max:30'), $f('joined_at', 'Tanggal bergabung', 'date', 'required|date'), $active], ['branch'], 'operations', 'operations'],
            'schedules' => [WorkSchedule::class, 'Jadwal kerja', 'Penugasan kerja harian tim di cabang.', ['employee.name' => 'Karyawan', 'employee.branch.name' => 'Cabang', 'work_date' => 'Tanggal', 'start_time' => 'Mulai', 'end_time' => 'Selesai'], [$f('employee_id', 'Karyawan', 'select', 'required|integer|exists:employees,id', 'employees'), $f('work_date', 'Tanggal', 'date', 'required|date'), $f('start_time', 'Mulai', 'time', 'required|date_format:H:i'), $f('end_time', 'Selesai (hari yang sama)', 'time', 'required|date_format:H:i|after:start_time')], ['employee.branch'], 'operations', 'operations'],
            'expenses' => [Expense::class, 'Pengeluaran', 'Catat beban operasional dan ajukan persetujuan.', ['description' => 'Keperluan', 'branch.name' => 'Cabang', 'category' => 'Kategori', 'amount' => 'Nominal', 'expense_date' => 'Tanggal', 'approval_status' => 'Persetujuan'], [$branch, $f('category', 'Kategori', 'select', 'required|in:Sewa,Listrik,Air,Internet,Kebersihan,Maintenance,Marketing,Transportasi,Lainnya', 'expense_categories'), $f('amount', 'Nominal (Rp)', 'number', 'required|integer|min:1|max:1000000000'), $f('expense_date', 'Tanggal', 'date', 'required|date'), $f('description', 'Keperluan', 'textarea', 'required|string|max:2000'), $f('receipt', 'Bukti pengeluaran', 'file', 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120')], ['branch'], 'operations', 'operations'],
            'payroll' => [Payroll::class, 'Payroll', 'Gaji bersih dihitung dari komponen, lalu disetujui dan dicatat dibayar.', ['employee.name' => 'Karyawan', 'branch.name' => 'Cabang', 'period' => 'Periode', 'net_pay' => 'Gaji bersih', 'status' => 'Status'], [$f('employee_id', 'Karyawan', 'select', 'required|integer|exists:employees,id', 'employees'), $f('period', 'Awal periode bulanan', 'date', 'required|date_format:Y-m-d'), $f('base_salary', 'Gaji pokok (Rp)', 'number', 'required|integer|min:0|max:100000000'), $f('allowance', 'Tunjangan (Rp)', 'number', 'required|integer|min:0|max:100000000'), $f('bonus', 'Bonus (Rp)', 'number', 'required|integer|min:0|max:100000000'), $f('deduction', 'Potongan (Rp)', 'number', 'required|integer|min:0|max:100000000')], ['employee', 'branch'], 'owner', 'owner'],
            'orders' => [Order::class, 'Transaksi penjualan', 'Riwayat transaksi sesuai akses dan cabang Anda.', ['order_number' => 'Transaksi', 'branch.name' => 'Cabang', 'cashier.name' => 'Kasir', 'total_amount' => 'Total', 'payment.method' => 'Pembayaran', 'status' => 'Status', 'completed_at' => 'Waktu'], [], ['branch', 'cashier', 'payment', 'refund'], 'all', 'none'],
            'shifts' => [CashierShift::class, 'Sesi kasir', 'Saldo awal, penerimaan tunai, dan rekonsiliasi akhir shift.', ['cashier.name' => 'Kasir', 'branch.name' => 'Cabang', 'opened_at' => 'Dibuka', 'opening_cash' => 'Kas awal', 'expected_cash' => 'Kas seharusnya', 'actual_cash' => 'Kas aktual', 'status' => 'Status'], [], ['cashier', 'branch'], 'all', 'none'],
            'refunds' => [Refund::class, 'Pengembalian dana', 'Refund penuh melalui permintaan dan persetujuan. Bahan terpakai tidak dikembalikan otomatis.', ['order.order_number' => 'Transaksi', 'order.branch.name' => 'Cabang', 'amount' => 'Nominal', 'reason' => 'Alasan', 'status' => 'Status'], [], ['order.branch', 'order.payment'], 'all', 'none'],
            'price-requests' => [PriceRequest::class, 'Persetujuan harga', 'Manager mengajukan perubahan; owner menetapkan harga final.', ['branchProduct.product.name' => 'Produk', 'branchProduct.branch.name' => 'Cabang', 'price' => 'Harga usulan', 'reason' => 'Alasan', 'status' => 'Status'], [$f('branch_product_id', 'Menu cabang', 'select', 'required|integer|exists:branch_products,id', 'branch_products'), $f('price', 'Harga usulan (Rp)', 'number', 'required|integer|min:0|max:10000000'), $f('reason', 'Alasan', 'textarea', 'required|string|max:1000')], ['branchProduct.product', 'branchProduct.branch'], 'operations', 'operations'],
            'audit' => [AuditLog::class, 'Audit log', 'Jejak perubahan penting untuk penelusuran operasional.', ['actor.name' => 'Pengguna', 'action' => 'Aktivitas', 'entity_type' => 'Modul', 'entity_id' => 'ID', 'created_at' => 'Waktu'], [], ['actor'], 'owner', 'none'],
        ];
        abort_unless(isset($definitions[$key]), 404);
        [$model, $title, $description, $columns, $fields, $relations, $read, $write] = $definitions[$key];
        return compact('key', 'model', 'title', 'description', 'columns', 'fields', 'relations', 'read', 'write');
    }

    public function allowed(User $user, string $level): bool
    {
        return match ($level) { 'all' => true, 'owner' => $user->isOwner(), 'operations' => $user->role !== Role::Cashier, default => false };
    }

    public function options(User $user): array
    {
        $access = new BranchAccess;
        $pairs = fn ($collection) => $collection->map(fn ($row) => ['value' => $row->id, 'label' => $row->name])->values();
        return [
            'branches' => $pairs($user->isOwner() ? Branch::all() : Branch::whereKey($user->branch_id)->get()),
            'all_branches' => $pairs(Branch::where('is_active', true)->get()),
            'categories' => $pairs(Category::all()), 'products' => $pairs(Product::all()), 'ingredients' => Ingredient::all()->map(fn ($row) => ['value' => $row->id, 'label' => $row->name.' ('.$row->unit.')']),
            'suppliers' => $pairs(Supplier::where('is_active', true)->get()),
            'employees' => $pairs($access->scope(Employee::query(), $user)->where('is_active', true)->get()),
            'branch_products' => $access->scope(BranchProduct::with('branch', 'product'), $user)->get()->map(fn ($row) => ['value' => $row->id, 'label' => $row->product->name.' / '.$row->branch->city]),
            'roles' => [['value' => 'manager', 'label' => 'Branch Manager'], ['value' => 'cashier', 'label' => 'Kasir']],
            'units' => collect(['g', 'ml', 'pcs'])->map(fn ($v) => ['value' => $v, 'label' => $v]),
            'expense_categories' => collect(['Sewa', 'Listrik', 'Air', 'Internet', 'Kebersihan', 'Maintenance', 'Marketing', 'Transportasi', 'Lainnya'])->map(fn ($v) => ['value' => $v, 'label' => $v]),
        ];
    }
}
