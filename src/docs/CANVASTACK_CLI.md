# CanvaStack CLI (Artisan Commands)

Dokumentasi lengkap untuk perintah-perintah CLI yang disediakan paket CanvaStack melalui Artisan.

## Lokasi Kode
- Service Provider (registrasi commands):
  - `packages/canvastack/canvastack/src/CanvastackServiceProvider.php`
- Namespace commands:
  - `packages/canvastack/canvastack/src/Console/*`
- Script pendukung (non-Artisan) yang dibungkus oleh command:
  - `scripts/db-check.php`
  - `scripts/hybrid-diff-summary.php`
  - `scripts/hybrid-diff-summary-filtered.php`

## Daftar Perintah

### 1) canvastack:test
- Kelas: `Canvastack\Canvastack\Console\CanvastackTestCommand`
- Fungsi: Menjalankan test paket Table Builder (`packages/.../Table/tests`) via PHPUnit menggunakan konfigurasi paket.
- Signature:
  ```
  canvastack:test {file? : Target test filename or class (without path)} {--filter=} {--suite=}
  ```
- Contoh:
  ```bash
  php artisan canvastack:test                         # semua test paket
  php artisan canvastack:test --suite=Canvastack-Table-Feature
  php artisan canvastack:test --filter=HybridCompare
  php artisan canvastack:test DatatablesPipelineGateTest
  php artisan canvastack:test packages/canvastack/canvastack/src/Library/Components/Table/tests/Feature/DatatablesPipelineGateTest.php
  ```
- Catatan: Command ini memaksa env testing (SQLite in-memory) agar deterministik untuk suite paket.

### 2) canvastack:snapshot:validate
- Kelas: `Canvastack\Canvastack\Console\CanvastackSnapshotValidateCommand`
- Fungsi: Validasi file snapshot inspector (JSON) beserta metadata toleransi.
- Signature:
  ```
  canvastack:snapshot:validate {--path=} {--strict}
  ```
- Contoh:
  ```bash
  php artisan canvastack:snapshot:validate
  php artisan canvastack:snapshot:validate --path=storage/app/datatable-inspector --strict
  ```

### 3) canvastack:snapshot:make
- Kelas: `Canvastack\Canvastack\Console\CanvastackSnapshotMakeCommand`
- Fungsi: Membuat template snapshot JSON inspector untuk sebuah tabel.
- Signature:
  ```
  canvastack:snapshot:make {table} {--lists=*} {--blacklist=*} {--orderby=} {--tolerance=} {--path=}
  ```
- Contoh:
  ```bash
  php artisan canvastack:snapshot:make users --lists=id name email --blacklist=password action --orderby=name --tolerance=0 --path=storage/app/datatable-inspector
  ```

### 4) canvastack:pipeline:dry-run
- Kelas: `Canvastack\Canvastack\Console\CanvastackPipelineDryRunCommand`
- Fungsi: Menjalankan pipeline Datatables dengan input mirip legacy untuk debugging.
- Signature:
  ```
  canvastack:pipeline:dry-run {--method=} {--datatables=} {--filters=} {--filter-page=} {--table=} {--pretty}
  ```
- Contoh:
  ```bash
  php artisan canvastack:pipeline:dry-run --table=users --pretty
  php artisan canvastack:pipeline:dry-run --method='{"difta":{"name":"users"}}' --datatables=path\\to\\datatables.json --filters='{}' --pretty
  ```

### 5) canvastack:db:check
- Kelas: `Canvastack\Canvastack\Console\CanvastackDbCheckCommand`
- Sumber fungsi: membungkus `scripts/db-check.php`
- Fungsi: Memvalidasi konektivitas DB (main dan cross-server). Exit non‑zero jika ada yang gagal.
- Signature:
  ```
  canvastack:db:check {--json : Output JSON only}
  ```
- Contoh:
  ```bash
  php artisan canvastack:db:check
  php artisan canvastack:db:check --json
  ```
- Output: JSON daftar koneksi dan status; STDERR berisi pesan ringkas jika gagal.

