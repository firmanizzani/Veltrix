# Veltrix
## Multi-Branch Coffee Shop Management & AI Business Intelligence Platform

**Document Type:** Product Requirements Document (PRD)  
**Version:** 1.0  
**Status:** Initial Product Specification  
**Platform:** Web Application  
**Target Users:** Coffee Shop Owners, Branch Managers, Cashiers  
**Development Approach:** Full-Stack, Modular Monolith  
**Initial Scope:** 3 Coffee Shop Branches

---

# 1. PRODUCT OVERVIEW

## 1.1 Product Summary

Veltrix adalah platform manajemen bisnis coffee shop berbasis web yang dirancang untuk membantu pemilik usaha mengelola, memantau, dan menganalisis operasional beberapa cabang melalui satu sistem terintegrasi.

Sistem ini menggabungkan Point of Sale (POS), manajemen produk, inventori bahan baku, pengeluaran operasional, manajemen karyawan, payroll, laporan bisnis, dan analitik berbasis Artificial Intelligence (AI).

Veltrix menyediakan tiga jenis pengguna, yaitu Owner, Branch Manager, dan Cashier, dengan hak akses sesuai tanggung jawab masing-masing.

Keunggulan utama Veltrix adalah AI Business Intelligence yang tidak hanya menjawab pertanyaan berdasarkan data bisnis, tetapi juga membantu mendeteksi perubahan performa, mengidentifikasi potensi masalah operasional, dan memberikan rekomendasi pengambilan keputusan.

## 1.2 Background

Pemilik coffee shop yang memiliki beberapa cabang sering menghadapi kesulitan dalam memonitor performa bisnis secara menyeluruh.

Data penjualan, stok bahan baku, pengeluaran, dan kinerja karyawan kerap dikelola secara terpisah. Kondisi ini menyebabkan pemilik usaha membutuhkan waktu lebih lama untuk memahami kondisi bisnis.

Sebagai contoh, owner yang mengelola tiga cabang di Depok, Jakarta, dan Bogor perlu mengetahui:

- Total pendapatan seluruh cabang.
- Cabang yang menghasilkan pendapatan dan keuntungan tertinggi.
- Produk yang paling laris secara keseluruhan.
- Cabang yang mengalami penurunan performa.
- Kondisi persediaan bahan baku setiap cabang.
- Pengeluaran operasional dan biaya tenaga kerja.
- Rekomendasi tindakan berdasarkan kondisi bisnis terbaru.

Veltrix dirancang untuk menjawab kebutuhan tersebut menggunakan data yang berasal langsung dari aktivitas operasional setiap cabang.

## 1.3 Problem Statement

Permasalahan utama yang ingin diselesaikan:

1. Data operasional dari beberapa cabang belum terintegrasi.
2. Owner kesulitan memantau pendapatan dan performa setiap cabang.
3. Pencatatan penjualan dan penggunaan stok bahan baku tidak selalu terhubung.
4. Evaluasi profitabilitas bisnis membutuhkan pengolahan data manual.
5. Informasi mengenai penurunan penjualan atau stok kritis sering terlambat diketahui.
6. Data historis belum dimanfaatkan secara optimal untuk mendukung keputusan bisnis.

## 1.4 Product Vision

Menjadi platform manajemen coffee shop multi-cabang yang mengintegrasikan operasional harian dan analitik bisnis agar pemilik usaha dapat mengambil keputusan secara lebih cepat, akurat, dan berbasis data.

## 1.5 Product Objectives

**Business Objectives**

- Meningkatkan visibilitas owner terhadap performa seluruh cabang.
- Mempermudah pengawasan operasional secara terpusat.
- Mengurangi pencatatan manual yang berulang.
- Membantu mengendalikan biaya dan pemborosan stok.
- Mempercepat identifikasi masalah dan peluang bisnis.

**Product Objectives**

- Menyediakan satu sumber data yang konsisten untuk seluruh cabang.
- Mengintegrasikan transaksi POS dengan inventori.
- Menyediakan pelaporan operasional dan finansial.
- Mendukung hak akses berbasis role dan cabang.
- Menghadirkan AI Business Assistant dan AI Insights.
- Menyediakan antarmuka yang responsif, modern, dan mudah digunakan.

## 1.6 Product Success Metrics

Target awal berikut merupakan target evaluasi produk, bukan hasil yang sudah tercapai.

| Metric | Initial Target |
|---|---|
| Successful transaction rate | ≥ 99% untuk transaksi valid dalam pengujian |
| Inventory synchronization | 100% transaksi selesai tercatat pada ledger stok |
| Dashboard data consistency | Konsisten dengan data transaksi sumber |
| Cross-branch access protection | 100% pengujian akses ilegal ditolak |
| AI factual accuracy | ≥ 95% pada dataset pertanyaan evaluasi |
| Critical insight detection | ≥ 95% pada skenario pengujian yang ditentukan |
| POS usability | Transaksi standar dapat diselesaikan tanpa pelatihan teknis khusus |

---

# 2. PRODUCT SCOPE

## 2.1 In-Scope Features

Veltrix mencakup modul berikut:

1. Authentication & Role-Based Access Control.
2. Multi-Branch Management.
3. Owner Dashboard.
4. Branch Manager Dashboard.
5. Cashier Point of Sale.
6. Product & Menu Management.
7. Recipe & Ingredient Management.
8. Inventory Management.
9. Purchasing & Stock Receiving.
10. Operational Expense Management.
11. Employee Management.
12. Payroll Management.
13. Financial & Sales Reporting.
14. AI Business Assistant.
15. Automated AI Business Insights.
16. Notifications & Activity Logs.
17. Account & System Settings.

Semua modul tersebut merupakan bagian dari rancangan produk akhir. Implementasi dilakukan secara bertahap berdasarkan ketergantungan teknis.

## 2.2 Out-of-Scope Features

Untuk menjaga fokus pengembangan, fitur berikut tidak termasuk dalam cakupan awal:

- Customer-facing ordering website.
- Aplikasi mobile native.
- Online food delivery integration.
- Payment gateway dengan transaksi uang sungguhan.
- Integrasi otomatis ke rekening bank.
- Automatic tax filing.
- Integrasi perangkat fingerprint.
- Integrasi mesin POS atau printer khusus secara native.
- Customer loyalty dan membership.
- Multi-company SaaS subscription dan billing.
- Autonomous AI yang melakukan perubahan bisnis tanpa persetujuan manusia.

Pembayaran QRIS, kartu, atau e-wallet dapat dicatat sebagai metode pembayaran, tetapi tidak berarti sistem terintegrasi langsung dengan penyedia pembayaran.

## 2.3 Initial Branch Configuration

Sistem menggunakan tiga cabang awal:

| Code | Branch | Location |
|---|---|---|
| BR-001 | Veltrix Depok | Depok |
| BR-002 | Veltrix Jakarta | Jakarta |
| BR-003 | Veltrix Bogor | Bogor |

Ketiga cabang hanya merupakan data awal pengujian. Arsitektur tidak boleh dibatasi menjadi tepat tiga cabang.

Owner dapat menambahkan cabang baru tanpa perubahan struktur database.

## 2.4 Key Product Assumptions

- Sistem pada versi awal digunakan oleh satu perusahaan dengan banyak cabang.
- Setiap akun Branch Manager memiliki satu cabang aktif yang ditugaskan.
- Setiap akun Cashier memiliki satu cabang aktif yang ditugaskan.
- Satu cabang dapat memiliki beberapa manager dan kasir.
- Owner memiliki akses ke seluruh cabang.
- Mata uang utama adalah Indonesian Rupiah (IDR).
- Perhitungan tanggal dan laporan mengikuti zona waktu cabang.
- Internet dibutuhkan untuk menggunakan aplikasi; offline POS tidak termasuk cakupan awal.
- Pelanggan tidak membutuhkan akun untuk melakukan pembelian.
- Akses pengguna dibuat oleh pengguna yang memiliki kewenangan, bukan melalui registrasi publik.

---

# 3. USER ROLES & ACCESS CONTROL

## 3.1 Owner

Owner adalah pengguna dengan otoritas tertinggi dalam sistem.

Tanggung jawab:

- Mengawasi seluruh cabang.
- Mengelola konfigurasi bisnis.
- Melihat laporan keuangan dan operasional.
- Mengevaluasi performa cabang.
- Mengakses AI Business Intelligence.
- Mengelola hak akses pengguna.
- Menyetujui perubahan tertentu yang memengaruhi bisnis.

### Owner Permissions

Owner dapat:

- Melihat dashboard gabungan.
- Membuka detail seluruh cabang.
- Mengelola cabang.
- Mengelola master katalog produk.
- Mengatur harga produk per cabang.
- Mengelola resep dan standar bahan.
- Melihat seluruh transaksi.
- Melihat seluruh inventori dan pembelian bahan.
- Melihat serta mengelola data karyawan.
- Mengelola payroll.
- Mengelola kategori pengeluaran.
- Melihat laporan keuangan lintas cabang.
- Mengakses AI Assistant dan AI Insights.
- Mengelola akun pengguna.
- Melihat audit log.

## 3.2 Branch Manager

Branch Manager bertanggung jawab atas satu cabang yang ditugaskan.

### Branch Manager Permissions

