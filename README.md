# VELTRIX

VELTRIX adalah aplikasi pengelolaan kedai kopi multi-cabang yang menghubungkan penjualan, stok bahan, pembelian, karyawan, keuangan, dan analisis bisnis dalam satu tempat. Aplikasi menyediakan ruang kerja untuk owner, manager cabang, dan kasir, dengan akses data sesuai peran dan cabang masing-masing.

Antarmuka menggunakan Bahasa Indonesia, nominal Rupiah (IDR), dan zona waktu bisnis Asia/Jakarta. Frontend React dan backend Laravel terhubung melalui Inertia dalam satu proyek.

## Fitur

- **Dashboard operasional:** ringkasan penjualan, tren harian dan per jam, menu terlaris, transaksi terbaru, stok rendah, dan pekerjaan yang perlu ditindaklanjuti.
- **Point of Sale:** pencarian dan filter menu, keranjang, jumlah pesanan, pencatatan pembayaran, uang diterima dan kembalian, sesi kasir, serta struk yang bisa dicetak.
- **Transaksi dan refund:** riwayat transaksi, detail pesanan, dan pengajuan serta persetujuan pengembalian dana penuh.
- **Katalog menu:** produk, kategori, foto sesuai menu, harga dan ketersediaan per cabang, serta persetujuan perubahan harga.
- **Bahan dan persediaan:** bahan baku, resep dengan versi, pemotongan bahan saat penjualan, stok cabang, penyesuaian, opname, riwayat pergerakan, dan peringatan stok rendah.
- **Pembelian dan transfer:** supplier, pembelian bahan, penerimaan sebagian, dan transfer stok dengan tahap persetujuan, pengiriman, serta penerimaan.
- **Karyawan dan payroll:** data karyawan yang terpisah dari akun login, jadwal kerja, komponen gaji bulanan, persetujuan dan pencatatan pembayaran payroll, serta slip gaji.
- **Pengeluaran:** pencatatan pengeluaran, kategori, bukti pengeluaran privat, dan persetujuan atau penolakan.
- **Laporan bisnis:** ringkasan penjualan, biaya bahan, estimasi laba, pengeluaran, payroll, perbandingan cabang, komposisi pembayaran, serta ekspor CSV dan PDF sesuai hak akses.
- **Laporan penjualan menu:** katalog lengkap termasuk menu tidak aktif atau belum terjual, filter tanggal/cabang/kategori/nama/SKU, pengurutan, pagination, rincian per cabang, dan ekspor seluruh hasil filter.
- **AI Business Intelligence:** chat Gemini dengan riwayat percakapan, periode analisis, bukti dari data bisnis, dan batas pertanyaan harian. Owner dapat menganalisis lintas cabang; manager dibatasi ke cabangnya dan tidak mendapat data payroll atau laba operasional.
- **Insight otomatis:** analisis berbasis aturan untuk perubahan penjualan, menu, pengeluaran, perbedaan cabang, dan risiko stok. Owner dapat meninjau temuan dan meminta penjelasan AI. Analisis berbasis aturan tetap tersedia tanpa Gemini.
- **Administrasi dan keamanan:** pengaturan cabang, pengguna dan peran, aktivasi akun, profil, pemulihan password, notifikasi, pengaturan bisnis/struk, dan audit perubahan.
- **Progressive Web App:** instalasi melalui browser yang mendukung, ikon aplikasi, panduan instalasi iOS, halaman offline, dan pembaruan yang dikendalikan pengguna. Transaksi POS tetap memerlukan koneksi; halaman privat dan permintaan checkout tidak disimpan sebagai cache offline.

## Peran pengguna

| Peran   | Cakupan                                                                                                                                   |
| ------- | ----------------------------------------------------------------------------------------------------------------------------------------- |
| Owner   | Pengelolaan seluruh cabang, pengguna, payroll, laporan keuangan, chat AI, dan insight otomatis.                                           |
| Manager | Operasional, stok, laporan yang diizinkan, dan chat AI untuk cabang yang ditugaskan. Tidak dapat mengakses payroll atau laba operasional. |
| Kasir   | POS, sesi kasir, transaksi miliknya, struk, dan pengajuan refund sesuai izin.                                                             |

Tidak ada registrasi publik. Akun operasional dikelola oleh owner. Sesi login berlaku maksimal tujuh hari sejak autentikasi password, tanpa opsi remember-me yang memperpanjang batas tersebut.

## Teknologi

