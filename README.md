# Dynamic Form Feed Consumer & Risk Assessment Application

Aplikasi web fullstack berbasis **Laravel** dan **PostgreSQL** yang mengkonsumsi feed data berformat JSON (`database/submission.json` atau upload langsung dari frontend), menyimpannya ke dalam basis data relasional dengan indeks yang dioptimalkan, menyediakan RESTful API, dan menyajikan frontend dinamis untuk menampilkan serta mengirimkan formulir risiko operasional.

---

## 📋 Fitur Utama & Kesesuaian Kriteria Uji

1. **Memory-Optimized JSON Feed Consumer (`LazyCollection` Generator)**
   - Mengkonsumsi data JSON berukuran dinamis secara efisien menggunakan **`Laravel LazyCollection` (PHP Generator)** dan transaksi database (`DB::transaction`).
   - Memori konstan ($O(1)$ memory footprint) tanpa menahan seluruh array pohon objek di RAM.
   - Mengekstrak tipe data analitik (`numeric_value`, `date_value`, `text_value`) dari payload dinamis untuk kebutuhan *Data Analytics & Reporting*.
   - Dilengkapi Artisan Command: `php artisan feed:consume`.

2. **Self-Healing Frontend & Web-Based JSON Upload Engine**
   - **Zero Configuration for User:** Jika database dalam keadaan kosong (misal setelah migrasi awal atau setelah pengetesan), UI otomatis memunculkan panel unggah file `submission.json` secara instan dari browser.
   - **Penyimpanan Berkas Privat:** Setiap file feed yang diunggah secara otomatis disimpan di direktori privat yang aman: `storage/app/private/private_feeds/` dengan penamaan timestamp unik.
   - Terdapat tombol *"Unggah File JSON Baru"* untuk memperbarui skema form kapan saja tanpa perlu menyentuh terminal CLI.
   - Notifikasi interaktif dengan fitur *Auto-Dismiss* (otomatis menghilang setelah 4 detik).

3. **Well-Formed Relational Database & Indexing Strategy**
   - Struktur database relasional ternormalisasi: `sections`, `fields`, `field_options`, `submissions`, dan `answers`.
   - Menggunakan primary key `string` unik yang konsisten dengan payload JSON feed.
   - **Indexing Strategy**:
     - Foreign Key Indexing (`parent_id`, `field_id`, `submission_id`) untuk kecepatan join query.
     - Composite Index `(submission_id, field_id)` untuk efisiensi lookup jawaban.
     - B-Tree Index pada `numeric_value`, `date_value`, dan `type` untuk performa agregasi analitik.

4. **RESTful API Endpoints**
   - `GET /api/form-schema` : Mengambil seluruh skema struktur form (Sections, Fields, Options) beserta nilai default/jawaban awal.
   - `POST /api/upload-feed` : Menerima unggahan file JSON dari frontend, menyimpannya ke private storage, dan langsung memprosesnya ke database.
   - `POST /api/submissions` : Menyimpan pengisian form baru dari user ke basis data.
   - `GET /api/submissions` : Mengambil seluruh riwayat pengisian formulir.
   - `GET /api/submissions/{id}` : Mengambil detail submission tertentu beserta seluruh isian jawabannya.

5. **Automated Testing Suite (Unit & Feature Tests)**
   - `FeedConsumerTest` : Memverifikasi integritas konsumsi file feed dan penyimpanan tabel relasional.
   - `FormApiTest` : Memverifikasi endpoint schema, upload feed, submission form, dan penanganan validasi data (status 200, 201, 422).

---

## 🏗️ Arsitektur Basis Data (ERD)

```text
[Sections] (1) ───< (N) [Fields] (1) ───< (N) [Field Options]
                            │ (1)
                            │
                            └───< (N) [Answers] (N) >─── (1) [Submissions]
```

### Penjelasan Tabel:
* **`sections`** : Menyimpan kategori/grup pertanyaan form (contoh: *Detail Kejadian Risiko Operasional*, *Detail Kerugian*).
* **`fields`** : Menyimpan definisi setiap input pertanyaan (label, tipe input, sub-tipe, deskripsi).
* **`field_options`** : Menyimpan opsi pilihan untuk tipe input `radio_button` dan `checkbox`.
* **`submissions`** : Menyimpan entitas pengiriman form (judul, waktu pengiriman).
* **`answers`** : Menyimpan detail jawaban yang terhubung ke `submission` dan `field`, lengkap dengan kolom nilai terstruktur (`text_value`, `numeric_value`, `date_value`, `raw_answer`, `supporting_file`).

---

## 📁 Struktur Direktori Utama