- Melihat dashboard cabangnya.
- Melihat transaksi cabangnya.
- Mengelola ketersediaan produk.
- Mengajukan perubahan harga untuk persetujuan owner.
- Mengelola persediaan bahan.
- Membuat pembelian dan penerimaan stok.
- Melakukan stock adjustment dengan alasan yang tercatat.
- Mencatat pengeluaran operasional.
- Melihat dan mengelola data karyawan cabangnya sesuai izin.
- Melihat jadwal kerja dan ringkasan performa kasir.
- Menyetujui refund atau pembatalan sesuai kebijakan.
- Melihat laporan operasional cabangnya.
- Melihat notifikasi operasional.

Branch Manager tidak dapat melihat data sensitif cabang lain, mengakses payroll seluruh perusahaan, mengelola owner, atau membuka laporan konsolidasi perusahaan.

## 3.3 Cashier

Cashier bertanggung jawab memproses penjualan di cabangnya.

### Cashier Permissions

- Mengakses halaman POS.
- Membuka dan menutup shift kasir.
- Membuat pesanan.
- Memilih produk dan kuantitas.
- Menambahkan catatan pesanan.
- Memproses transaksi pembayaran.
- Melihat riwayat transaksi miliknya.
- Membuat struk digital.
- Mengajukan pembatalan atau refund kepada manager.
- Melihat informasi profil pribadi.

Cashier tidak memiliki akses ke laporan keuangan perusahaan, manajemen stok utama, data payroll, pengaturan cabang, atau AI Business Assistant.

## 3.4 Permission Matrix

| Feature | Owner | Manager | Cashier |
|---|---|---|---|
| Consolidated Dashboard | Full | No | No |
| Branch Dashboard | All | Own branch | No |
| POS | Optional oversight | View | Operate |
| Sales History | All | Own branch | Own transactions |
| Product Catalog | Manage | View | View |
| Branch Availability | Manage | Manage own branch | View |
| Product Pricing | Approve/manage | Request | View |
| Inventory | All | Own branch | No |
| Purchasing | All | Own branch | No |
| Operational Expenses | All | Own branch | No |
| Employee Management | All | Limited own branch | No |
| Payroll | Full | No sensitive salary access | No |
| Business Reports | All | Own branch | No |
| AI Business Assistant | Full | No | No |
| AI Insights | Full | Operational alerts only | No |
| User Management | Full | No | No |
| Audit Logs | Full | Limited relevant logs | No |

All permissions must be enforced by the backend.

---

# 4. CORE MODULE REQUIREMENTS

# 4.1 Authentication & Account Management

**Module ID:** AUTH

### Functional Requirements

**AUTH-01 — Login**

Users can log in using their email and password.

**AUTH-02 — Logout**

Users can end their authenticated session.

**AUTH-03 — Role-Based Redirect**

After successful login:

- Owner enters Owner Dashboard.
- Branch Manager enters Branch Dashboard.
- Cashier enters POS.

**AUTH-04 — Account Creation**

Owner can create Branch Manager and Cashier accounts.

**AUTH-05 — Password Management**

Users can change their own passwords. Account recovery must use a secure reset flow.

**AUTH-06 — Account Status**

Owner can activate or deactivate accounts. Deactivated users must not be able to access protected features.

**AUTH-07 — Session Security**

Authentication sessions use secure HTTP-only cookies, CSRF protection, and session regeneration on login.

### Acceptance Criteria

- Users cannot access protected pages without authentication.
- Users cannot access features outside their permissions.
- Manager and Cashier access is restricted to their assigned branch.
- Deactivated users lose access to protected functionality.

---

# 4.2 Owner Dashboard

**Module ID:** ODB

## Purpose

Memberikan ringkasan menyeluruh mengenai kondisi bisnis semua cabang.

### Dashboard Overview

**ODB-01 — KPI Summary Cards**

Dashboard menampilkan:

- Total gross sales.
- Net sales setelah diskon dan refund.
- Total completed transactions.
- Average order value.
- Estimated gross profit.
- Operating expenses.
- Estimated operating profit.
- Total active branches.

Nilai profit harus diberi label estimasi apabila belum seluruh biaya dan penyesuaian akuntansi tersedia.

**ODB-02 — Revenue Trend**

Grafik tren penjualan dapat difilter berdasarkan:

- Today.
- Last 7 Days.
- This Month.
- Custom Date Range.

**ODB-03 — Branch Performance Comparison**

Menampilkan perbandingan tiga cabang berdasarkan:

- Net sales.
- Total transactions.
- Average order value.
- Units sold.
- Estimated gross profit.
- Estimated operating profit.

**ODB-04 — Top Selling Products**

Menampilkan produk terlaris berdasarkan:

- Quantity sold.
- Net sales contribution.

Kedua metode pengurutan harus dibedakan karena produk paling banyak terjual belum tentu menghasilkan pendapatan terbesar.

**ODB-05 — Sales Distribution**

Menampilkan proporsi penjualan berdasarkan cabang, kategori, atau metode pembayaran.

**ODB-06 — Recent Transactions**

Menampilkan transaksi terbaru dari semua cabang dengan identitas cabang dan status transaksi.

**ODB-07 — Operational Alerts**

Menampilkan peringatan:

- Low stock.
- Out of stock.
- Unusual sales decrease.
- Pending approvals.
- Unusual operating expenses.
- Stock discrepancy.

**ODB-08 — AI Insights Preview**

Menampilkan ringkasan insight bisnis terbaru dengan tombol untuk membuka detail.

**ODB-09 — Dashboard Filters**

Owner dapat mengatur:

- Date range.
- Branch.
- Product category.
- Comparison period.

## Owner Dashboard Main Layout

1. Top Header & Global Filters.
2. KPI Summary Cards.
3. Revenue Trend Chart.
4. Branch Performance Comparison.
5. Top Selling Products.
6. AI Business Insights.
7. Inventory Alerts.
8. Recent Transactions.

### Acceptance Criteria

- Dashboard dapat menampilkan data gabungan atau cabang tertentu.
- KPI mengikuti filter periode yang dipilih.
- Angka laporan konsisten dengan transaksi yang telah diselesaikan.
- Transaksi pending, void, dan refund tidak dihitung sebagai penjualan final secara keliru.
- Owner dapat melakukan drill-down dari ringkasan menuju detail data.

---

# 4.3 Branch Management

**Module ID:** BRN

### Functional Requirements

**BRN-01 — Branch Directory**

Owner dapat melihat semua cabang dalam bentuk daftar atau kartu.

**BRN-02 — Create Branch**

Owner dapat menambahkan cabang baru dengan informasi:

- Branch name.
- Branch code.
- Address.
- Contact number.
- Opening hours.
- Time zone.
- Operational status.

**BRN-03 — Edit Branch**

Owner dapat memperbarui informasi cabang.

**BRN-04 — Branch Detail**

Setiap cabang memiliki halaman detail berisi:

- Sales performance.
- Transaction volume.
- Products.
- Inventory status.
- Employees.
- Operating expenses.
- Recent activity.

**BRN-05 — Assign Staff**

Owner dapat menugaskan manager dan cashier ke cabang.

**BRN-06 — Branch Status**

Cabang dapat ditandai active atau inactive.

Cabang inactive tidak dapat menerima transaksi baru, tetapi data historis tetap tersedia.

---

# 4.4 Branch Manager Dashboard

**Module ID:** MDB

## Purpose

Membantu manager memantau dan menjalankan operasional cabang yang ditugaskan.

### Functional Requirements

**MDB-01 — Branch KPI**

Menampilkan:

- Today's sales.
- Today's completed transactions.
- Average order value.
- Units sold.
- Low-stock items.
- Pending operational tasks.

**MDB-02 — Sales Trends**

Grafik penjualan harian, mingguan, dan bulanan.

**MDB-03 — Best-Selling Products**

Ranking produk terlaris khusus cabangnya.

**MDB-04 — Inventory Alerts**

Daftar bahan baku yang mencapai batas minimum.

**MDB-05 — Recent Transactions**

Menampilkan transaksi cabang terbaru.

**MDB-06 — Operational Expenses**

Ringkasan pengeluaran cabang.

**MDB-07 — Staff Overview**

Menampilkan karyawan dan shift yang terdaftar pada cabangnya.

**MDB-08 — Pending Approvals**

Menampilkan permintaan refund, stock adjustment, atau tugas yang membutuhkan perhatian manager.

### Acceptance Criteria

- Semua data berasal dari cabang yang ditugaskan.
- Manager tidak dapat mengakses data cabang lain dengan memodifikasi parameter request.
- Dashboard diperbarui sesuai data terbaru yang tersedia.

---

# 4.5 Point of Sale (POS)

**Module ID:** POS

## Purpose

Memungkinkan Cashier melakukan transaksi penjualan secara cepat, akurat, dan terintegrasi dengan inventori.

### Functional Requirements

**POS-01 — Product Catalog**

Menampilkan produk dengan:

- Product name.
- Category.
- Image.
- Selling price.
- Availability status.

**POS-02 — Product Search**

Kasir dapat mencari produk berdasarkan nama.

**POS-03 — Product Category Filter**

Produk dapat difilter berdasarkan kategori.

**POS-04 — Shopping Cart**

Kasir dapat:

- Menambahkan produk.
- Mengubah kuantitas.
- Menghapus produk.
- Melihat subtotal.
- Melihat total pembayaran.

**POS-05 — Order Notes**

Kasir dapat menambahkan catatan transaksi atau item.

**POS-06 — Payment Processing**

Metode pencatatan pembayaran:

- Cash.
- QRIS.
- Debit/Credit Card.
- E-Wallet.

Pada versi awal, pembayaran selain cash dicatat secara manual sebagai telah diterima oleh kasir, tanpa verifikasi langsung ke penyedia pembayaran.