Laravel 13, PHP 8.4, React 19, TypeScript, Inertia 3, Tailwind CSS 4, komponen Radix/shadcn, Lucide, Recharts, PostgreSQL/Neon, Dompdf, serta Laravel AI SDK dengan Gemini.

PostgreSQL digunakan untuk data operasional. SQLite hanya digunakan pada pengujian terisolasi. Aplikasi tidak memerlukan pemisahan frontend dan backend, XAMPP, MySQL, atau Docker untuk development lokal.

## Instalasi lokal

Siapkan PHP 8.4+, Composer, Node.js 22.12+ pada seri 22.x, dan PostgreSQL. Aktifkan ekstensi PHP `curl`, `fileinfo`, `mbstring`, `openssl`, `pdo_pgsql`, `pgsql`, `zip`, `intl`, dan `dom`. Pengujian cepat juga membutuhkan `pdo_sqlite`.

```sh
composer install
npm ci
```

Salin `.env.example` menjadi `.env`, lalu isi kredensial database. Koneksi Neon memakai `DB_SSLMODE=require` dan `PGCHANNELBINDING=require`.

Untuk instalasi baru:

```sh
php artisan key:generate
php artisan migrate
```

Jangan membuat ulang `APP_KEY` saat memakai database lama yang berisi data terenkripsi.

Jalankan backend dan frontend di terminal terpisah:

```sh
php artisan serve
```

```sh
npm run dev
```

