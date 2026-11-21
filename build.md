# MASTER PROMPT AI CODING AGENT — VELTRIX

## 1. PERAN DAN TUJUAN

Bertindaklah sebagai Senior Full-Stack Software Engineer, Software Architect, Laravel Developer, React Developer, Database Engineer, AI Engineer, sekaligus UI/UX Designer profesional.

Tugasmu adalah membangun aplikasi web bernama **Veltrix**, yaitu platform manajemen operasional dan analitik bisnis coffee shop multi-cabang yang terintegrasi dengan Artificial Intelligence (AI).

Project ini merupakan aplikasi full-stack berskala besar yang harus dirancang dengan arsitektur yang baik, database terstruktur, keamanan yang memadai, tampilan modern, dan seluruh fitur yang saling terintegrasi.

**Informasi Project:**

- Nama aplikasi: Veltrix
- Jenis aplikasi: Web Application
- Konsep: Multi-Branch Coffee Shop Management & AI Business Intelligence
- Target pengguna: Owner, Branch Manager, dan Cashier
- Jumlah cabang awal: 3 cabang
- Bahasa antarmuka: Bahasa Indonesia
- Mata uang: Rupiah (IDR)
- Arsitektur: Modular Monolith
- Pendekatan: Full-Stack Development

**Tujuan utama Veltrix:**

Membantu pemilik bisnis coffee shop memantau penjualan, inventori, pengeluaran, karyawan, payroll, serta performa seluruh cabang melalui satu aplikasi.

Veltrix juga menyediakan AI Business Assistant dan Automated AI Insights untuk membantu owner memahami data bisnis, menemukan masalah operasional, serta memperoleh rekomendasi berdasarkan data.

Aplikasi harus dikembangkan sebagai sistem yang benar-benar dapat digunakan, bukan hanya prototype, landing page, atau dashboard dengan data statis.

---

# 2. TECH STACK WAJIB

Gunakan teknologi berikut:

### Frontend
- React dengan TypeScript.
- Inertia.js sebagai penghubung React dan Laravel.
- Tailwind CSS untuk styling.
- shadcn/ui untuk komponen antarmuka.
- Lucide React untuk icon.
- Recharts untuk visualisasi data.

### Backend
- Laravel sebagai backend utama.
- PHP versi stabil yang kompatibel.
- Laravel Eloquent ORM.
- Laravel Form Request untuk validasi.
- Laravel Policies dan Gates untuk authorization.
- Laravel Queue untuk background jobs.
- Laravel Scheduler untuk tugas otomatis.

### Database
- PostgreSQL.
- Neon PostgreSQL sebagai opsi database cloud.
- Laravel Migrations dan Seeders.

### Artificial Intelligence
- Laravel AI SDK.
- Google Gemini API.
- Tool Calling untuk mengakses data bisnis secara terkontrol.
- Laravel Analytics Service untuk perhitungan bisnis.

### Development
- Composer.
- Node.js dan npm.
- Vite.
- Git.
- Environment variables.

Gunakan versi stabil dan kompatibel yang tersedia pada saat implementasi.

**Larangan dan batasan teknis:**

- Jangan menggunakan Docker.
- Jangan mengganti Laravel dengan Express.js, FastAPI, atau Go.
- Jangan menggunakan Next.js.
- Jangan memisahkan React menjadi frontend SPA dengan REST API untuk setiap halaman.
- Jangan menggunakan microservices.
- Jangan menggunakan MongoDB.
- Jangan menggunakan Firebase sebagai database utama.
- Jangan membuat payment gateway sungguhan.
- Jangan membuat fitur AI palsu dengan jawaban yang di-hardcode.
- Jangan menggunakan data dummy sebagai sumber data utama aplikasi ketika sistem sudah beroperasi.

Gunakan Laravel dan React melalui Inertia.js dalam satu aplikasi terintegrasi.

REST API internal boleh ditambahkan apabila diperlukan untuk interaksi tertentu, tetapi bukan menjadi arsitektur utama aplikasi.

---

# 3. ARSITEKTUR APLIKASI

Gunakan pendekatan **Modular Monolith**.

Seluruh backend berada dalam satu aplikasi Laravel, tetapi pisahkan logika bisnis secara terstruktur.

Modul utama:

1. Authentication & Authorization
2. Branch Management
3. Product Management
4. Recipe Management
5. Point of Sale
6. Inventory Management
7. Purchasing
8. Employee Management
9. Payroll Management
10. Operational Expenses
11. Sales Analytics
12. Financial Reports
13. AI Business Assistant
14. Automated AI Insights
15. Notifications
16. Audit Logs
17. System Settings

Pisahkan tanggung jawab masing-masing bagian.

Contoh struktur:

- Controllers: menangani request dan response.
- Form Requests: validasi input.
- Models: representasi data.
- Services: logika bisnis.
- Policies: pengaturan izin akses.
- Jobs: proses background.
- Events: peristiwa aplikasi.
- Analytics Services: penghitungan metrik bisnis.
- AI Services: integrasi AI.
- React Pages: halaman aplikasi.
- Components: komponen UI reusable.
- Layouts: layout berdasarkan role.

Hindari menempatkan seluruh business logic di dalam controller.

Gunakan kode yang bersih, mudah dipelihara, dan dapat dikembangkan.

---

# 4. KONSEP MULTI-BRANCH