### 6) canvastack:inspector:summary
- Kelas: `Canvastack\Canvastack\Console\CanvastackInspectorSummaryCommand`
- Sumber fungsi: membungkus `scripts/hybrid-diff-summary.php` dan `scripts/hybrid-diff-summary-filtered.php`
- Fungsi: Menghasilkan ringkasan Markdown dari file inspector; bisa difilter berdasarkan nama route.
- Signature:
  ```
  canvastack:inspector:summary {route?} {--output=}
  ```
- Contoh:
  ```bash
  # Ringkasan global
  php artisan canvastack:inspector:summary --output=_inspector_summary.md

  # Ringkasan khusus route incentive
  php artisan canvastack:inspector:summary "modules.incentive.incentive.index" --output=d:\\worksites\\mantra.smartfren.dev\\_inspector_incentive_summary.md
  ```

### 7) canvastack:pipeline:bench
- Kelas: `Canvastack\Canvastack\Console\CanvastackPipelineBenchCommand`
- Fungsi: Benchmark pipeline DataTables (server-side) dengan input mirip legacy; menampilkan metrik performa.
- Signature:
  ```
  canvastack:pipeline:bench {table} {--connection=} {--length=30} {--start=0} {--search=} {--filters=*} {--order=*} {--repeat=3} {--warmup=1} {--probe} {--json}
  ```
- Catatan:
  - `--warmup` mengabaikan N run pertama saat menghitung statistik.
  - Statistik mencakup: avg_ms, min_ms, max_ms, median_ms, p95_ms.
  - `--probe` kini menerapkan filters dan mencoba kolom order aman: prioritas kunci filter → `id` → kolom pertama → tanpa order.
- Contoh:
  ```bash
  # ETL incentive (MySQL cross-conn) dengan warm-up dan probe
  php artisan canvastack:pipeline:bench report_data_summary_incentive_asm \
    --connection=mysql_mantra_etl --length=30 --order=period:desc \
    --filters=period_string="01 March, 2023" --repeat=7 --warmup=2 --probe --json

  # Base module
  php artisan canvastack:pipeline:bench base_module --length=30 --order=module_name:asc --repeat=7 --warmup=2 --probe --json
  ```

### 8) canvastack:relation:bench
- Kelas: `Canvastack\Canvastack\Console\CanvastackRelationBenchCommand`
- Fungsi: Benchmark operasi relasi berat:
  - Modules::privileges() — join base_module + base_group_privilege + base_group dan transform privileges.
  - Users+Groups — join users + base_user_group + base_group.
- Signature:
  ```
  canvastack:relation:bench {target : modules_privileges|users_groups} {--group=} {--page-type=} {--repeat=5} {--warmup=1} {--json}
  ```
- Contoh:
  ```bash
  # Privileges untuk group 1 (admin page)
  php artisan canvastack:relation:bench modules_privileges --group=1 --page-type=admin --repeat=7 --warmup=2 --json

  # Users + groups join (sample 50 baris)
  php artisan canvastack:relation:bench users_groups --repeat=7 --warmup=2 --json
  ```

#### Output (JSON)
- Struktur ringkas:
  ```json
  {
    "table": "<string>",
    "connection": "<string|null>",
    "repeat": <int>,
    "length": <int>,
    "start": <int>,
    "search": "<string>",
    "filters": {"key":"value", ...},
    "order": ["col:dir", ...],
    "stats": {
      "avg_ms": <number>,
      "min_ms": <number>,
      "max_ms": <number>,
      "median_ms": <number>,
      "p95_ms": <number>,
      "warmup_ignored": <int>
    },
    "runs": [ {"ms": <number>, "rows": <int>, "recordsTotal": <int>, "recordsFiltered": <int>}, ... ],
    "probe": { "count": <int>, "first": { /* row */ } }
  }
  ```
- Catatan: `probe.first` diambil dengan kolom order aman (kunci filter → id → kolom pertama → tanpa order) dan menerapkan `filters`.

---