**POS-07 — Change Calculation**

Untuk pembayaran tunai, sistem menghitung uang kembalian.

**POS-08 — Complete Transaction**

Saat pembayaran dikonfirmasi:

1. Backend memvalidasi produk, harga, dan cabang.
2. Sistem memvalidasi ketersediaan bahan menurut kebijakan stok.
3. Transaksi dicatat.
4. Item penjualan dicatat.
5. Pengurangan stok bahan dicatat.
6. Transaksi dinyatakan completed.
7. Dashboard dapat membaca data terbaru.

Operasi penting harus menggunakan database transaction agar tidak terjadi transaksi penjualan yang berhasil tanpa pencatatan perubahan inventori.

**POS-09 — Transaction Receipt**

Setelah transaksi berhasil, sistem membuat struk digital yang dapat ditampilkan, dicetak melalui browser, atau diunduh.

**POS-10 — Transaction History**

Cashier dapat melihat transaksi yang diprosesnya sendiri.

**POS-11 — Cashier Shift**

Kasir dapat membuka dan menutup shift dengan pencatatan:

- Opening cash.
- Start time.
- End time.
- Expected cash.
- Actual closing cash.
- Cash discrepancy.

**POS-12 — Refund & Void**

Kasir dapat mengajukan refund atau void. Manager atau Owner menyetujui sesuai aturan.

Refund dan void harus menyimpan jejak audit dan tidak boleh menghapus transaksi asli.

**POS-13 — Duplicate Submission Protection**

Sistem harus mencegah transaksi ganda akibat double-click, request retry, atau gangguan koneksi.

### POS Business Rules

- Harga final divalidasi oleh backend.
- Produk tidak tersedia tidak bisa ditambahkan ke transaksi final.
- Transaksi yang telah completed tidak dapat diubah langsung.
- Refund diproses sebagai transaksi koreksi.
- Penjualan harus dikaitkan dengan cabang, cashier, dan shift.
- Kebijakan stok tidak boleh membiarkan dua transaksi bersamaan menggunakan persediaan yang sama secara tidak konsisten.

### Acceptance Criteria

- Total transaksi dihitung dengan benar.
- Kasir tidak dapat memodifikasi harga melalui browser developer tools.
- Double-click tidak menyebabkan transaksi duplikat.
- Pengurangan stok hanya dilakukan sekali untuk transaksi yang berhasil.
- Refund mengoreksi laporan dan stok sesuai kebijakan.

---

# 4.6 Product & Menu Management

**Module ID:** PRD

### Functional Requirements

**PRD-01 — Product Categories**

Owner dapat membuat dan mengelola kategori produk seperti Coffee, Non-Coffee, Tea, Food, dan Pastry.

**PRD-02 — Product Management**

Setiap produk menyimpan:

- Product ID.
- SKU.
- Product name.
- Category.
- Description.
- Product image.
- Base price.
- Product status.

**PRD-03 — Branch-Specific Pricing**

Owner dapat menentukan harga berbeda untuk cabang tertentu.

Contoh:

| Product | Depok | Jakarta | Bogor |
|---|---:|---:|---:|
| Kopi Susu Gula Aren | Rp22.000 | Rp25.000 | Rp23.000 |

Branch Manager dapat mengajukan perubahan harga, tetapi perubahan baru aktif setelah disetujui Owner.

**PRD-04 — Branch Availability**

Manager dapat mengatur ketersediaan produk untuk cabangnya.

**PRD-05 — Product Images**

Sistem mendukung unggahan dan pengelolaan foto produk.

**PRD-06 — Product Status**

Produk dapat dinonaktifkan tanpa menghapus riwayat penjualannya.

**PRD-07 — Product Sales Analytics**

Menampilkan jumlah terjual, pendapatan, dan estimasi margin produk.

### Business Rules

- Master produk bersifat terpusat.
- Harga dan ketersediaan dapat berbeda per cabang.
- Produk yang sudah pernah terjual tidak dihapus secara permanen.
- Item transaksi menyimpan snapshot harga dan nama produk saat penjualan agar riwayat tetap benar meskipun katalog berubah.

---

# 4.7 Recipe & Ingredient Management

**Module ID:** RCP

## Purpose

Menghubungkan setiap menu dengan bahan baku yang digunakan dalam proses produksi.

### Functional Requirements

**RCP-01 — Ingredient Master**

Setiap bahan memiliki:

- Ingredient ID.
- Ingredient name.
- Unit of measurement.
- Minimum stock level.
- Active status.

**RCP-02 — Product Recipe**

Owner dapat menentukan komposisi bahan untuk setiap produk.

Contoh resep Kopi Susu Gula Aren:

| Ingredient | Quantity |
|---|---:|
| Coffee Beans | 18 gram |
| Fresh Milk | 150 ml |
| Palm Sugar Syrup | 20 ml |

**RCP-03 — Automatic Ingredient Deduction**

Jika dua Kopi Susu Gula Aren terjual, sistem menghitung kebutuhan bahan:

- Coffee Beans: 36 gram.
- Fresh Milk: 300 ml.
- Palm Sugar Syrup: 40 ml.

**RCP-04 — Recipe Versioning**

Perubahan resep berlaku untuk transaksi berikutnya dan tidak mengubah perhitungan historis.

**RCP-05 — Recipe Cost Estimation**

Sistem menghitung perkiraan biaya bahan per menu berdasarkan metode penilaian persediaan yang dipilih.

### Business Rules

- Satuan bahan harus konsisten dan dapat dikonversi secara terdefinisi.
- Setiap produk yang menggunakan stok bahan harus memiliki resep aktif.
- Perubahan resep tidak boleh merusak pencatatan stok sebelumnya.
- Produk yang dijual sebagai barang jadi dapat menggunakan aturan stok barang jadi alih-alih resep bahan.

---

# 4.8 Inventory Management

**Module ID:** INV

## Purpose

Mengelola persediaan bahan baku pada setiap cabang.

### Functional Requirements

**INV-01 — Inventory Overview**

Manager dapat melihat:

- Ingredient name.
- Current stock.
- Unit.
- Minimum stock.
- Stock status.
- Estimated stock value.

**INV-02 — Branch-Specific Inventory**

Setiap cabang memiliki saldo inventori sendiri.

**INV-03 — Automatic Stock Deduction**

Stok berkurang berdasarkan resep saat transaksi selesai.

**INV-04 — Stock Adjustment**

Manager dapat mencatat penyesuaian stok dengan alasan:

- Spoilage.
- Damage.
- Expired goods.
- Physical count correction.
- Other approved reason.

**INV-05 — Low Stock Alerts**

Sistem memberikan peringatan ketika stok mencapai atau berada di bawah batas minimum.

**INV-06 — Stock Movement History**

Sistem mencatat:

- Stock in.
- Stock out.
- Sales consumption.
- Stock adjustment.
- Return/restoration.
- Inter-branch transfer.

**INV-07 — Inventory Transfers**

Owner atau Manager dapat membuat permintaan perpindahan stok antar cabang.

Transfer menggunakan status requested, approved, dispatched, received, atau cancelled.

Stok tujuan hanya bertambah ketika penerimaan dikonfirmasi.

**INV-08 — Stock Count**

Manager dapat melakukan stock opname dan mencatat selisih antara stok sistem dan stok fisik.

### Business Rules

- Seluruh perubahan stok dicatat dalam inventory movement ledger.
- Saldo stok tidak boleh berubah tanpa sumber transaksi yang jelas.
- Pengurangan dan penambahan stok harus aman terhadap request bersamaan.
- Kebijakan awal adalah menolak penjualan apabila bahan tidak mencukupi.
- Perubahan stok akibat refund perlu mengikuti apakah bahan benar-benar dapat dikembalikan ke inventori.
- Transfer antar cabang harus menjaga konsistensi stok pengirim dan penerima.

---

# 4.9 Purchasing & Stock Receiving

**Module ID:** PUR

### Functional Requirements

**PUR-01 — Supplier Directory**

Menyimpan nama supplier, kontak, dan status.

**PUR-02 — Purchase Order**

Manager dapat membuat pesanan pembelian bahan baku.

**PUR-03 — Purchase Items**

Setiap pembelian memiliki daftar bahan, jumlah, harga satuan, dan total.

**PUR-04 — Purchase Status**

Status pembelian:

- Draft.
- Ordered.
- Partially Received.
- Received.
- Cancelled.

**PUR-05 — Stock Receiving**

Stok bertambah berdasarkan jumlah bahan yang benar-benar diterima.

**PUR-06 — Purchase History**

Manager dan Owner dapat melihat riwayat pembelian.

**PUR-07 — Purchase Cost Tracking**

Sistem menyimpan biaya pembelian untuk mendukung analisis biaya bahan dan nilai persediaan.

### Business Rules

- Membuat purchase order tidak langsung menambah stok.
- Stok bertambah setelah penerimaan bahan dikonfirmasi.
- Pembelian dan pembayaran kepada supplier harus dibedakan.
- Pembelian persediaan tidak otomatis dihitung seluruhnya sebagai beban laba rugi pada tanggal pembelian.

---

# 4.10 Operational Expense Management

**Module ID:** EXP

### Functional Requirements

**EXP-01 — Expense Categories**

Kategori pengeluaran dapat mencakup:

- Rent.
- Electricity.
- Water.
- Internet.
- Cleaning.
- Maintenance.
- Transportation.
- Marketing.
- Miscellaneous.

**EXP-02 — Record Expense**

Manager dapat mencatat:

- Expense category.
- Amount.
- Expense date.
- Description.
- Branch.
- Supporting document.
- Payment status.

**EXP-03 — Expense Approval**

Pengeluaran tertentu dapat membutuhkan persetujuan owner berdasarkan batas nominal yang dikonfigurasi.

**EXP-04 — Expense History**

Pengeluaran dapat difilter berdasarkan tanggal, kategori, dan cabang.

**EXP-05 — Expense Analytics**

Owner dapat melihat tren pengeluaran dan perbandingan antar cabang.

### Business Rules

- Pengeluaran bisnis harus dapat dilacak ke cabang atau kategori biaya pusat.
- Pengeluaran belum disetujui tidak dianggap final dalam laporan yang mensyaratkan approval.
- Semua perubahan nilai pengeluaran tercatat dalam audit log.

---

# 4.11 Employee Management

**Module ID:** EMP

### Functional Requirements

**EMP-01 — Employee Directory**

Menyimpan data karyawan:

- Employee ID.
- Full name.
- Contact information.
- Position.
- Assigned branch.
- Employment status.
- Join date.

**EMP-02 — Employee Assignment**

Owner dapat memindahkan karyawan antar cabang.

**EMP-03 — Employee Status**

Status:

- Active.
- Inactive.
- Resigned.

**EMP-04 — Work Schedule**

Manager dapat membuat dan mengatur jadwal kerja staf cabangnya.

**EMP-05 — Cashier Activity**

Manager dapat melihat transaksi yang diproses oleh kasir sesuai akses cabangnya.

### Business Rules

- Data karyawan berbeda dari akun login, tetapi dapat saling dihubungkan.
- Tidak semua karyawan wajib mempunyai akun sistem.
- Data pribadi karyawan hanya dapat dilihat oleh pengguna yang berwenang.

---

# 4.12 Payroll Management

**Module ID:** PAY

## Purpose

Mempermudah pencatatan perhitungan dan pembayaran gaji karyawan.

### Functional Requirements

**PAY-01 — Salary Configuration**

Owner dapat mengatur komponen gaji:

- Base salary.
- Allowance.
- Bonus.
- Deduction.

**PAY-02 — Payroll Period**

Sistem mendukung periode payroll bulanan.

**PAY-03 — Payroll Calculation**

Sistem menghitung:

Net Pay = Base Salary + Allowances + Bonuses − Deductions

**PAY-04 — Payroll Review**

Owner dapat meninjau payroll sebelum disetujui.

**PAY-05 — Payroll Approval**

Payroll harus disetujui sebelum dinyatakan final.

**PAY-06 — Payment Status**

Status payroll:

- Draft.
- Approved.
- Paid.

Status paid merupakan pencatatan pembayaran manual, bukan transfer bank otomatis.

**PAY-07 — Payslip**

Owner dapat menghasilkan slip gaji.

**PAY-08 — Payroll Reports**

Owner dapat melihat total biaya payroll berdasarkan bulan dan cabang.

### Business Rules

- Nominal gaji merupakan data sensitif.
- Manager tidak dapat melihat rincian gaji tanpa izin khusus.
- Payroll yang telah final tidak boleh diubah diam-diam.
- Koreksi menggunakan mekanisme adjustment atau revisi yang dapat diaudit.
- Perhitungan pajak penghasilan, BPJS, dan kepatuhan ketenagakerjaan otomatis tidak termasuk cakupan awal.

---

# 4.13 Reports & Business Analytics

**Module ID:** RPT

## Purpose

Menyediakan laporan akurat untuk membantu evaluasi performa bisnis.

### Functional Requirements

**RPT-01 — Sales Report**

Menampilkan:

- Gross sales.
- Discounts.
- Refunds.
- Net sales.
- Transaction count.
- Items sold.
- Average order value.

**RPT-02 — Branch Performance Report**

Membandingkan performa tiap cabang.

**RPT-03 — Product Performance Report**

Menampilkan ranking produk berdasarkan jumlah unit terjual dan kontribusi pendapatan.

**RPT-04 — Inventory Report**

Menampilkan penggunaan bahan, penerimaan stok, penyesuaian, dan nilai persediaan.

**RPT-05 — Expense Report**

Menampilkan pengeluaran berdasarkan kategori, periode, dan cabang.

**RPT-06 — Payroll Report**

Menampilkan total payroll dan alokasinya per cabang.

**RPT-07 — Profitability Report**

Menghitung estimasi:

Gross Profit = Net Sales − Cost of Goods Sold

Operating Profit = Gross Profit − Operating Expenses − Payroll Expenses

Rumus ini merupakan penyederhanaan manajerial. Hasil bukan laporan laba rugi akuntansi resmi dan tidak mencakup seluruh kemungkinan penyesuaian, pajak, depresiasi, maupun biaya pembiayaan.

**RPT-08 — Date & Branch Filters**

Laporan dapat difilter berdasarkan periode dan cabang.

**RPT-09 — Export Report**

Mendukung ekspor CSV dan PDF.

**RPT-10 — Drill-Down**

Owner dapat membuka data detail yang membentuk angka dalam laporan.

### Business Rules

- Definisi metrik harus konsisten di seluruh dashboard dan laporan.
- Produk yang dikembalikan diperhitungkan dengan benar.
- Laporan tidak boleh menghitung transaksi yang sama lebih dari sekali.
- Nilai uang disimpan dengan representasi presisi yang aman.
- Data historis tetap tersedia ketika produk, akun, atau cabang dinonaktifkan.

---

# 5. AI BUSINESS INTELLIGENCE

## 5.1 AI Overview

Veltrix menyediakan dua kemampuan AI utama:

1. **AI Business Assistant** — menjawab pertanyaan owner menggunakan data operasional.
2. **Automated AI Insights** — mendeteksi tren, anomali, dan peluang bisnis secara berkala.

AI harus berfungsi sebagai pendukung pengambilan keputusan, bukan pengganti keputusan owner.

## 5.2 AI Business Assistant

**Module ID:** AIA

### Purpose

Memungkinkan Owner mengakses data bisnis melalui percakapan bahasa alami tanpa harus membuka dan menganalisis banyak laporan secara manual.

### Functional Requirements

**AIA-01 — Conversational Interface**

Owner dapat mengetik pertanyaan melalui halaman AI Assistant.

**AIA-02 — Business Data Querying**

AI memahami pertanyaan dan memilih tool backend yang sesuai.

Contoh pertanyaan:

- Produk apa yang paling laris di seluruh cabang bulan ini?
- Berapa total pendapatan minggu ini?
- Cabang mana yang memiliki omzet tertinggi?
- Berapa rata-rata transaksi per hari?
- Apa lima produk dengan penjualan terendah?
- Cabang mana yang mengalami penurunan penjualan?
- Berapa total biaya operasional bulan ini?
- Produk mana yang memiliki margin tertinggi?
- Apakah penjualan minggu ini lebih baik dibanding minggu lalu?
- Bahan baku apa yang diperkirakan segera habis?

**AIA-03 — Multi-Branch Analysis**

AI dapat menggabungkan data dari seluruh cabang atau menganalisis satu cabang sesuai pertanyaan.

**AIA-04 — Period Understanding**

AI memahami periode seperti:

- Today.
- Yesterday.
- This Week.
- Last Week.
- This Month.
- Last Month.
- Custom date range.

Periode yang ambigu harus diklarifikasi atau dijelaskan asumsi tanggalnya.

**AIA-05 — Tool Calling**

AI hanya dapat menggunakan fungsi backend yang diizinkan.

Contoh tools:

- get_top_selling_products
- get_sales_summary
- compare_branch_performance
- get_product_performance
- get_operational_expenses
- get_profitability_summary
- get_low_stock_items
- get_inventory_forecast
- get_sales_trends

**AIA-06 — Data-Grounded Responses**

Jawaban AI harus berasal dari data backend, bukan angka yang dikarang oleh model.

**AIA-07 — Response Format**

Jawaban dapat berisi:

- Ringkasan.
- Angka utama.
- Perbandingan.
- Penjelasan.
- Rekomendasi.
- Referensi periode dan sumber data internal.

**AIA-08 — Chat History**

Sistem menyimpan riwayat percakapan owner dengan batas retensi yang dapat diatur.

**AIA-09 — Follow-Up Questions**

Owner dapat mengajukan pertanyaan lanjutan dengan konteks percakapan sebelumnya.

**AIA-10 — Error Handling**

Jika data tidak tersedia, tool gagal, atau pertanyaan di luar cakupan, AI harus memberikan respons yang jelas dan tidak mengarang jawaban.

### AI Business Rules

- AI tidak boleh memiliki akses SQL unrestricted.
- Query dilakukan melalui tool backend yang telah divalidasi.
- Semua akses data mengikuti permission pengguna.
- Input model tidak boleh langsung diubah menjadi SQL bebas.
- Output AI tidak boleh dianggap fakta tanpa data pendukung.
- Data sensitif hanya dikirim ke model jika diperlukan dan diperbolehkan.
- API key disimpan di server dan tidak diekspos ke frontend.
- AI Assistant pada versi awal bersifat read-only.

### Example Interaction

**Owner:**

"Produk apa yang paling laris di seluruh cabang bulan ini?"

**System Action:**

1. Gemini memahami maksud pertanyaan.
2. Gemini memilih get_top_selling_products.
3. Laravel memvalidasi parameter.
4. Laravel menjalankan query ke PostgreSQL.
5. Database mengembalikan data agregasi.
6. Gemini menyusun jawaban berdasarkan data.