Veltrix digunakan oleh satu perusahaan coffee shop yang memiliki beberapa cabang.

Buat tiga cabang awal:

1. Veltrix Coffee — Depok.
2. Veltrix Coffee — Jakarta.
3. Veltrix Coffee — Bogor.

Ketiga cabang harus memiliki data operasional masing-masing, tetapi terhubung ke satu database terpusat.

Sistem harus mendukung penambahan cabang baru tanpa mengubah struktur utama.

Setiap transaksi, stok, pengeluaran, dan data karyawan harus memiliki relasi cabang yang jelas.

Owner memiliki akses seluruh cabang, sedangkan Branch Manager dan Cashier hanya mengakses cabang tempat mereka ditugaskan.

**Penting:**

Jangan membuat tiga database terpisah.

Gunakan satu database PostgreSQL dengan struktur relasional yang mendukung multi-branch.

---

# 5. ROLE DAN HAK AKSES

Sistem harus menyediakan tiga role.

## A. OWNER

Owner merupakan pengguna dengan hak akses tertinggi.

Owner dapat:

- Melihat dashboard gabungan seluruh cabang.
- Memantau performa setiap cabang.
- Membandingkan pendapatan dan keuntungan cabang.
- Melihat seluruh transaksi.
- Mengelola cabang.
- Mengelola produk dan kategori.
- Mengatur harga per cabang.
- Mengelola resep produk.
- Memantau seluruh inventori.
- Melihat pembelian bahan baku.
- Mengelola karyawan.
- Mengelola payroll.
- Melihat pengeluaran operasional.
- Mengakses laporan bisnis.
- Mengakses AI Business Assistant.
- Melihat AI Business Insights.
- Mengelola akun Manager dan Cashier.
- Melihat audit log.
- Mengatur konfigurasi aplikasi.

## B. BRANCH MANAGER

Branch Manager bertanggung jawab atas satu cabang.

Manager dapat:

- Melihat dashboard cabangnya.
- Melihat riwayat transaksi cabangnya.
- Memantau performa produk.
- Mengelola ketersediaan menu.
- Mengajukan perubahan harga.
- Mengelola inventori bahan baku.
- Melakukan stock adjustment.
- Mengelola pembelian bahan.
- Mencatat penerimaan stok.
- Mengelola pengeluaran operasional.
- Melihat dan mengelola karyawan cabangnya.
- Mengelola jadwal kerja.
- Melihat laporan operasional.
- Menyetujui refund sesuai izin.
- Melihat notifikasi stok dan operasional.

Manager tidak boleh mengakses data internal cabang lain.

## C. CASHIER

Cashier menggunakan sistem Point of Sale.

Cashier dapat:

- Login ke aplikasi.
- Membuka shift kasir.
- Mengakses POS.
- Melihat produk yang tersedia.
- Mencari dan memilih produk.
- Menambahkan produk ke keranjang.
- Mengubah kuantitas pesanan.
- Melakukan checkout.
- Mencatat metode pembayaran.
- Melihat riwayat transaksi miliknya.
- Membuat struk.
- Mengajukan refund.
- Menutup shift.
- Mengelola profil pribadi.

Cashier tidak boleh melihat laporan keuangan perusahaan, payroll, atau dashboard owner.

**Keamanan:**

Implementasikan Role-Based Access Control di backend Laravel.

Jangan hanya menyembunyikan menu berdasarkan role.

Setiap request harus diperiksa melalui middleware, policies, atau mekanisme authorization yang sesuai.

---

# 6. AUTHENTICATION & USER MANAGEMENT

Bangun sistem autentikasi yang mencakup:

- Login.
- Logout.
- Password hashing.
- Session management.
- Change password.
- Reset password.
- Account activation/deactivation.
- Role-based redirect.
- User profile.

Tidak perlu registrasi publik.

Akun Owner dibuat saat initial setup.

Owner dapat membuat akun Branch Manager dan Cashier.

Setelah login, pengguna diarahkan ke halaman sesuai role.

Pastikan session, CSRF protection, dan authorization bekerja dengan benar.

---

# 7. OWNER DASHBOARD

Bangun dashboard owner yang modern, informatif, dan profesional.

Halaman ini merupakan salah satu fitur utama aplikasi.

### A. Summary Cards

Tampilkan:

- Total omzet.
- Total transaksi.
- Rata-rata nilai transaksi.
- Total produk terjual.
- Estimasi laba kotor.
- Pengeluaran operasional.
- Estimasi laba operasional.
- Jumlah cabang aktif.

Gunakan perhitungan dari database.

### B. Revenue Analytics

Tampilkan grafik tren penjualan berdasarkan periode:

- Hari ini.
- 7 hari terakhir.
- Bulan ini.
- Rentang tanggal khusus.

Gunakan Recharts.

### C. Branch Comparison

Bandingkan seluruh cabang berdasarkan:

- Omzet.
- Jumlah transaksi.
- Rata-rata nilai transaksi.
- Jumlah produk terjual.
- Estimasi keuntungan.
- Pertumbuhan penjualan.

Sediakan filter cabang dan periode.

### D. Top Selling Products

Tampilkan produk terlaris berdasarkan:

- Jumlah unit terjual.
- Total pendapatan produk.

Tampilkan nama produk, gambar, kuantitas terjual, dan kontribusi penjualan.

### E. AI Insights Preview

