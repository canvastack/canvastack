# Canvastack Table Builder — Test Suite Documentation

Dokumentasi untuk pengujian Table Builder (legacy Datatables + pipeline hybrid) pada paket `Canvastack`.

## Struktur Folder
- `Unit/`: Unit test untuk komponen kecil (adapter, diff, feature flag, dll.)
- `Feature/`: Feature test E2E (Laravel boot, sqlite in-memory, HybridCompare, snapshot replay)
- `Support/`: Helper/stubs yang dibutuhkan test (mis. fungsi global legacy)
- `phpunit.xml`: Konfigurasi untuk menjalankan test paket ini secara terpisah

## Prasyarat
- PHP 8.1+ dan Laravel 10 (proyek ini)
- Windows workspace path: `d:\worksites\mantra.smartfren.dev`
- Dependensi sudah terpasang (composer install) sehingga `vendor` tersedia

## Cara Menjalankan
- Dari root project (menjalankan semua test proyek):
  - `vendor\bin\phpunit`
- Hanya test paket Table Builder — cara 1 (langsung PHPUnit):
  - `php vendor\phpunit\phpunit\phpunit packages/canvastack/canvastack/src/Library/Components/Table/tests/phpunit.xml`
- Hanya test paket Table Builder — cara 2 (Artisan command):
  - `php artisan canvastack:test` → seluruh test paket
  - `php artisan canvastack:test --suite=Canvastack-Table-Feature` → hanya Feature tests
  - `php artisan canvastack:test --suite=Canvastack-Table-Unit` → hanya Unit tests
  - `php artisan canvastack:test DatatablesInspectorReplayTest` → filter nama file/kelas (tanpa path)
  - `php artisan canvastack:test --filter="pattern"` → teruskan filter ke PHPUnit

### Contoh
```bash
# Semua test paket
php artisan canvastack:test

# Hanya Feature
php artisan canvastack:test --suite=Canvastack-Table-Feature

# Menjalankan test yang mengandung nama "HybridCompare"
php artisan canvastack:test --filter=HybridCompare

# Menjalankan satu kelas test
php artisan canvastack:test DatatablesHybridCompareTest
```

## Snapshot Replay (Feature: DatatablesInspectorReplayTest)
- Lokasi file snapshot: `storage/app/datatable-inspector/*.json`
- Tujuan: memutar ulang skenario Datatables berdasarkan snapshot riil, lalu membandingkan hasil legacy vs pipeline (HybridCompare)
- Alur utama:
  1) Membangun schema dinamis (base table + joined tables) dengan heuristik tipe kolom
  2) Seeding data dummy yang sesuai
  3) Menjalankan `HybridCompare` untuk mendapatkan `legacy_result` dan `diff`
  4) Menghitung `severity` dari `diff` dan memvalidasi terhadap `tolerance`

### Format Minimal File Snapshot
```json
{
  "table_name": "users",
  "columns": {
    "lists": ["id", "name", "email"],
    "blacklist": ["password", "action", "no"],
    "orderby": {"column": "id", "order": "desc"}
  },
  "joins": {
    "foreign_keys": {
      "users.id": "posts.user_id"
    },
    "selected": ["users.*"]
  },
  "filters": {"where": [], "applied": [], "raw_params": []},
  "paging": {"start": 0, "length": 10, "total": 3},
  "tolerance": 120
}
```
- Kolom wajib: `table_name`, `columns.lists`
- `joins.foreign_keys` menggunakan pasangan "table.col": "table.col" (sesuai legacy `leftJoin`)
- `tolerance` opsional (lihat bagian toleransi)
- Bila folder snapshot kosong, test menyediakan fallback skenario sederhana `users`

### Heuristik Tipe Kolom (Type Guessing)
- Integer:
  - `id`, `*_id`, `qty`, `count`, `number`, `year`, `*_year`, `age`