```text
vesperia-backend/
├── app/
│   ├── Console/Commands/ConsumeJsonFeed.php   # Artisan command: feed:consume
│   ├── Http/Controllers/Api/FormController.php # REST API controller
│   ├── Models/                                # Eloquent Models (Section, Field, FieldOption, Submission, Answer)
│   └── Services/FeedConsumerService.php       # Core business logic (LazyCollection streaming generator)
├── database/
│   ├── migrations/                            # Skema tabel PostgreSQL
│   └── submission.json                        # File payload JSON referensi
├── resources/
│   └── views/form.blade.php                   # Frontend Dynamic Risk Assessment Form (Blade + Vanilla JS)
├── storage/app/
│   └── private/
│       └── private_feeds/                     # Direktori penyimpanan berkas JSON feed hasil upload
├── tests/
│   └── Feature/
│       ├── FeedConsumerTest.php               # Test suite untuk feed consumer
│       └── FormApiTest.php                    # Test suite untuk endpoint API
├── answers.md                                 # Jawaban pertanyaan teknis tantangan
└── README.md                                  # Dokumentasi lengkap sistem
```

---

## 🚀 Panduan Instalasi & Menjalankan Project

### Prasyarat:
* PHP >= 8.2 (dengan ekstensi `pdo_pgsql`, `pgsql`, `mbstring`)
* Composer
* PostgreSQL Database Server

---

### Langkah-langkah:

#### 1. Masuk ke Direktori Project
```powershell
cd vesperia-backend
```

#### 2. Install Dependensi PHP
```powershell
composer install
```

#### 3. Konfigurasi Environment (`.env`)
Sesuaikan konfigurasi PostgreSQL di file `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=vesperia_db
DB_USERNAME=username_postgres_anda
DB_PASSWORD="password_postgres_anda"
```

#### 4. Generate Application Key
```powershell
php artisan key:generate
```

#### 5. Jalankan Database Migration
```powershell
php artisan migrate
```

#### 6. Menjalankan Server & Mengisi Data
Jalankan server lokal:
```powershell
php artisan serve
```
Buka browser di **[http://127.0.0.1:8000](http://127.0.0.1:8000)**:
* **Opsi A (Via Browser):** Anda bisa langsung memilih dan mengunggah file `submission.json` melalui panel upload pada tampilan web.
* **Opsi B (Via CLI):** Anda juga bisa menjalankan `php artisan feed:consume` di terminal.

---

## 🧪 Menjalankan Automated Tests

Aplikasi dilengkapi dengan test suite lengkap untuk memastikan keandalan backend dan API:

```powershell
php artisan test
```

---

## 📡 Dokumentasi API

### 1. Ambil Skema Form
* **URL:** `/api/form-schema`
* **Method:** `GET`
* **Response (200 OK):**
```json
{
  "success": true,
  "message": "Form schema berhasil diambil",
  "data": [
    {
      "id": "1609227754-u4t8-cck1-w18p7azbr",
      "name": "Detail Kejadian Risiko Operasional",
      "order": 0,
      "payloads": [
        {
          "id": "1617779234-f0oy-phln-ppl0u1qx5",
          "label": "Bulan Pelaporan",
          "type": "radio_button",
          "options": [
            { "id": "1617779275-lt0k-zexz-uol8cts7s", "label": "Januari", "value": "" }
          ],
          "answer": { "value": [...] }
        }
      ]
    }
  ]
}
```

### 2. Upload File JSON Feed Baru
* **URL:** `/api/upload-feed`
* **Method:** `POST`
* **Headers:** `Content-Type: multipart/form-data`
* **Body:** `feed_file` (File JSON)
* **Response (200 OK):**
```json
{
  "success": true,
  "message": "File JSON berhasil diunggah dan skema form berhasil dibuat!",
  "data": {
    "sections": 2,
    "fields": 11,
    "options": 40,
    "answers": 11
  }
}
```

### 3. Submit Pengisian Form
* **URL:** `/api/submissions`
* **Method:** `POST`
* **Headers:** `Content-Type: application/json`
* **Request Body:**
```json
{
  "title": "Laporan Risiko Operasional Q1",
  "answers": [
    {
      "field_id": "1617779234-f0oy-phln-ppl0u1qx5",
      "value": [
        { "id": "1617779275-lt0k-zexz-uol8cts7s", "label": "Januari", "value": "" }
      ]
    },
    {
      "field_id": "1619062883-xsov-yboj-1hjgvgasn",
      "value": "6000"
    }
  ]
}
```
* **Response (201 Created):**
```json
{
  "success": true,
  "message": "Form berhasil disimpan",
  "data": {
    "submission_id": 1,
    "submitted_at": "2026-09-15T04:30:00.000000Z"
  }
}
```

### 4. Riwayat Submissions
* **URL:** `/api/submissions`
* **Method:** `GET`
* **Response (200 OK):** Mengembalikan daftar seluruh submission beserta jawaban terstrukturnya.