Tampilkan insight bisnis terbaru dalam bentuk kartu yang informatif.

Contoh:

- Penjualan Bogor mengalami penurunan.
- Matcha Latte mengalami peningkatan penjualan.
- Stok susu di Jakarta diperkirakan segera habis.
- Pengeluaran listrik Depok meningkat signifikan.

### F. Recent Transactions

Tampilkan transaksi terbaru dari seluruh cabang.

Informasi meliputi:

- Transaction ID.
- Cabang.
- Kasir.
- Total transaksi.
- Metode pembayaran.
- Waktu transaksi.
- Status.

Dashboard harus terlihat seperti aplikasi business intelligence modern, bukan kumpulan kartu statistik tanpa keterkaitan.

---

# 8. BRANCH MANAGER DASHBOARD

Buat dashboard khusus untuk manager.

Informasi utama:

- Penjualan hari ini.
- Total transaksi hari ini.
- Rata-rata nilai transaksi.
- Grafik penjualan.
- Produk terlaris.
- Stok bahan menipis.
- Pengeluaran cabang.
- Aktivitas transaksi terbaru.
- Tugas atau persetujuan yang tertunda.

Semua data harus difilter berdasarkan cabang manager yang sedang login.

Manager tidak boleh dapat mengubah branch ID untuk mengakses cabang lain.

---

# 9. POINT OF SALE (POS)

Bangun sistem POS yang benar-benar berfungsi.

Desain POS harus cepat, responsif, dan nyaman digunakan oleh kasir.

### Tampilan POS

Gunakan layout:

- Sidebar atau navigasi kasir.
- Header dengan informasi shift.
- Search bar produk.
- Filter kategori.
- Grid produk dengan foto dan harga.
- Shopping cart pada sisi kanan.
- Checkout button yang jelas.

### Fitur POS

1. Menampilkan katalog produk.
2. Mencari produk.
3. Filter berdasarkan kategori.
4. Menambahkan produk ke cart.
5. Mengubah kuantitas.
6. Menghapus item.
7. Menghitung subtotal.
8. Menampilkan total pembayaran.
9. Memilih metode pembayaran.
10. Menghitung kembalian tunai.
11. Menyelesaikan transaksi.
12. Membuat struk.
13. Melihat riwayat transaksi.
14. Membuka dan menutup shift.
15. Mengajukan refund atau void.

Metode pembayaran:

- Cash.
- QRIS.
- Debit/Credit Card.
- E-Wallet.

Pembayaran non-tunai hanya dicatat secara manual. Tidak perlu integrasi payment gateway.

### Integrasi POS

Ketika transaksi berhasil:

- Transaksi tersimpan di PostgreSQL.
- Item penjualan tersimpan.
- Produk terjual dihitung.
- Bahan baku berkurang berdasarkan resep.
- Riwayat inventori diperbarui.
- Laporan penjualan menggunakan data terbaru.
- Dashboard owner dan manager dapat menampilkan perubahan.

Pastikan semua proses penting menggunakan database transaction.

Cegah transaksi ganda akibat double-click atau request berulang.

Jangan percaya harga yang dikirim frontend. Backend wajib memvalidasi harga final.

---

# 10. PRODUCT & MENU MANAGEMENT

Buat pengelolaan menu coffee shop.

Data produk:

- ID produk.
- SKU.
- Nama produk.
- Kategori.
- Deskripsi.
- Gambar.
- Harga dasar.
- Status.

Kategori contoh:

- Coffee.
- Non-Coffee.
- Tea.
- Food.
- Pastry.

Owner dapat membuat, mengubah, dan menonaktifkan produk.

Produk yang pernah terjual tidak boleh dihapus permanen karena dibutuhkan untuk riwayat transaksi.

### Branch-Specific Pricing

Harga produk dapat berbeda antar cabang.

Contoh:

Kopi Susu Gula Aren:

- Depok: Rp22.000.
- Jakarta: Rp25.000.
- Bogor: Rp23.000.

Owner memiliki kontrol atas perubahan harga.

Manager dapat mengajukan perubahan harga cabangnya, tetapi membutuhkan persetujuan Owner.

### Product Availability

Manager dapat mengatur apakah produk tersedia pada cabangnya.

Produk yang tidak tersedia tidak boleh diproses dalam checkout.

---

# 11. RECIPE MANAGEMENT

Buat sistem resep untuk menghubungkan menu dengan bahan baku.

Contoh:

Produk: Kopi Susu Gula Aren.

Komposisi:

- Biji kopi: 18 gram.
- Susu: 150 ml.
- Sirup gula aren: 20 ml.

Jika dua cup terjual, sistem harus menghitung kebutuhan bahan:

- Biji kopi: 36 gram.
- Susu: 300 ml.
- Sirup gula aren: 40 ml.

### Persyaratan

- Owner dapat membuat resep.
- Owner dapat mengubah resep.
- Setiap resep memiliki daftar bahan dan kuantitas.
- Gunakan satuan yang konsisten.
- Perubahan resep tidak mengubah transaksi historis.
- Sistem dapat menghitung estimasi biaya bahan setiap menu.

Dukung recipe versioning agar penjualan historis tetap akurat.

---

# 12. INVENTORY MANAGEMENT

Buat inventori berbasis cabang.

Setiap cabang memiliki stok bahan masing-masing.

Data yang ditampilkan:

- Nama bahan.
- Stok tersedia.
- Satuan.
- Minimum stok.
- Status stok.
- Estimasi nilai stok.

### Fitur

- Inventory overview.
- Automatic stock deduction.
- Manual stock adjustment.
- Low stock alerts.
- Stock movement history.
- Stock opname.
- Inter-branch stock transfer.
- Inventory valuation.

### Stock Movement

Setiap perubahan stok harus tercatat.

Jenis perubahan:

- Stock In.
- Stock Out.
- Sales Consumption.
- Adjustment.
- Transfer.
- Return.

Jangan memperbarui angka stok tanpa mencatat sumber perubahannya.

### Stock Transfer

Dukung perpindahan stok antar cabang.

Contoh:

Cabang Depok mengirim 5 liter susu ke Cabang Bogor.

Proses:

1. Manager membuat permintaan transfer.
2. Permintaan disetujui sesuai hak akses.
3. Stok sumber dialokasikan atau dikurangi saat pengiriman.
4. Cabang tujuan menerima barang.
5. Stok tujuan bertambah berdasarkan jumlah diterima.
6. Seluruh proses tercatat dalam riwayat.

### Low Stock Alert

Apabila stok mencapai batas minimum, sistem mengirim notifikasi.

Pastikan perubahan stok aman saat beberapa transaksi terjadi secara bersamaan.

---

# 13. PURCHASING MANAGEMENT

Buat modul pembelian bahan baku.

Fitur:

- Supplier management.
- Purchase order.
- Purchase order items.
- Purchase history.
- Stock receiving.
- Purchasing cost tracking.

Status pembelian:

- Draft.
- Ordered.
- Partially Received.
- Received.
- Cancelled.

Manager dapat mencatat pembelian bahan untuk cabangnya.

Stok tidak langsung bertambah ketika purchase order dibuat.

Stok hanya bertambah setelah penerimaan barang dikonfirmasi.

Catat harga bahan untuk mendukung penghitungan biaya persediaan dan margin.

---

# 14. OPERATIONAL EXPENSE MANAGEMENT

Buat modul pencatatan pengeluaran operasional.

Kategori contoh:

- Sewa tempat.
- Listrik.
- Air.
- Internet.
- Kebersihan.
- Maintenance.
- Marketing.
- Transportasi.
- Lainnya.

Data pengeluaran:

- Cabang.
- Kategori.
- Nominal.
- Tanggal.
- Deskripsi.
- Bukti pengeluaran.
- Status persetujuan.

Manager mencatat pengeluaran cabangnya.

Owner dapat melihat dan membandingkan pengeluaran seluruh cabang.

Sediakan laporan berdasarkan kategori dan periode.

---

# 15. EMPLOYEE MANAGEMENT

Buat sistem manajemen karyawan.

Informasi karyawan:

- Employee ID.
- Nama lengkap.
- Nomor kontak.
- Posisi.
- Cabang.
- Tanggal bergabung.
- Status kerja.

Fitur:

- Employee directory.
- Add/edit employee.
- Branch assignment.
- Employee status.
- Work schedule.
- Cashier activity overview.

Pisahkan data karyawan dari akun login karena tidak semua karyawan harus memiliki akun aplikasi.

Manager hanya dapat mengelola karyawan cabangnya sesuai izin.

---

# 16. PAYROLL MANAGEMENT

Bangun modul penggajian karyawan.

Fitur:

- Salary configuration.
- Payroll period.
- Payroll calculation.
- Allowances.
- Bonuses.
- Deductions.
- Payroll approval.
- Payment status.
- Payslip.
- Payroll history.

Gunakan rumus:

Gaji Bersih = Gaji Pokok + Tunjangan + Bonus − Potongan

Status payroll:

- Draft.
- Approved.
- Paid.

Pembayaran dilakukan di luar aplikasi dan statusnya dicatat secara manual.

Owner memiliki akses penuh terhadap payroll.

Pastikan data gaji tidak dapat diakses oleh Cashier atau Manager tanpa izin khusus.

---

# 17. REPORTS & BUSINESS ANALYTICS

Bangun halaman laporan lengkap.

### Sales Report

Menampilkan:

- Gross sales.
- Net sales.
- Total transaksi.
- Produk terjual.
- Average order value.
- Refund.
- Tren penjualan.

### Branch Performance

Bandingkan:

- Pendapatan.
- Transaksi.
- Produk terjual.
- Pertumbuhan.
- Pengeluaran.
- Estimasi keuntungan.

### Product Analytics

Menampilkan:

- Produk terlaris.
- Produk dengan penjualan rendah.
- Pendapatan per produk.
- Performa kategori.
- Estimasi margin.

### Inventory Report

Menampilkan:

- Penggunaan bahan.
- Restocking.
- Stock adjustments.
- Stock transfers.
- Inventory value.

### Financial Report

Menampilkan:

- Net sales.
- Cost of goods sold.
- Gross profit.
- Operational expenses.
- Payroll expenses.
- Estimated operating profit.

Gunakan rumus yang konsisten.

Jangan menyamakan omzet dengan profit.

Biaya pembelian bahan baku tidak boleh langsung dianggap seluruhnya sebagai beban penjualan pada saat dibeli.

Gunakan metode penilaian biaya persediaan yang konsisten dan terdokumentasi.

### Reporting Features