**Example Response (Simulated):**

"Produk paling laris bulan ini adalah Kopi Susu Gula Aren, dengan total 550 cup terjual di seluruh cabang. Jakarta menjadi kontributor tertinggi dengan 210 cup, diikuti Depok sebanyak 180 cup dan Bogor sebanyak 160 cup."

### Acceptance Criteria

- AI memilih fungsi yang tepat untuk pertanyaan umum pada dataset pengujian.
- Angka yang ditampilkan sesuai hasil perhitungan backend.
- AI mencantumkan periode analisis.
- AI menyatakan dengan jelas saat informasi tidak tersedia.
- Permintaan di luar hak akses ditolak.
- Pertanyaan lanjutan dapat menggunakan konteks yang relevan tanpa mengorbankan keamanan data.

---

# 5.3 Automated AI Business Insights

**Module ID:** AII

## Purpose

Membantu owner menemukan masalah atau peluang bisnis tanpa harus mengajukan pertanyaan terlebih dahulu.

### Insight Categories

**AII-01 — Sales Decline Detection**

Sistem mengidentifikasi penurunan penjualan signifikan dibandingkan periode pembanding yang setara.

Contoh:

"Penjualan cabang Bogor turun 15% dibandingkan minggu sebelumnya."

**AII-02 — Sales Growth Detection**

Sistem mendeteksi peningkatan performa cabang atau produk.

**AII-03 — Product Trend Analysis**

Sistem mengidentifikasi produk yang mengalami pertumbuhan atau penurunan jumlah penjualan.

**AII-04 — Low Stock Prediction**

Sistem memperkirakan waktu habisnya stok berdasarkan persediaan, konsumsi historis, dan asumsi perhitungan.

**AII-05 — Expense Anomaly Detection**

Sistem mendeteksi pengeluaran yang meningkat jauh dari baseline historis.

**AII-06 — Branch Comparison**

Sistem membandingkan performa cabang dan mengidentifikasi perbedaan yang patut dianalisis.

**AII-07 — Product Opportunity**

Sistem memberikan rekomendasi evaluasi terhadap produk yang menunjukkan tren permintaan positif.

**AII-08 — Insight Priority**

Setiap insight memiliki tingkat prioritas:

- Critical.
- Warning.
- Informational.

**AII-09 — Insight Status**

Insight dapat memiliki status:

- New.
- Read.
- Dismissed.
- Resolved.

**AII-10 — Insight Detail**

Setiap insight menampilkan:

- Title.
- Description.
- Branch.
- Analysis period.
- Key metric.
- Comparison baseline.
- Supporting data.
- Recommendation.
- Confidence or calculation limitations.
- Generation timestamp.

## AI Insight Generation Mechanism

Sistem menggunakan pendekatan hybrid:

**Step 1 — Data Collection**

Laravel mengambil data operasional dari database.

**Step 2 — Deterministic Analysis**

Analytics Engine melakukan perhitungan:

- Growth rate.
- Revenue variance.
- Sales trend.
- Stock consumption rate.
- Expense deviation.
- Branch performance.

**Step 3 — Rule-Based Detection**

Sistem mengevaluasi threshold yang sudah ditentukan.

Contoh:

Penurunan penjualan lebih dari 10% dapat memicu insight apabila volume data memenuhi batas minimum.

**Step 4 — LLM Interpretation**

Gemini menghasilkan penjelasan natural dan rekomendasi berdasarkan hasil analisis.

**Step 5 — Insight Storage**

Insight disimpan ke database dan ditampilkan di dashboard.

**Step 6 — Notification**

Owner menerima notifikasi ketika insight penting tersedia.

## Scheduling

Analisis otomatis dijalankan melalui Laravel Scheduler dan Queue.

Jadwal awal:

- Daily operational insights: setiap pagi setelah data hari sebelumnya lengkap.
- Weekly performance insights: awal minggu.
- Monthly business summary: setelah periode bulan ditutup.

Frekuensi dapat diubah melalui konfigurasi.

## Important AI Limitations

- Korelasi bukan bukti sebab-akibat.
- Sistem tidak boleh menyimpulkan penyebab penurunan omzet tanpa bukti yang memadai.
- AI tidak boleh membuat pembelian, mengubah harga, atau melakukan transfer stok secara otomatis.
- Prediksi berbasis data terbatas harus diberi penjelasan keterbatasan.
- Insight yang sudah pernah dibuat tidak boleh dikirim berulang tanpa perubahan penting.

### Acceptance Criteria

- Insight hanya dihasilkan berdasarkan data yang valid.
- Sistem dapat menunjukkan data pendukung.
- Insight duplikat dapat dikendalikan.
- Proses analisis gagal tidak mengganggu transaksi POS.
- Penggunaan Gemini API dapat dipantau dan dibatasi.

---

# 6. NOTIFICATIONS & AUDIT LOGS

## 6.1 Notifications

**Module ID:** NTF

### Notification Types

- Low inventory.
- Out of stock.
- Pending refund approval.
- Pending price change.
- Purchase receiving reminder.
- Significant sales decline.
- New business insight.
- Payroll pending approval.

### Requirements

- Notifikasi ditampilkan di aplikasi.
- Notifikasi dibatasi berdasarkan role dan cabang.
- Pengguna dapat menandai notifikasi sebagai telah dibaca.
- Notifikasi memiliki tautan ke halaman yang relevan.
- Notifikasi tidak boleh membocorkan data yang tidak boleh diakses pengguna.

## 6.2 Audit Logs

**Module ID:** AUD

Sistem mencatat aktivitas sensitif seperti:

- Login dan perubahan status akun.
- Perubahan harga.
- Perubahan resep.
- Stock adjustment.
- Refund approval.
- Payroll approval.
- Perubahan hak akses.
- Perubahan konfigurasi cabang.

Setiap audit log minimal menyimpan actor, action, entity, timestamp, dan detail perubahan yang relevan.

Audit log harus dilindungi dari perubahan oleh pengguna biasa.

---

# 7. MAIN USER FLOWS

## 7.1 Flow A — Cashier Sales Transaction

1. Cashier login.
2. Cashier membuka shift.
3. Cashier mengakses POS.
4. Cashier memilih produk.
5. Sistem menambahkan produk ke cart.
6. Cashier mengatur kuantitas.
7. Sistem menampilkan total.
8. Cashier memilih metode pembayaran.
9. Backend memvalidasi transaksi.
10. Pembayaran dikonfirmasi sesuai metode.
11. Sistem menyimpan transaksi.
12. Sistem mengurangi stok berdasarkan resep.
13. Sistem membuat struk.
14. Dashboard menampilkan data transaksi terbaru.

**Expected Result:** Penjualan berhasil dicatat dan seluruh perubahan operasional terkait tetap konsisten.

## 7.2 Flow B — Inventory Restocking

1. Manager login.
2. Manager membuka Inventory.
3. Manager melihat bahan yang menipis.
4. Manager membuka Purchasing.
5. Manager membuat purchase order.
6. Supplier mengirim bahan.
7. Manager mencatat barang yang diterima.
8. Sistem menambah stok sesuai jumlah penerimaan.
9. Stock movement tercatat.
10. Status peringatan stok diperbarui.

**Expected Result:** Stok tersedia bertambah berdasarkan penerimaan nyata dan memiliki riwayat yang dapat diaudit.

## 7.3 Flow C — Owner Monitoring

1. Owner login.
2. Sistem menampilkan consolidated dashboard.
3. Owner memilih periode.
4. Sistem menampilkan KPI dan grafik.
5. Owner membandingkan tiga cabang.
6. Owner membuka detail cabang.
7. Owner mengevaluasi penjualan, biaya, dan profitabilitas.
8. Owner membuka AI Insights untuk melihat temuan otomatis.

**Expected Result:** Owner dapat memahami performa bisnis tanpa menggabungkan laporan cabang secara manual.

## 7.4 Flow D — AI Business Assistant

1. Owner membuka AI Assistant.
2. Owner mengetik pertanyaan bisnis.
3. Laravel mengirim pertanyaan beserta definisi tools yang diizinkan ke Gemini.
4. Gemini memilih tool analitik.
5. Laravel memvalidasi akses dan parameter.
6. Analytics Service menghitung hasil.
7. Hasil dikirim kembali ke model.
8. Gemini menyusun respons.
9. Owner menerima jawaban dan dapat mengajukan pertanyaan lanjutan.

**Expected Result:** Owner mendapatkan jawaban yang akurat dan bisa ditelusuri ke data.

## 7.5 Flow E — Automated Business Insights

1. Scheduler memicu proses analitik.
2. Queue worker mengambil tugas.
3. Sistem mengagregasi data dari seluruh cabang.
4. Analytics Engine mendeteksi perubahan penting.
5. Gemini menambahkan ringkasan dan rekomendasi bila diperlukan.
6. Insight disimpan.
7. Owner menerima notifikasi.
8. Owner membuka detail insight.
9. Owner menindaklanjuti rekomendasi secara manual.

**Expected Result:** Owner mengetahui kondisi penting tanpa memeriksa laporan satu per satu.

## 7.6 Flow F — Payroll

1. Owner memilih periode payroll.
2. Sistem mengambil karyawan aktif dan konfigurasi gaji.
3. Sistem menghitung payroll draft.
4. Owner meninjau dan menyesuaikan komponen yang diizinkan.
5. Owner menyetujui payroll.
6. Pembayaran dilakukan di luar sistem.
7. Owner mencatat status paid.
8. Sistem menghasilkan slip gaji dan memperbarui laporan biaya.

