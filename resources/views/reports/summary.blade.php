<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan bisnis VELTRIX</title>
    <style>
        @page { margin: 38px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #22332a; line-height: 1.6; }
        h1 { font-size: 23px; color: #166348; margin: 0 0 4px; }
        h2 { font-size: 14px; margin: 25px 0 10px; }
        p { margin: 5px 0; color: #59665e; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; color: #59665e; font-weight: normal; }
        td, th { padding: 8px 0; border-bottom: 1px solid #e5eae7; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        .num { text-align: right; }
        .note { margin-top: 20px; font-size: 9px; }
    </style>
</head>
<body>
    @php
        $money = fn ($value) => 'Rp ' . number_format($value, 0, ',', '.');
        $labels = [
            'gross' => 'Penjualan kotor', 'refunds' => 'Refund diproses',
            'net' => 'Penjualan bersih', 'transactions' => 'Transaksi',
            'units' => 'Produk bersih terjual', 'average' => 'Rata-rata transaksi',
            'cogs' => 'Biaya bahan terjual', 'gross_profit' => 'Estimasi laba kotor',
            'expenses' => 'Pengeluaran disetujui', 'payroll' => 'Payroll disetujui / dibayar',
            'profit' => 'Estimasi laba operasional', 'active_branches' => 'Cabang aktif',
        ];
    @endphp
    <h1>VELTRIX</h1>
    <h2>Laporan bisnis</h2>
    <p>{{ $scope }}</p>
    <p>Periode {{ $filters['start'] }} sampai {{ $filters['end'] }} · WIB</p>
    <table>
        <thead><tr><th>Metrik</th><th class="num">Nilai</th></tr></thead>
        <tbody>
        @foreach ($report['metrics'] as $key => $value)
            <tr>
                <td>{{ $labels[$key] ?? $key }}</td>
                <td class="num">{{ in_array($key, ['transactions', 'units', 'active_branches']) ? number_format($value, 0, ',', '.') : $money($value) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <h2>Performa cabang</h2>
    <table>
        <thead><tr><th>Cabang</th><th class="num">Transaksi</th><th class="num">Penjualan bersih</th></tr></thead>
        <tbody>
        @foreach ($report['branches'] as $branch)
            <tr><td>{{ $branch['name'] }}</td><td class="num">{{ $branch['transactions'] }}</td><td class="num">{{ $money($branch['amount']) }}</td></tr>
        @endforeach
        </tbody>
    </table>
    <p class="note">Biaya persediaan memakai rata-rata tertimbang per cabang dan snapshot pada penjualan. Pembelian belum diterima tidak menambah stok. Estimasi belum mencakup depresiasi, pajak, dan penyesuaian akuntansi. Refund tidak mengembalikan bahan terpakai.</p>
</body>
</html>