- Filter tanggal.
- Filter cabang.
- Filter kategori.
- Grafik interaktif.
- Tabel dengan pagination.
- Search.
- Sorting.
- Export CSV.
- Export PDF.

Semua angka harus berasal dari database.

---

# 18. AI BUSINESS ASSISTANT

Ini merupakan salah satu fitur unggulan Veltrix.

Bangun AI Business Assistant menggunakan:

- Laravel AI SDK.
- Google Gemini API.
- Tool Calling.
- PostgreSQL.
- Laravel Analytics Services.

AI harus dapat memahami pertanyaan bisnis dalam bahasa Indonesia.

### Contoh Pertanyaan

- Produk apa yang paling laris di seluruh cabang?
- Cabang mana yang menghasilkan omzet tertinggi?
- Berapa total pendapatan bulan ini?
- Apakah penjualan meningkat dibanding bulan lalu?
- Cabang mana yang mengalami penurunan?
- Berapa rata-rata transaksi per hari?
- Apa produk dengan margin terbesar?
- Berapa total pengeluaran operasional bulan ini?
- Bahan baku apa yang hampir habis?
- Cabang mana yang memiliki estimasi keuntungan paling tinggi?
- Bagaimana performa Depok dibanding Jakarta?
- Apa rekomendasi berdasarkan kondisi bisnis minggu ini?

### Cara Kerja

1. Owner mengirim pertanyaan.
2. Gemini memahami maksud pertanyaan.
3. Gemini memilih tool yang relevan.
4. Laravel memvalidasi parameter dan hak akses.
5. Analytics Service menjalankan query.
6. PostgreSQL mengembalikan hasil.
7. Gemini menyusun jawaban berdasarkan data.
8. Jawaban ditampilkan melalui chat.

### Tools yang Harus Dibuat

- getTopSellingProducts
- getSalesSummary
- getBranchRevenue
- compareBranches
- getProductPerformance
- getOperationalExpenses
- getProfitabilitySummary
- getInventoryStatus
- getLowStockIngredients
- getSalesTrends

Buat tools menggunakan fitur resmi Laravel AI SDK sesuai API yang tersedia pada versi terpasang.

### Ketentuan Penting

- AI tidak boleh menjalankan SQL bebas dari input pengguna.
- AI hanya dapat menggunakan tools yang disediakan.
- Perhitungan numerik dilakukan oleh backend.
- Model hanya menjelaskan dan menginterpretasikan hasil.
- AI harus menyebutkan periode analisis.
- Jika data tidak tersedia, AI harus menyatakannya.
- Jangan mengarang angka.
- Jangan membocorkan informasi sensitif.
- Simpan Gemini API key di environment variables.
- Jangan mengirim API key ke frontend.

### Chat Interface

Desain halaman AI seperti aplikasi conversational assistant modern.

Fitur:

- Chat input.
- Message bubbles.
- Chat history.
- Suggested questions.
- Loading state.
- Error state.
- Structured response.
- Ringkasan data pendukung.
- Informasi periode.

Simpan riwayat percakapan pada database dengan kebijakan retensi yang dapat dikonfigurasi.

---

# 19. AUTOMATED AI BUSINESS INSIGHTS

Bangun fitur AI yang dapat menghasilkan insight secara otomatis tanpa menunggu pertanyaan Owner.

Gunakan pendekatan hybrid:

**Analytics Engine + Rule-Based Detection + Gemini Interpretation.**

### Insight yang Harus Didukung

**1. Sales Decline Detection**

Mendeteksi penurunan penjualan dibandingkan periode sebelumnya.

**2. Sales Growth Detection**

Mengidentifikasi pertumbuhan penjualan.

**3. Product Trend Analysis**

Menganalisis perubahan popularitas produk.

**4. Low Stock Prediction**

Memperkirakan kapan bahan baku akan habis berdasarkan data konsumsi.

**5. Expense Anomaly Detection**

Menemukan pengeluaran yang meningkat secara tidak biasa.

**6. Branch Performance Comparison**

Mengidentifikasi cabang yang berkinerja lebih baik atau lebih buruk berdasarkan metrik tertentu.

**7. Business Recommendations**

Memberikan rekomendasi yang dapat dipertimbangkan oleh Owner.

### Contoh Insight

Judul: Penjualan Cabang Bogor Menurun

Deskripsi:

Penjualan Cabang Bogor mengalami penurunan 14% dibandingkan periode sebelumnya.

Rekomendasi:

Evaluasi performa produk dengan penurunan terbesar, pola transaksi berdasarkan jam, dan aktivitas promosi pada periode terkait.

### Insight Priority

Gunakan status:

- Critical.
- Warning.
- Informational.

### Insight Detail

Setiap insight harus menyimpan:

- Judul.
- Kategori.
- Cabang terkait.
- Periode analisis.
- Metrik utama.
- Nilai pembanding.
- Data pendukung.
- Rekomendasi.
- Tingkat prioritas.
- Waktu analisis.
- Status insight.

### Scheduler

Gunakan Laravel Scheduler dan Queue untuk menjalankan analisis secara berkala.

Contoh:

- Analisis harian.
- Ringkasan mingguan.
- Ringkasan bulanan.

### Prinsip Penting

Gunakan SQL dan kode Laravel untuk mendeteksi perubahan statistik.

Gemini digunakan untuk menyusun penjelasan dan rekomendasi.