**Expected Result:** Biaya payroll tercatat secara konsisten dan dapat diaudit.

---

# 8. INFORMATION ARCHITECTURE & PAGE STRUCTURE

## 8.1 Owner Navigation

**Dashboard**
- Overview.
- Branch Comparison.
- AI Insights Preview.

**Branches**
- Branch List.
- Branch Detail.
- Create/Edit Branch.

**Sales**
- All Transactions.
- Transaction Detail.
- Sales Analytics.

**Products**
- Product Catalog.
- Categories.
- Recipes.
- Branch Pricing.
- Price Change Requests.

**Inventory**
- Inventory Overview.
- Stock Movements.
- Stock Transfers.
- Stock Adjustments.
- Purchasing.
- Suppliers.

**Employees**
- Employee Directory.
- Work Schedules.
- Payroll.

**Reports**
- Sales Report.
- Product Performance.
- Branch Performance.
- Inventory Report.
- Expense Report.
- Profitability Report.

**AI Intelligence**
- AI Business Assistant.
- Business Insights.
- Insight History.

**Management**
- User Accounts.
- Access Control.
- Operational Expenses.
- Audit Logs.
- Settings.

## 8.2 Branch Manager Navigation

**Dashboard**
- Branch Overview.

**Sales**
- Transactions.
- Sales Performance.

**Products**
- Product Availability.
- Price Change Requests.

**Inventory**
- Stock Overview.
- Stock Movements.
- Adjustments.
- Purchasing.
- Stock Transfers.

**Operations**
- Expenses.
- Employees.
- Work Schedules.
- Approvals.

**Reports**
- Branch Sales.
- Product Performance.
- Inventory Usage.
- Expenses.

**Account**
- Profile.
- Settings.

## 8.3 Cashier Navigation

**POS**
- Product Catalog.
- Shopping Cart.
- Checkout.

**Transactions**
- My Transactions.
- Transaction Detail.
- Refund Requests.

**Shifts**
- Open Shift.
- Current Shift.
- Close Shift.
- Shift History.

**Account**
- Profile.
- Settings.

---

# 9. UI/UX REQUIREMENTS

## 9.1 Design Direction

Veltrix menggunakan desain modern business SaaS dashboard.

Visual characteristics:

- Clean and professional interface.
- Minimal visual clutter.
- Consistent spacing and typography.
- Clear information hierarchy.
- Modern charts and tables.
- Responsive navigation.
- Accessible interaction states.
- Consistent status indicators.

## 9.2 Suggested Design System

**Primary Theme**

- Background: Soft white / light neutral.
- Primary: Deep coffee brown.
- Secondary: Warm beige.
- Accent: Muted green.
- Text: Dark charcoal.
- Success: Green.
- Warning: Amber.
- Error: Red.

Pemilihan warna harus memenuhi kebutuhan kontras dan aksesibilitas.

## 9.3 Dashboard UI

Owner dan Manager menggunakan sidebar navigation dengan:

- Application logo.
- Main navigation.
- Active navigation indicator.
- User role indicator.
- Profile and logout.
- Collapsible sidebar.

Konten utama menggunakan cards, tables, charts, dan filter yang tertata rapi.

## 9.4 POS UI

POS mengutamakan kecepatan:

- Product grid.
- Category filters.
- Search.
- Persistent cart panel.
- Large checkout action.
- Clear payment dialog.
- Visible payment status.

POS harus nyaman digunakan pada desktop dan tablet.

## 9.5 AI Assistant UI

Halaman AI menyediakan:

- Chat interface.
- Message history.
- Suggested questions.
- Structured responses.
- Loading indicator.
- Error and retry states.
- Data reference and analysis period.

## 9.6 AI Insights UI

Insight dapat ditampilkan dalam cards yang memperlihatkan:

- Severity.
- Insight title.
- Key metric.
- Branch.
- Analysis period.
- Recommendation.
- View details.

## 9.7 Responsive Behavior

- Desktop: full dashboard experience.
- Tablet: responsive dashboard and POS.
- Mobile browser: monitoring dan fitur sederhana tetap dapat digunakan, dengan tabel dan grafik yang menyesuaikan layar.

Aplikasi native mobile tidak termasuk cakupan awal.

---

# 10. TECHNICAL ARCHITECTURE

## 10.1 Technology Stack

| Layer | Technology |
|---|---|
| Frontend | React + TypeScript |
| Frontend-Backend Bridge | Inertia.js |
| Backend | Laravel |
| Styling | Tailwind CSS |
| UI Components | shadcn/ui |
| Charts | Recharts |
| Database | PostgreSQL |
| Database Hosting | Neon |
| ORM | Laravel Eloquent |
| Authentication | Laravel Session Authentication |
| Authorization | Laravel Policies / Gates |
| AI Integration | Laravel AI SDK |
| AI Provider | Google Gemini API |
| Background Jobs | Laravel Queue |
| Scheduling | Laravel Scheduler |
| Real-Time (Optional) | Laravel Reverb |
| File Storage | S3-compatible object storage or equivalent |
| Testing | Pest/PHPUnit, frontend testing tools |

Framework versions should be pinned to compatible stable releases during project initialization.

## 10.2 Architecture Pattern

**Modular Monolith**

Satu aplikasi Laravel menangani backend dan mengirimkan halaman React melalui Inertia.

Modul bisnis dipisahkan secara logis:

- Identity & Access.
- Branches.
- Sales.
- Products.
- Inventory.
- Purchasing.
- Employees.
- Payroll.
- Finance.
- Analytics.
- AI Intelligence.
- Notifications.

Setiap modul dapat memiliki controller, services, policies, jobs, events, dan repository/query objects sesuai kebutuhan.

## 10.3 Backend Design Principles

- Controller menangani request dan response.
- Form Request melakukan validasi.
- Policies/Gates menangani authorization.
- Service classes mengelola business logic.
- Eloquent menangani akses data.
- Database transactions menjaga konsistensi operasi penting.
- Jobs memproses pekerjaan berat.
- Events digunakan untuk memicu proses lanjutan secara terkontrol.
- Analytics query dipisahkan dari logika transaksi.

## 10.4 AI Architecture

AI Integration Layer menyediakan:

- Agent configuration.
- Tool definitions.
- Analytics tools.
- Response validation.
- Logging.
- Error handling.
- Usage monitoring.

Alur utama:

React Chat UI → Laravel Controller → AI Agent → Authorized Analytics Tool → PostgreSQL → AI Response → React UI.

Automated Insights:

Laravel Scheduler → Queue Job → Analytics Engine → Optional Gemini Interpretation → Insights Database → Dashboard/Notification.

## 10.5 Real-Time Data Strategy

Versi awal menggunakan refresh atau polling terkontrol untuk dashboard.

Laravel Broadcasting/Reverb dapat ditambahkan ketika pembaruan instan antar pengguna diperlukan.

Transaksi bisnis tetap harus benar meskipun koneksi real-time terputus.

## 10.6 Database Principles

- Primary key menggunakan identifier konsisten.
- Foreign key digunakan pada relasi bisnis penting.
- Indexed foreign keys dan query filter yang sering digunakan.
- Timestamps untuk aktivitas penting.
- Soft deletion atau status inactive bagi data dengan histori.
- Relasi branch dipertahankan pada data operasional.
- Harga transaksi disimpan sebagai snapshot.
- Nilai mata uang menggunakan tipe presisi, bukan floating-point.
- Kuantitas bahan mendukung pecahan dengan presisi terdefinisi.
- Semua mutasi finansial dan stok memiliki jejak perubahan.

---

# 11. DATABASE DESIGN

## 11.1 Identity & Branch

**users**
- id
- name
- email
- password
- role
- status
- last_login_at
- created_at
- updated_at

**branches**
- id
- code
- name
- address
- phone
- timezone
- status
- created_at
- updated_at

**user_branch_assignments**
- id
- user_id
- branch_id
- is_active
- assigned_at

**employees**
- id
- user_id (nullable)
- branch_id
- employee_code
- full_name
- phone
- position
- join_date
- employment_status
- created_at
- updated_at

## 11.2 Product Catalog

**categories**
- id
- name
- status

**products**
- id
- category_id
- sku
- name
- description
- image_path
- base_price
- status
- created_at
- updated_at

**branch_products**
- id
- branch_id
- product_id
- selling_price
- is_available

**product_price_requests**
- id
- branch_id
- product_id
- requested_by
- proposed_price
- status
- reviewed_by
- reviewed_at

## 11.3 Recipe & Inventory

**ingredients**
- id
- name
- base_unit
- status

**product_recipe_versions**
- id
- product_id
- version_number
- effective_from
- status

**recipe_items**
- id
- recipe_version_id
- ingredient_id
- quantity_required

**branch_inventory**
- id
- branch_id
- ingredient_id
- current_quantity
- minimum_quantity
- updated_at

**inventory_movements**
- id
- branch_id
- ingredient_id
- movement_type
- quantity_change
- unit_cost
- reference_type
- reference_id
- performed_by
- created_at

**stock_adjustments**
- id
- branch_id
- ingredient_id
- quantity_change
- reason
- approved_by
- created_at

**stock_transfers**
- id
- source_branch_id
- destination_branch_id
- status
- requested_by
- approved_by
- created_at

**stock_transfer_items**
- id
- transfer_id
- ingredient_id
- requested_quantity
- received_quantity

## 11.4 Sales & POS

**cashier_shifts**
- id
- branch_id
- cashier_id
- opening_cash
- expected_closing_cash
- actual_closing_cash
- opened_at
- closed_at
- status