### 8) canvastack:relation:bench
- Kelas: `Canvastack\\Canvastack\\Console\\CanvastackRelationBenchCommand`
- Fungsi: Benchmark operasi relasi berat:
  - Modules::privileges() — join base_module + base_group_privilege + base_group dan transform privileges.
  - Users+Groups — join users + base_user_group + base_group.
- Signature:
  ```
  canvastack:relation:bench {target : modules_privileges|users_groups} {--group=} {--page-type=} {--mode=join} {--limit=50} {--repeat=5} {--warmup=1} {--json}
  ```
- Opsi khusus users_groups:
  - **--mode**: `join` (single SQL join), `eager` (with('group')), `lazy` (akses relasi per-user → N+1).
  - **--limit**: jumlah user yang diambil untuk sampling.
- Contoh:
  ```bash
  # Privileges untuk group 1 (admin page)
  php artisan canvastack:relation:bench modules_privileges --group=1 --page-type=admin --repeat=7 --warmup=2 --json

  # Privileges untuk group non-root (mis. 2) pada halaman frontend
  php artisan canvastack:relation:bench modules_privileges --group=2 --page-type=frontend --repeat=7 --warmup=2 --json

  # Users + groups baseline join
  php artisan canvastack:relation:bench users_groups --mode=join --limit=100 --repeat=7 --warmup=2 --json

  # Users + groups eager load (hindari N+1)
  php artisan canvastack:relation:bench users_groups --mode=eager --limit=100 --repeat=7 --warmup=2 --json

  # Users + groups lazy (sengaja N+1 untuk perbandingan)
  php artisan canvastack:relation:bench users_groups --mode=lazy --limit=100 --repeat=7 --warmup=2 --json
  ```

#### Output (JSON)
- Struktur ringkas:
  ```json
  {
    "target": "modules_privileges|users_groups",
    "repeat": <int>,
    "warmup_ignored": <int>,
    "stats": { "avg_ms": <number>, "min_ms": <number>, "max_ms": <number>, "median_ms": <number>, "p95_ms": <number> },
    "runs": [ {"ms": <number>}, ... ],
    "extra": {
      // modules_privileges
      "routes": ["<route>", ...],
      "roles_count": <int>,
      "privileges_count": <int>,
      "menu_count": <int>
      // users_groups
      ,"rows": <int>
    }
  }
  ```

#### Tips Benchmark
- Gunakan `--repeat>=7` dan `--warmup=2..3` untuk hasil stabil.
- Jalankan dua kali berturut untuk mengurangi efek cold cache storage/DB.
- Interpretasi cepat:
  - **avg_ms**: rata-rata keseluruhan setelah warm-up.
  - **median_ms**: baseline umum; tahan terhadap outlier.
  - **p95_ms**: tail-latency; penting untuk UX.

#### Troubleshooting
- Error probe "Unknown column '1' in order clause": sudah ditangani dengan fallback order aman pada versi ini.
- Pastikan nama `--filters` sesuai nama kolom aktual (case-sensitive sesuai DB). Gunakan `--probe` untuk validasi cepat.

## Variabel Lingkungan Terkait
- `CANVASTACK_PIPELINE_GATE=off|soft|strict` — Mode gate untuk perbandingan pipeline vs legacy.
- `CANVASTACK_DT_DIFF_TOLERANCE=int` — Toleransi severity diff (default 0/ketat).

## Alur Kerja yang Direkomendasikan
1. Cek koneksi DB:
   ```bash
   php artisan canvastack:db:check
   ```
2. Jalankan halaman target (mode local/hybrid) agar file inspector terisi.
3. Hasilkan ringkasan:
   ```bash
   php artisan canvastack:inspector:summary "<route-name>" --output=_inspector_<route>.md
   ```
4. Untuk verifikasi regresi otomatis, gunakan test paket:
   ```bash
   CANVASTACK_PIPELINE_GATE=soft php artisan canvastack:test --suite=Canvastack-Table-Feature
   ```

## Catatan Implementasi
- Kedua command baru (db:check, inspector:summary) memanggil script pendukung dan mewarisi exit code-nya agar mudah diintegrasikan ke CI.
- Semua path menggunakan `base_path()` sehingga berjalan konsisten dari root proyek.