Jangan menggunakan Gemini untuk menghitung seluruh data mentah apabila hasil yang sama dapat dihitung secara deterministik.

Jangan menyimpulkan hubungan sebab-akibat tanpa bukti.

Cegah insight duplikat.

AI tidak boleh melakukan perubahan harga, pembelian bahan, atau transaksi secara otomatis.

---

# 20. NOTIFICATIONS

Bangun sistem notifikasi dalam aplikasi.

Jenis notifikasi:

- Stok menipis.
- Stok habis.
- Refund approval.
- Price change request.
- Purchasing reminder.
- Penurunan penjualan.
- Insight bisnis baru.
- Payroll approval.

Fitur:

- Notification bell.
- Unread count.
- Notification list.
- Mark as read.
- Link ke detail terkait.

Pastikan setiap notifikasi hanya diterima pengguna yang memiliki akses.

---

# 21. AUDIT LOGS

Catat aktivitas sensitif.

Contoh:

- Perubahan harga.
- Perubahan stok.
- Persetujuan refund.
- Perubahan role.
- Perubahan akun.
- Persetujuan payroll.
- Perubahan resep.
- Perubahan konfigurasi cabang.

Audit log minimal menyimpan:

- Pengguna.
- Aktivitas.
- Entity terkait.
- Perubahan data yang relevan.
- Timestamp.

Jangan memungkinkan pengguna biasa mengubah atau menghapus audit log.

---

# 22. DATABASE DESIGN

Gunakan PostgreSQL dengan database relasional yang dinormalisasi secara wajar.

Minimal buat tabel yang mendukung:

### Authentication
- users
- user_branch_assignments

### Branch
- branches

### Products
- categories
- products
- branch_products
- product_price_requests

### Recipe
- ingredients
- product_recipe_versions
- recipe_items

### Sales
- cashier_shifts
- orders
- order_items
- payments
- refunds
- refund_items

### Inventory
- branch_inventory
- inventory_movements
- stock_adjustments
- stock_transfers
- stock_transfer_items

### Purchasing
- suppliers
- purchase_orders
- purchase_order_items
- stock_receipts
- stock_receipt_items

### Employees
- employees
- work_schedules

### Payroll
- employee_compensations
- payroll_periods
- payroll_items
- payroll_adjustments

### Finance
- expense_categories
- expenses

### AI
- ai_conversations
- ai_messages
- ai_tool_executions
- business_insights

### System
- notifications
- audit_logs
- application_settings

Boleh menambahkan tabel pendukung apabila memang diperlukan.

### Ketentuan Database

- Gunakan foreign keys.
- Gunakan unique constraints.
- Buat indexes untuk query penting.
- Gunakan timestamps.
- Gunakan database transactions.
- Hindari penyimpanan nilai uang menggunakan floating-point.
- Gunakan tipe data yang mendukung kuantitas pecahan untuk bahan baku.
- Simpan snapshot harga transaksi.
- Hindari hard delete untuk data yang memiliki riwayat penting.
- Gunakan migrations dan seeders.
- Gunakan nama tabel dan kolom yang konsisten dalam snake_case.

Buat database yang mampu mendukung penambahan cabang, transaksi dalam jumlah besar, dan laporan historis.

---

# 23. UI/UX DESIGN REQUIREMENTS

Veltrix harus memiliki desain premium, modern, profesional, dan tidak terlihat seperti template admin generik.

Gunakan pendekatan modern SaaS dashboard.

### Design Direction

- Clean.
- Elegant.
- Professional.
- Modern.
- Minimal.
- Data-focused.
- Consistent.
- Responsive.

### Warna

Gunakan identitas visual yang cocok dengan brand Veltrix.

Rekomendasi:

- Primary: Deep emerald atau forest green.
- Background: Soft neutral white.
- Surface: White.
- Secondary: Light gray.
- Text: Dark charcoal.
- Success: Green.
- Warning: Amber.
- Danger: Red.

Gunakan warna secara proporsional.

Hindari terlalu banyak warna mencolok dan gradient yang berlebihan.

### Typography

Gunakan font modern seperti Inter atau Geist.

Pastikan hierarki heading, label, angka statistik, dan teks pendukung jelas.

### Layout

Gunakan:

- Sidebar navigation.
- Top header.
- Page title.
- Breadcrumb jika diperlukan.
- Filter toolbar.
- KPI cards.
- Data tables.
- Charts.
- Dialogs.
- Drawers.
- Toast notifications.

### UI Component Rules

- Gunakan shadcn/ui.
- Gunakan Lucide React.
- Konsisten dalam radius, spacing, dan padding.
- Gunakan hover dan active states.
- Buat loading skeleton.
- Buat empty states.
- Buat error states.
- Buat success feedback.
- Gunakan confirmation dialog untuk tindakan destruktif.
- Pastikan tabel tidak overflow secara tidak terkontrol.
- Gunakan pagination untuk data besar.

### Owner Dashboard

Fokus pada analytics, visualisasi data, dan monitoring.

### Branch Manager Dashboard

Fokus pada aktivitas operasional dan stok.

### Cashier POS

Fokus pada kecepatan transaksi dengan produk dan keranjang yang mudah digunakan.

### AI Assistant

Gunakan tampilan chat modern yang nyaman dibaca.

### Responsive Design

Optimalkan untuk desktop dan tablet.