**orders**
- id
- order_number
- branch_id
- cashier_id
- shift_id
- subtotal
- discount_amount
- total_amount
- status
- completed_at
- created_at

**order_items**
- id
- order_id
- product_id
- product_name_snapshot
- unit_price
- quantity
- total_price
- recipe_version_id

**payments**
- id
- order_id
- method
- amount
- status
- reference_number
- paid_at

**refunds**
- id
- order_id
- amount
- reason
- status
- requested_by
- approved_by
- processed_at

Production implementation must also represent item-level refund quantities when partial refunds are supported.

## 11.5 Purchasing

**suppliers**
- id
- name
- phone
- email
- status

**purchase_orders**
- id
- branch_id
- supplier_id
- purchase_number
- total_amount
- status
- ordered_at

**purchase_order_items**
- id
- purchase_order_id
- ingredient_id
- ordered_quantity
- unit_price

**stock_receipts**
- id
- purchase_order_id
- branch_id
- received_by
- received_at

**stock_receipt_items**
- id
- stock_receipt_id
- ingredient_id
- quantity_received
- unit_cost

## 11.6 Finance & Payroll

**expense_categories**
- id
- name
- status

**expenses**
- id
- branch_id (nullable)
- category_id
- amount
- expense_date
- description
- approval_status
- payment_status
- created_by

**employee_compensations**
- id
- employee_id
- base_salary
- effective_from
- effective_until

**payroll_periods**
- id
- period_start
- period_end
- status
- approved_by

**payroll_items**
- id
- payroll_period_id
- employee_id
- branch_id
- base_salary
- allowance
- bonus
- deduction
- net_pay
- payment_status

**payroll_adjustments**
- id
- payroll_item_id
- adjustment_type
- amount
- reason

## 11.7 AI & System

**ai_conversations**
- id
- user_id
- title
- created_at
- updated_at

**ai_messages**
- id
- conversation_id
- sender
- content
- created_at

**ai_tool_executions**
- id
- conversation_id
- tool_name
- status
- duration_ms
- created_at

**business_insights**
- id
- branch_id (nullable)
- insight_type
- severity
- title
- summary
- metric_payload
- recommendation
- period_start
- period_end
- status
- generated_at

**notifications**
- id
- user_id
- title
- message
- type
- is_read
- created_at

**audit_logs**
- id
- actor_id
- action
- entity_type
- entity_id
- changes
- created_at

## 11.8 Database Constraints

- Identifiers and unique business codes must have database constraints.
- Branch-product combinations must be unique.
- Inventory records must be unique per branch and ingredient.
- Order numbers and payment references must prevent unintended duplication.
- Foreign keys must maintain referential integrity.
- Sensitive records must not be deleted by cascading operations without explicit business justification.
- Snapshot fields must retain historical accuracy.

The tables above are the conceptual starting schema. Additional supporting tables, constraints, indexes, and accounting/approval records will be finalized during detailed database design.

---

# 12. NON-FUNCTIONAL REQUIREMENTS

## 12.1 Performance

**NFR-01**

Dashboard utama ditargetkan memuat data inti dalam waktu kurang dari 3 detik pada kondisi jaringan dan dataset pengujian yang ditentukan.

**NFR-02**

Interaksi lokal POS seperti memilih produk dan mengubah cart harus terasa responsif tanpa request server pada setiap perubahan kuantitas.

**NFR-03**

Query laporan berat harus menggunakan agregasi, caching, pagination, atau optimasi sesuai kebutuhan.

**NFR-04**

Pemrosesan AI tidak boleh menghambat transaksi operasional.

## 12.2 Security

**NFR-05**

Semua endpoint dan tindakan sensitif harus menerapkan authorization di backend.

**NFR-06**

Aplikasi harus menggunakan HTTPS pada lingkungan produksi.

**NFR-07**

Password disimpan menggunakan hashing aman bawaan framework.

**NFR-08**

Sistem menerapkan CSRF protection, validasi input, rate limiting, dan perlindungan terhadap akses lintas cabang.

**NFR-09**

API key dan secret tidak disimpan di frontend.

**NFR-10**

Data payroll dan informasi pribadi dilindungi sesuai kebutuhan akses dan peraturan privasi yang relevan.

## 12.3 Reliability & Integrity

**NFR-11**

Transaksi POS dan perubahan stok harus menggunakan mekanisme atomik.

**NFR-12**

Sistem harus menangani kegagalan sebagian proses tanpa menciptakan data tidak konsisten.

**NFR-13**

Queue jobs harus aman terhadap retry dan pemrosesan berulang.

**NFR-14**

Backup database dan pengujian pemulihan harus direncanakan sebelum production deployment.

## 12.4 Availability

**NFR-15**

Target awal ketersediaan layanan adalah 99,5% setelah deployment produksi, dengan ketergantungan terhadap kemampuan dan SLA infrastruktur yang dipilih.

## 12.5 Scalability

**NFR-16**

Sistem mendukung penambahan cabang tanpa perubahan arsitektur dasar.

**NFR-17**

Database dan laporan dirancang agar jumlah transaksi yang bertambah tidak langsung memperlambat POS.

## 12.6 Usability

**NFR-18**

Antarmuka menggunakan bahasa Indonesia yang jelas dan konsisten.

**NFR-19**

Status loading, empty state, success, dan error harus terlihat.

**NFR-20**

Operasi penting memiliki konfirmasi yang sesuai.

## 12.7 Maintainability

**NFR-21**

Struktur kode modular, konsisten, dan terdokumentasi.

**NFR-22**

Business logic utama memiliki automated tests.

**NFR-23**

Database changes dikelola menggunakan migrations.

## 12.8 AI-Specific Requirements

**NFR-24**

AI harus transparan mengenai periode, batas data, dan hasil yang tidak pasti.

**NFR-25**

Penggunaan token/API harus dapat dimonitor.

**NFR-26**

AI memiliki timeout, retry policy, dan fallback ketika provider tidak tersedia.

**NFR-27**

Kegagalan AI tidak boleh menyebabkan POS, inventori, dan laporan utama tidak berfungsi.

---

# 13. ANALYTICS & FINANCIAL DEFINITIONS

Agar hasil dashboard, laporan, dan AI konsisten, sistem harus menggunakan definisi metrik yang sama.

| Metric | Definition |
|---|---|
| Gross Sales | Nilai penjualan sebelum diskon dan refund |
| Net Sales | Penjualan setelah diskon dan refund, dengan pajak dan service charge dipisahkan bila berlaku |
| Transaction Count | Jumlah transaksi final sesuai periode |
| Average Order Value | Net Sales / Completed Transaction Count |
| Units Sold | Total kuantitas produk terjual setelah koreksi refund |
| COGS | Biaya bahan atau barang yang dialokasikan ke penjualan |
| Gross Profit | Net Sales − COGS |
| Operating Expenses | Biaya operasional yang diakui dalam periode |
| Payroll Expenses | Beban tenaga kerja yang dialokasikan ke periode |
| Estimated Operating Profit | Gross Profit − Operating Expenses − Payroll Expenses |
| Sales Growth | (Current Period − Previous Period) / Previous Period × 100% |

Apabila nilai periode sebelumnya nol, sistem tidak boleh menghasilkan persentase pertumbuhan yang menyesatkan.

Penggunaan angka pembanding harus mempertimbangkan durasi periode yang sama, cabang yang sama, serta status kelengkapan data.

---

# 14. EDGE CASES & ERROR HANDLING

## 14.1 POS

- Kasir mengirim request pembayaran dua kali.
- Internet terputus ketika checkout.
- Dua kasir menjual produk yang menggunakan stok terakhir secara bersamaan.
- Harga produk berubah ketika cart masih terbuka.
- Shift kasir belum dibuka.
- Pembayaran gagal atau statusnya belum dapat dipastikan.
- Transaksi berhasil tetapi pembuatan struk gagal.
- Refund hanya untuk sebagian item.

## 14.2 Inventory

- Stok tidak cukup.
- Resep produk belum dibuat.
- Unit bahan tidak sesuai.
- Bahan diterima hanya sebagian.
- Transfer stok belum diterima cabang tujuan.
- Stock opname menemukan selisih.
- Bahan rusak atau kedaluwarsa.
- Harga bahan berubah.

## 14.3 User Access

- Manager mencoba mengakses cabang lain.
- Akun karyawan dinonaktifkan saat sesi masih berjalan.
- Owner mengubah role pengguna.
- Pengguna mencoba mengubah ID pada URL untuk mengakses data terlarang.

## 14.4 AI

- Gemini API tidak tersedia.
- Rate limit provider tercapai.
- Pertanyaan tidak relevan dengan data bisnis.
- Data historis belum mencukupi.
- Tool menghasilkan data kosong.
- Model menyusun kesimpulan yang tidak didukung.
- Insight yang sama terdeteksi berulang.
- Pengguna mencoba menyisipkan instruksi berbahaya melalui input chat atau data bisnis.

## 14.5 Reporting

- Transaksi dibatalkan setelah periode laporan ditutup.
- Pengeluaran dicatat terlambat.
- Harga bahan berubah.
- Cabang baru belum memiliki data.
- Periode pembanding tidak lengkap.
- Payroll belum final.

Sistem harus memiliki perilaku yang terdefinisi dan dapat diuji untuk setiap kondisi tersebut.

---

# 15. DEVELOPMENT ROADMAP

Roadmap ini menunjukkan urutan implementasi, bukan komitmen tanggal penyelesaian.