- Boolean:
  - `active`, `enabled`, `disabled`, `is_*`, `has_*`, `*_flag`
- Date/Time:
  - `*_at` (timestamp), `created_at`, `updated_at`, `deleted_at`, `date`, `*_date`, `time`, `*_time`, `datetime`
- Decimal (termasuk currency):
  - `amount`, `price`, `cost`, `balance`, `total`, `rate`, `ratio`, `percent`, `percentage`
- Geo (lat/lng):
  - `lat`, `latitude`, `lng`, `long`, `longitude` (presisi desimal khusus lat/lng)
- UUID:
  - `uuid`, `guid`, atau nama kolom yang mengandung kata tersebut
- JSON-like:
  - `json`, `meta`, `payload`, `attrs`, `attributes`
- IP/URL/Email/Phone:
  - `ip` → string(45)
  - `url`, `uri`, `link` → string(2048)
  - `email` → string
  - `phone`, `msisdn`, `mobile` → string
- Long text:
  - `desc`, `description`, `notes`, `remark`, `comment`, `message`
- Fallback:
  - `string`

### Aturan Seeding Nilai Dummy
- Integer → `1`
- Boolean → `true`
- Timestamp/Datetime → `now()` format `Y-m-d H:i:s`
- Date → `Y-m-d`
- Time → `H:i:s`
- Decimal → `1234.560000`
- Lat/Lng → contoh koordinat Jakarta (`lat=-6.20000000`, `lng=106.81666600`)
- Text → pengulangan teks pendek
- JSON → `{"k":"<nama_kolom>","v":"val"}` (string JSON)
- UUID → `00000000-0000-4000-8000-000000000000`
- IP → `127.0.0.1`
- URL → `https://example.test/<nama_kolom>`
- Email → `<nama_kolom>@example.test`
- Phone → `08123456789`
- Timezone → `UTC`

## Toleransi & Severity Diff
- Sumber nilai tolerance (prioritas):
  1) `tolerance` pada snapshot (atau `diff.tolerance`)
  2) ENV `CANVASTACK_DT_DIFF_TOLERANCE`
  3) Default test Feature Replay: `100`
- Bobot per komponen diff (computeSeverityScore) dan alasannya:
  - `data_length` → +50: perbedaan jumlah baris data sangat memengaruhi ekuitas hasil.
  - `recordsTotal` → +30: total catatan berubah, indikasi mismatch query global.
  - `recordsFiltered` → +30: hasil setelah filter berubah, indikasi mismatch filter.
  - `draw` → +5: metadata permintaan, bobot kecil.
  - `error` → +100: error operasional dari pipeline, sifatnya kritikal.
  - Kunci lain (tidak termasuk daftar di atas) → +1/keys: sinyal ada perbedaan lain yang belum dibobotkan spesifik.
  - `pipeline_output_unavailable` → 1000: pipeline tidak mengembalikan hasil sama sekali, blocker.

### Contoh Override Toleransi via ENV (PowerShell/Windows)
```bash
# Sementara menaikkan toleransi menjadi 150
$env:CANVASTACK_DT_DIFF_TOLERANCE = "150"
php artisan canvastack:test --suite=Canvastack-Table-Feature
```

### Per-snapshot Override
- Tambahkan `"tolerance": <int>` atau `"diff": {"tolerance": <int>}` di file JSON snapshot.

## Troubleshooting
- PHPUnit tidak ditemukan → pastikan `composer install` telah dijalankan (vendor tersedia)
- Tidak ada file snapshot → test akan menggunakan fallback otomatis (users)
- Error DB/sqlite → pastikan test berjalan di environment `testing` (perintah Artisan mengatur ENV sqlite in-memory otomatis)

## Catatan
- Beberapa test mungkin di-skip tergantung kondisi lingkungan (mis. tidak ada snapshot)
- Heuristik tipe kolom dapat ditambah kapan pun; ajukan pola nama kolom lain bila dibutuhkan