Mobile browser tetap harus memiliki tampilan yang layak, tetapi POS desktop/tablet menjadi prioritas utama.

**Jangan membuat semua halaman memiliki layout dan isi yang sama.**

Setiap halaman harus dirancang berdasarkan kebutuhan dan aktivitas pengguna.

---

# 24. SIDEBAR NAVIGATION

## OWNER

- Dashboard
- Cabang
- Penjualan
- Produk
- Inventori
- Pembelian
- Karyawan
- Payroll
- Pengeluaran
- Laporan
- AI Assistant
- AI Insights
- Manajemen Pengguna
- Audit Log
- Pengaturan

Kelompokkan menu secara logis jika jumlah navigasi terlalu banyak.

## BRANCH MANAGER

- Dashboard
- Penjualan
- Produk
- Inventori
- Pembelian
- Karyawan
- Jadwal Kerja
- Pengeluaran
- Laporan
- Pengaturan

## CASHIER

- Point of Sale
- Riwayat Transaksi
- Shift Kasir
- Profil

Setiap role harus memiliki layout dan navigasi sesuai tanggung jawabnya.

---

# 25. DATA SEEDING

Buat seed data yang realistis untuk pengembangan dan demonstrasi.

Minimal:

- 1 akun Owner.
- 3 cabang.
- 1 Manager per cabang.
- 2 Cashier per cabang.
- Beberapa kategori produk.
- Minimal 20 produk coffee shop.
- Daftar bahan baku.
- Resep produk.
- Stok awal setiap cabang.
- Supplier.
- Transaksi historis beberapa bulan.
- Pengeluaran operasional.
- Data karyawan.
- Data payroll.

Gunakan data simulasi yang konsisten secara bisnis.

Contoh:

Produk yang dijual harus memiliki transaksi yang valid.

Jumlah stok harus dapat direkonsiliasi dengan penerimaan, penggunaan, dan penyesuaian.

Dashboard harus membaca seed data dari database melalui backend.

Jangan melakukan hardcode angka statistik pada komponen React.

Pisahkan seed data demonstrasi dari data produksi.

---

# 26. SECURITY & VALIDATION

Implementasikan:

- Authentication.
- Authorization.
- CSRF protection.
- Input validation.
- Secure sessions.
- Password hashing.
- Rate limiting.
- Branch access restriction.
- Secure file uploads.
- Audit logging.
- Proper error handling.

Periksa akses pada setiap operasi sensitif.

Pastikan tidak ada kebocoran data antar cabang.

Hindari menyimpan API key atau kredensial rahasia di repository.

Gunakan `.env.example` untuk mendokumentasikan variabel konfigurasi tanpa menyimpan secret.

---

# 27. PERFORMANCE & RELIABILITY

Optimalkan aplikasi agar tetap responsif.

Persyaratan:

- Pagination pada tabel.
- Database indexing.
- Optimasi query Eloquent.
- Hindari N+1 queries.
- Gunakan caching bila diperlukan.
- Gunakan background jobs untuk proses berat.
- Dashboard tidak boleh memblokir transaksi.
- AI timeout harus ditangani.
- Queue retry harus aman.
- Proses checkout harus idempotent.
- Gunakan database locking atau strategi concurrency lain sesuai kebutuhan.

Aplikasi tetap harus berfungsi ketika Gemini API tidak tersedia.

AI bukan dependency kritis untuk menjalankan POS.

---

# 28. TESTING

Buat pengujian untuk business logic penting.

### Authentication Tests
- Login.
- Logout.
- Protected routes.
- Role permissions.
- Cross-branch access restrictions.

### POS Tests
- Cart totals.
- Transaction creation.
- Payment validation.
- Duplicate requests.
- Refund.
- Shift management.

### Inventory Tests
- Recipe deductions.
- Stock receiving.
- Stock adjustment.
- Stock transfer.
- Insufficient inventory.
- Concurrent transactions.

### Finance Tests
- Revenue calculation.
- Gross profit.
- Operational expense.
- Payroll calculation.
- Period filtering.

### AI Tests
- Correct tool selection.
- Correct numerical responses.
- Invalid questions.
- Empty data.
- Unauthorized access.
- Provider failures.

Gunakan automated tests untuk fitur yang memiliki risiko tinggi.

---

# 29. IMPLEMENTATION STRATEGY

Bangun aplikasi secara bertahap berdasarkan dependensi teknis.

Namun, seluruh fitur yang disebutkan tetap merupakan target implementasi project.

### Tahap 1 — Foundation

- Setup Laravel + React Inertia.
- Database.
- Authentication.
- Role management.
- Branch management.
- Design system.

### Tahap 2 — POS & Products

- Product catalog.
- Product categories.
- Branch pricing.
- Cashier POS.
- Transactions.
- Payments.
- Receipts.
- Cashier shifts.

### Tahap 3 — Inventory

- Ingredients.
- Recipes.
- Inventory.
- Stock movements.
- Purchasing.
- Restocking.
- Transfers.

### Tahap 4 — Business Operations

- Employees.
- Work schedules.
- Expenses.
- Payroll.

### Tahap 5 — Analytics

- Owner dashboard.
- Manager dashboard.
- Reports.
- Branch comparison.
- Profitability.

### Tahap 6 — AI Business Assistant

- Laravel AI SDK.
- Gemini integration.
- AI tools.
- Chat interface.
- Conversation history.