Buka [aplikasi lokal](http://127.0.0.1:8000). Jika ingin menggunakan aset hasil build tanpa server Vite, jalankan `npm run build`, kemudian cukup jalankan server Laravel.

Pada workspace Windows yang memiliki PHP portabel lokal:

```powershell
& .\.tools\php\php.exe artisan serve
& .\.tools\php\php.exe .tools\composer.phar install
```

Folder `.tools` tidak ikut Git. SSR Inertia dan pengiriman otomatis browser log Boost dinonaktifkan secara default.

## Instalasi awal dan akun demo

Untuk instalasi operasional tanpa transaksi contoh:

```sh
php artisan db:seed
php artisan veltrix:owner owner@example.com --name=Owner
```

Perintah owner meminta password tersembunyi dan menolak pembuatan owner kedua.

Untuk database development yang masih kosong, gunakan demo seeder sebagai pengganti pembuatan owner awal:

```sh
php artisan db:seed --class=DemoSeeder
```

Demo seeder hanya berjalan pada environment `local` atau `testing` dan berhenti jika pengguna atau produk sudah ada. Data contoh mencakup tiga cabang, sepuluh akun, dua puluh menu, resep, stok, supplier, karyawan, jadwal, draft payroll, dan sekitar tiga bulan transaksi.

| Peran   | Cabang         | Email demo                    |
| ------- | -------------- | ----------------------------- |
| Owner   | Seluruh cabang | `owner@veltrix.com`           |
| Manager | Depok          | `depok.manager@veltrix.com`   |
| Kasir 1 | Depok          | `depok.kasir1@veltrix.com`    |
| Kasir 2 | Depok          | `depok.kasir2@veltrix.com`    |
| Manager | Jakarta        | `jakarta.manager@veltrix.com` |
| Kasir 1 | Jakarta        | `jakarta.kasir1@veltrix.com`  |
| Kasir 2 | Jakarta        | `jakarta.kasir2@veltrix.com`  |
| Manager | Bogor          | `bogor.manager@veltrix.com`   |
| Kasir 1 | Bogor          | `bogor.kasir1@veltrix.com`    |
| Kasir 2 | Bogor          | `bogor.kasir2@veltrix.com`    |

Password bawaan seluruh akun demo: `demo1234`. Akun tersebut khusus demonstrasi, bukan konfigurasi akun produksi.

Foto menu adalah gambar hasil generasi yang disimpan di `public/images/menu`. Database menyimpan path gambar; server aplikasi menyajikan file fotonya. Ganti dengan foto produk kedai untuk katalog sebenarnya.

## Konfigurasi AI

Isi variabel server berikut di `.env` lokal atau Environment Variables pada hosting:

```env
AI_ENABLED=true
GEMINI_API_KEY=isi_key_di_server
GEMINI_MODEL=gemini-3.5-flash-lite
AI_DAILY_LIMIT=30
```

Model default mengikuti `config/intelligence.php`. Pastikan model tersebut tersedia untuk key dan kuota provider yang digunakan. Key tidak boleh masuk ke Git, kode frontend, atau variabel `VITE_*`.

```sh
php artisan veltrix:ai-check --models
php artisan veltrix:ai-check
```

Perintah pertama menampilkan model teks yang tersedia. Perintah kedua memeriksa koneksi tanpa mengirim data bisnis. Untuk secara eksplisit menguji pemanggilan analytics tool dengan agregat bisnis:

```sh
php artisan veltrix:ai-check --business
```

Chat memakai tool agregasi read-only; AI tidak diberi akses SQL bebas atau perubahan transaksi. Pertanyaan produk dapat mencari seluruh katalog dan nama historis, membedakan produk tidak dikenal dari produk tanpa penjualan, serta membandingkan periode dengan perhitungan backend. Setiap pesan menyimpan cakupan tanggal/cabangnya, sehingga jawaban lama mempertahankan konteks aslinya.

Batas default adalah 30 pertanyaan per pengguna per hari kalender Jakarta, termasuk percobaan yang gagal. Pemakaian token dicatat pada jawaban; kuota dan biaya Gemini mengikuti akun provider.

**Chat AI dan analisis yang diminta manual memakai queue `database` secara eksplisit**, termasuk jika `QUEUE_CONNECTION=sync`. Jalankan worker persisten:

```sh
php artisan queue:work database --tries=1 --timeout=150
```

Tanpa worker, permintaan AI akan tetap pending hingga dianggap kedaluwarsa. UI memantau hanya saat jawaban sedang diproses. Pengujian otomatis tidak memanggil provider AI secara langsung.

Untuk analisis berbasis aturan tanpa Gemini:

```sh
php artisan veltrix:insights
```

Analisis tren menggunakan periode lengkap tujuh hari dengan batas minimum transaksi. Estimasi cakupan stok berasal dari konsumsi resep yang tercatat. Temuan menyimpan bukti, status peninjauan, dan notifikasi yang dideduplikasi.

## Integritas data dan batas fitur

- Harga checkout ditentukan backend. Checkout dan penerimaan memiliki idempotency key untuk mencegah pencatatan berulang.
- Penjualan, pembayaran, pemotongan bahan, ledger, dan audit dicatat dalam transaksi database. Row lock melindungi stok dari pemakaian bersamaan.
- Biaya stok memakai rata-rata tertimbang per bahan/cabang. Biaya bahan pesanan dibekukan saat checkout; perubahan harga berikutnya tidak menulis ulang riwayat.
- Pembelian belum menambah stok sebelum diterima. Transfer mempertahankan snapshot biaya sumber.
- Refund berlaku penuh untuk satu pesanan dan tidak mengembalikan bahan yang telah dikonsumsi ke stok. Pembayaran non-tunai dan refund dicatat manual; belum ada payment gateway.
- Payroll disusun dari komponen bulanan dan diakui pada tanggal pertama periodenya setelah disetujui/dibayar. Belum ada perhitungan otomatis absensi atau pajak wajib.
- Laba merupakan estimasi manajemen, bukan laporan akuntansi resmi. Valuasi persediaan menggambarkan saldo stok saat ini, bukan saldo historis pada akhir periode laporan.

Rincian implementasi dan batas alur kerja tersedia di [docs/IMPLEMENTATION.md](docs/IMPLEMENTATION.md). Kebutuhan produk dan arahan implementasi tersedia di [prd.md](prd.md) dan [build.md](build.md).

## Queue, scheduler, dan email

Jalankan worker database di atas untuk AI. Jalankan scheduler setiap menit melalui cron atau layanan setara:

```sh
php artisan schedule:run
```

Scheduler menjalankan notifikasi stok setiap jam dan analisis bisnis harian pada pukul 06.00 Asia/Jakarta. Pemeriksaan stok manual tersedia melalui:

```sh
php artisan veltrix:notifications
```

`MAIL_MAILER=log` mencatat email untuk development. Atur SMTP atau transport email yang sesuai agar pemulihan password dapat mengirim email ke pengguna.

## Deployment Vercel

Deployment disiapkan dari root repo tanpa Docker, menggunakan runtime komunitas `vercel-php@0.8.0` untuk PHP 8.4. Runtime ini sudah diarsipkan dan tidak didukung resmi Vercel.

Saat import repository, gunakan:

| Pengaturan                   | Nilai                                   |
| ---------------------------- | --------------------------------------- |
| Application/Framework Preset | `Other`                                 |
| Root Directory               | `./`                                    |
| Node.js                      | `22.x`                                  |
| Install Command              | `npm ci --include=dev --ignore-scripts` |
| Build Command                | `npm run build:vercel`                  |
| Output Directory             | `.vercel-static`                        |

Nilai build sudah diatur dalam `vercel.json`. Tambahkan Environment Variables berikut:

| Variabel      | Nilai                                                                       |
| ------------- | --------------------------------------------------------------------------- |
| `APP_KEY`     | Key aplikasi yang sama jika memakai database yang sudah ada; simpan privat. |
| `APP_URL`     | URL HTTPS deployment atau domain aplikasi.                                  |
| `DB_HOST`     | Hostname pooled Neon.                                                       |
| `DB_PORT`     | `5432`                                                                      |
| `DB_DATABASE` | Nama database Neon.                                                         |
| `DB_USERNAME` | Nama role Neon.                                                             |
| `DB_PASSWORD` | Password Neon; simpan privat.                                               |

Tambahkan juga variabel AI di bagian konfigurasi AI jika chat ingin diaktifkan. Jika mengimpor `.env` lokal, sesuaikan `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, `SESSION_SECURE_COOKIE=true`, dan `LOG_CHANNEL=stderr`. Jangan menimpa konfigurasi hosting dengan URL localhost atau pengaturan development.

Konfigurasi Vercel menyiapkan session/cache database, TLS Neon, cookie HTTPS, dan log stderr. Build hanya menyalin aset publik, foto, dan file PWA ke `.vercel-static`; kode PHP, `.env`, private storage, serta file Vite development tidak menjadi aset publik. Laravel menggunakan direktori sementara untuk cache framework dan hasil kompilasi view. Development lokal tetap memakai `public/index.php`.

Jalankan migrasi dari lingkungan tepercaya yang dikonfigurasi ke database tujuan bila diperlukan:

```sh
php artisan migrate --force
```

Build tidak menjalankan migrasi, reset database, demo seeder, atau pembuatan `APP_KEY` baru. Gunakan endpoint `/up` untuk pemeriksaan dasar deployment.

Batas deployment yang perlu disiapkan:

- Vercel tidak otomatis menjalankan Artisan queue worker atau scheduler. **AI memerlukan worker database persisten terpisah**, dan scheduler juga perlu dijalankan dari lingkungan eksternal. `QUEUE_CONNECTION=sync` pada konfigurasi umum tidak menggantikan worker AI.
- Upload bukti pengeluaran saat ini memakai private local storage. Filesystem Vercel bersifat sementara; penyimpanan permanen diperlukan sebelum mengandalkan fitur upload bukti di produksi. Foto menu bawaan ikut deployment dan tidak bergantung pada upload runtime.
- Konfigurasikan pengiriman email serta backup database. Konfigurasi repo dan tes lokal tidak membuktikan bahwa deployment online, backup/restore, atau uji beban produksi sudah berhasil.

Untuk hosting PHP biasa, arahkan document root ke `public/`, pasang dependency produksi, build frontend, atur environment, jalankan migrasi, lalu `php artisan optimize`. Kelola worker menggunakan process supervisor dan scheduler menggunakan cron.

## Pengujian dan format

```sh
php artisan test
php vendor/bin/phpunit -c phpunit.pgsql.xml
npm run test:pwa
npm run build
npm run format:check
```

Test runner menolak database operasional. Suite cepat memakai SQLite in-memory. Suite PostgreSQL menggunakan database lokal `veltrix_test` pada `127.0.0.1:55432`, termasuk dua proses terpisah untuk menguji checkout stok terakhir dan replay permintaan. Pengujian tidak mereset database Neon aplikasi.

Suite PostgreSQL memerlukan cluster test lokal yang aktif. Pada workspace Windows ini, cluster berada di `.tools/pgsql-test`; kredensial dan konfigurasi tersebut terpisah dari Neon.

Prettier digunakan untuk TypeScript, CSS, dan PHP dengan lebar baris 90. Jalankan `npm run format` untuk memformat source. Screenshot QA lokal disimpan pada folder `.tasteskill` yang diabaikan Git.

## Kebersihan repository

Seluruh folder `.impeccable/` diabaikan Git dan tetap menjadi data tooling lokal. Folder dependency, runtime lokal, hasil build, screenshot QA, `.env`, key, log, dan backup juga tidak disertakan ke repository.

Jangan commit kredensial Neon/Gemini, dump database, atau bukti pengeluaran privat. Simpan backup terenkripsi di luar web root dan repository, lalu verifikasi pemulihannya pada database terpisah.