## Phase 1 — Foundation & Authentication

**Goal:** Membangun struktur dasar aplikasi.

Deliverables:

- Laravel + React Inertia setup.
- Design system.
- Database configuration.
- Authentication.
- Role-based authorization.
- User account management.
- Branch management.
- Application layout.

**Exit Criteria:** Semua role dapat login dan hanya mengakses halaman yang diizinkan.

## Phase 2 — Products & POS

**Goal:** Menyelesaikan alur penjualan.

Deliverables:

- Product categories.
- Product management.
- Branch-specific pricing.
- POS interface.
- Shopping cart.
- Payment recording.
- Cashier shifts.
- Transaction history.
- Digital receipts.
- Refund approval workflow.

**Exit Criteria:** Cashier dapat menyelesaikan transaksi dan sistem menyimpan data penjualan secara konsisten.

## Phase 3 — Inventory & Purchasing

**Goal:** Mengintegrasikan penjualan dengan stok bahan.

Deliverables:

- Ingredient master.
- Recipe management.
- Branch inventory.
- Automatic stock deduction.
- Stock movements.
- Stock adjustment.
- Low stock alerts.
- Purchasing.
- Stock receiving.
- Branch stock transfer.

**Exit Criteria:** Penjualan memengaruhi inventori dengan benar dan seluruh perubahan stok dapat diaudit.

## Phase 4 — Operational & Financial Management

**Goal:** Mencatat biaya operasional dan manajemen karyawan.

Deliverables:

- Employee directory.
- Work schedules.
- Expense categories.
- Expense recording.
- Payroll configuration.
- Payroll calculation.
- Payslip.
- Payroll reports.

**Exit Criteria:** Biaya operasional dan tenaga kerja dapat dicatat per cabang dan periode.

## Phase 5 — Owner Dashboard & Reports

**Goal:** Menyediakan Business Intelligence berbasis data.

Deliverables:

- Consolidated owner dashboard.
- Branch manager dashboard.
- Revenue charts.
- Branch comparisons.
- Product performance.
- Inventory reports.
- Operational expenses.
- Profitability report.
- Report exports.
- Analytics query optimization.

**Exit Criteria:** Owner dapat mengevaluasi performa semua cabang dan hasilnya cocok dengan data sumber.

## Phase 6 — AI Business Assistant

**Goal:** Mengaktifkan analisis bisnis berbasis bahasa alami.

Deliverables:

- Laravel AI SDK integration.
- Gemini provider configuration.
- Authorized analytics tools.
- AI chat interface.
- Chat history.
- Structured response handling.
- API usage monitoring.
- AI evaluation tests.

**Exit Criteria:** Owner dapat mengajukan pertanyaan bisnis dan menerima jawaban berdasarkan data yang diverifikasi.

## Phase 7 — Automated AI Insights

**Goal:** Menghasilkan insight bisnis secara proaktif.

Deliverables:

- Analytics detection rules.
- Sales anomaly detection.
- Product trend detection.
- Stock forecasting.
- Expense anomaly detection.
- Insight generation jobs.
- Insight history.
- Notifications.
- Recommendation generation.

**Exit Criteria:** Sistem dapat mendeteksi skenario masalah dan peluang sesuai aturan, menyimpan insight, serta menjelaskan temuan dengan data pendukung.

## Phase 8 — Quality Assurance & Deployment

**Goal:** Menyiapkan aplikasi untuk penggunaan yang stabil.

Deliverables:

- Automated testing.
- Authorization testing.
- Database integrity testing.
- Concurrency testing.
- AI evaluation.
- Performance optimization.
- Security review.
- Production configuration.
- Backup and recovery plan.
- Deployment documentation.

**Exit Criteria:** Seluruh critical acceptance criteria terpenuhi dan tidak ada defect kritis yang belum ditangani.

---

# 16. TESTING STRATEGY

## 16.1 Unit Testing

Menguji:

- Harga dan total transaksi.
- Diskon dan refund.
- Recipe consumption.
- Stock calculations.
- Payroll calculations.
- Sales growth formulas.
- Profit calculations.
- Insight detection rules.

## 16.2 Integration Testing

Menguji:

- POS dengan database.
- POS dengan inventory.
- Purchasing dengan stock receiving.
- Refund dengan sales reports.
- Payroll dengan expense reporting.
- AI tools dengan Analytics Service.

## 16.3 Authorization Testing

Menguji seluruh role untuk memastikan tidak ada akses yang melampaui izin, termasuk manipulasi parameter cabang dan akses langsung ke endpoint.

## 16.4 Concurrency Testing

Menguji:

- Penjualan bersamaan menggunakan stok terbatas.
- Request checkout berulang.
- Stock transfer bersamaan.
- Idempotent job execution.

## 16.5 AI Evaluation

Membuat dataset pertanyaan dan hasil yang telah diketahui.

Evaluation categories:

- Product sales.
- Branch revenue.
- Sales comparisons.
- Expenses.
- Inventory.
- Profitability.
- Missing data.
- Unauthorized requests.

Menilai:

- Correct tool selection.
- Numerical correctness.
- Period correctness.
- Grounding.
- Permission compliance.
- Clarity.

## 16.6 User Acceptance Testing

Skenario UAT harus mencakup setidaknya:

1. Owner membuat cabang dan akun.
2. Owner membuat produk dan resep.
3. Manager menerima bahan baku.
4. Cashier menjual produk.
5. Inventory berkurang.
6. Manager melihat laporan cabang.
7. Owner melihat laporan gabungan.
8. Owner bertanya kepada AI.
9. Sistem menghasilkan insight otomatis.
10. Owner memeriksa rekomendasi dan data pendukung.

---

# 17. RISKS & MITIGATION

| Risk | Impact | Mitigation |
|---|---|---|
| Scope terlalu besar | Pengembangan tidak selesai | Implementasi bertahap berdasarkan dependency |
| Inconsistent inventory | Stok dan laporan salah | Transactions, locking, movement ledger |
| Incorrect financial reports | Keputusan bisnis keliru | Definisi metrik, tests, reconciliation |
| AI hallucination | Insight menyesatkan | Deterministic analytics, tool calling, grounding |
| AI API costs | Biaya meningkat | Usage limits, caching, scheduled analysis |
| Cross-branch data leakage | Risiko keamanan | Backend authorization, automated tests |
| Slow reporting queries | Dashboard lambat | Indexing, aggregates, optimization |
| Queue failures | Insight terlambat | Monitoring, retries, idempotency |
| Incomplete operational data | Analisis tidak akurat | Data validation and completeness indicators |
| Deployment constraints | Background jobs tidak berjalan | Hosting yang mendukung workers dan scheduler |

---

# 18. FINAL PRODUCT ACCEPTANCE CRITERIA

Veltrix dinyatakan memenuhi spesifikasi utama apabila:

- Owner dapat memonitor seluruh cabang melalui satu dashboard.
- Branch Manager hanya dapat mengelola cabang yang ditugaskan.
- Cashier dapat memproses penjualan melalui POS.
- Transaksi dan inventori tetap konsisten.
- Resep menentukan pengurangan bahan yang relevan.
- Restocking, stock adjustment, dan transfer memiliki riwayat yang jelas.
- Pengeluaran, karyawan, dan payroll dapat dicatat.
- Owner dapat melihat laporan penjualan dan estimasi profitabilitas.
- AI Business Assistant dapat menjawab pertanyaan berdasarkan data database.
- Automated AI Insights dapat mendeteksi masalah atau peluang berdasarkan aturan analitik.
- Sistem memiliki authentication, authorization, audit logs, dan error handling.
- Aplikasi dapat digunakan pada desktop dan tablet.
- Modul bisnis utama dapat berjalan ketika provider AI tidak tersedia.
- Pengujian fungsional dan integritas data kritis berhasil.

---

# 19. FUTURE ENHANCEMENTS

Fitur berikut tidak menjadi persyaratan rilis awal, tetapi dapat dikembangkan setelah sistem inti stabil:

- Customer loyalty program.
- Customer-facing online ordering.
- Delivery platform integration.
- Real payment gateway integration.
- Advanced demand forecasting.
- Purchase order recommendations.
- Employee attendance integration.
- Multi-company SaaS architecture.
- Mobile owner application.
- Offline-capable POS.
- Automated financial reconciliation.
- More advanced cost accounting.
- AI-assisted what-if simulations.

---

# 20. PRODUCT SUMMARY

Veltrix dirancang sebagai platform manajemen bisnis coffee shop multi-cabang yang mengintegrasikan operasional, pengawasan, dan analitik dalam satu sistem.

Sistem menggunakan tiga role:

**Owner** berfokus pada pengawasan, analisis bisnis, laporan keuangan, dan AI Business Intelligence.

**Branch Manager** berfokus pada pengelolaan operasional, inventori, pengeluaran, karyawan, dan laporan cabang.

**Cashier** berfokus pada transaksi penjualan melalui Point of Sale.

Keunggulan utama Veltrix adalah keterhubungan data operasional dengan analitik cerdas.

Setiap transaksi yang dicatat tidak hanya menghasilkan data penjualan, tetapi juga memperbarui penggunaan bahan, memengaruhi laporan, dan menjadi sumber untuk analisis bisnis.

AI kemudian memanfaatkan data terstruktur tersebut untuk menjawab pertanyaan serta menghasilkan insight yang relevan.

**Product Positioning:**

*Veltrix — One Platform to Manage, Monitor, and Understand Every Branch.*

**End of PRD — Version 1.0**