### Tahap 7 — Automated AI Insights

- Detection engine.
- Scheduled analytics.
- AI recommendations.
- Insight history.
- Notifications.

### Tahap 8 — Quality & Deployment

- Testing.
- Bug fixing.
- Performance optimization.
- Security review.
- Production configuration.
- Deployment documentation.

Jangan membangun UI seluruh halaman terlebih dahulu kemudian membiarkan backend belum berfungsi.

Selesaikan fitur secara vertikal: database, business logic, authorization, halaman React, validasi, dan pengujian.

---

# 30. AI AGENT WORKING RULES

Selama pengembangan, ikuti aturan berikut:

1. Pahami keseluruhan kebutuhan Veltrix sebelum menentukan struktur aplikasi.
2. Periksa kondisi repository terlebih dahulu.
3. Jika repository kosong, lakukan inisialisasi menggunakan stack yang ditentukan.
4. Jika sudah terdapat implementasi, pertahankan kode yang benar dan lakukan pengembangan tanpa merusak fitur yang ada.
5. Buat struktur folder yang rapi.
6. Gunakan reusable components.
7. Jangan mengimplementasikan business logic penting secara langsung di frontend.
8. Jangan menggunakan data hardcode untuk fitur utama.
9. Jangan membuat tombol atau menu yang hanya menjadi dekorasi.
10. Setiap fitur yang dikembangkan harus memiliki alur yang benar.
11. Gunakan PostgreSQL sebagai sumber data utama.
12. Pastikan relasi antar modul berjalan.
13. Terapkan validasi dan authorization.
14. Jangan menambahkan framework yang tidak dibutuhkan.
15. Jangan membuat kompleksitas teknis tanpa alasan.
16. Periksa kompatibilitas dependency sebelum instalasi.
17. Jangan menghapus data atau file penting tanpa alasan yang jelas.
18. Buat migration ketika mengubah struktur database.
19. Sertakan seeders untuk demonstrasi.
20. Jalankan pengujian dan perbaiki error yang ditemukan.
21. Dokumentasikan konfigurasi environment yang diperlukan.
22. Jika API key Gemini belum tersedia, buat integrasi yang siap dikonfigurasi, bukan AI palsu.
23. Jangan menyatakan fitur selesai sebelum memverifikasi alurnya.
24. Jangan mengklaim seluruh fitur sudah diuji apabila pengujian belum benar-benar dijalankan.
25. Pertahankan konsistensi desain di seluruh aplikasi.

---

# 31. OUTPUT YANG DIHARAPKAN

Hasil akhir harus berupa aplikasi Veltrix yang memiliki:

- Struktur project yang profesional.
- Frontend React + Inertia.
- Backend Laravel.
- Database PostgreSQL.
- Authentication.
- Tiga role dengan hak akses yang benar.
- Dashboard Owner.
- Dashboard Branch Manager.
- Cashier POS.
- Product Management.
- Recipe Management.
- Inventory Management.
- Purchasing.
- Employee Management.
- Payroll.
- Operational Expenses.
- Reports & Analytics.
- AI Business Assistant.
- Automated AI Insights.
- Notifications.
- Audit Logs.
- Seed data realistis.
- Testing.
- Dokumentasi setup dan penggunaan.

### Dokumentasi

Sertakan README yang menjelaskan:

- Deskripsi project.
- Tech stack.
- Persyaratan sistem.
- Cara instalasi.
- Environment variables.
- Konfigurasi PostgreSQL.
- Cara menjalankan migration.
- Cara menjalankan seeder.
- Cara menjalankan frontend dan backend.
- Cara konfigurasi Gemini API.
- Cara menjalankan queue worker.
- Cara mengaktifkan scheduler.
- Akun demo untuk setiap role.
- Panduan deployment.

Jangan memasukkan password produksi atau API key sungguhan ke README.

---

# 32. FINAL INSTRUCTIONS

Bangun **Veltrix** sebagai aplikasi web full-stack yang lengkap, terintegrasi, modern, dan memiliki kualitas arsitektur yang baik.

Prioritaskan:

1. Ketepatan business logic.
2. Konsistensi database.
3. Keamanan role dan cabang.
4. Kualitas antarmuka.
5. Integrasi antar modul.
6. Keakuratan laporan bisnis.
7. Integrasi AI berbasis data.
8. Reliability dan maintainability.

Veltrix bukan hanya dashboard yang menampilkan grafik.

Veltrix harus menjadi sistem operasional multi-cabang yang menghubungkan aktivitas penjualan, inventori, pembelian, karyawan, pengeluaran, payroll, serta analitik bisnis dalam satu platform.

Pastikan AI Business Assistant benar-benar dapat membaca hasil analisis database melalui tools yang aman.

Pastikan Automated AI Insights dapat mendeteksi kondisi penting berdasarkan data dan memberikan rekomendasi yang relevan.

**Mulai dengan memeriksa repository, merancang struktur aplikasi, database schema, dan rencana implementasi. Setelah itu, kerjakan modul secara bertahap hingga seluruh cakupan project selesai.**

Jangan berhenti pada pembuatan mockup atau frontend saja.

Setiap fitur harus terhubung dengan backend dan database serta diuji sesuai kebutuhan.

**Nama aplikasi yang wajib digunakan di seluruh project adalah VELTRIX.**