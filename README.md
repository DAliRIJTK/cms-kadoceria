<div align="center">

<br/>

```
██╗  ██╗ █████╗ ██████╗  ██████╗  ██████╗███████╗██████╗ ██╗ █████╗ 
██║ ██╔╝██╔══██╗██╔══██╗██╔═══██╗██╔════╝██╔════╝██╔══██╗██║██╔══██╗
█████╔╝ ███████║██║  ██║██║   ██║██║     █████╗  ██████╔╝██║███████║
██╔═██╗ ██╔══██║██║  ██║██║   ██║██║     ██╔══╝  ██╔══██╗██║██╔══██║
██║  ██╗██║  ██║██████╔╝╚██████╔╝╚██████╗███████╗██║  ██║██║██║  ██║
╚═╝  ╚═╝╚═╝  ╚═╝╚═════╝  ╚═════╝  ╚═════╝╚══════╝╚═╝  ╚═╝╚═╝╚═╝  ╚═╝
```

### **Content Management System**
### Buku Interaktif Dwibahasa Indonesia–Sunda

<br/>

[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?style=for-the-badge&logo=postgresql&logoColor=white)](https://postgresql.org)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-3-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)](https://tailwindcss.com)

<br/>

> **Platform modern untuk mengelola buku digital interaktif dwibahasa Sunda–Indonesia,**
> lengkap dengan narasi audio, backsound, dan hotspot interaktif berbasis koordinat.

<br/>

[📖 Demo](#) · [🐛 Laporkan Bug](https://github.com/DAliRIJTK/cms-kadoceria/issues) · [✨ Usulkan Fitur](https://github.com/DAliRIJTK/cms-kadoceria/issues)

</div>

---

## 📋 Daftar Isi

- [Tentang Proyek](#-tentang-proyek)
- [Fitur Utama](#-fitur-utama)
- [Teknologi](#️-teknologi)
- [Cara Instalasi](#-cara-instalasi)
- [Konfigurasi Environment](#️-konfigurasi-environment)
- [Menjalankan Aplikasi](#-menjalankan-aplikasi)
- [Struktur Folder](#-struktur-folder)
- [Sistem Audio & Hotspot](#-sistem-audio--hotspot)
- [Konversi PDF ke Gambar](#️-konversi-pdf-ke-gambar)
- [Autentikasi](#-autentikasi)
- [API Publik](#-api-publik)
- [Integrasi Mobile](#-integrasi-mobile)
- [Author](#-author)

---

## 🎯 Tentang Proyek

**CMS Kadoceria** adalah sistem manajemen konten yang dikembangkan untuk mendukung digitalisasi buku cerita anak dwibahasa **Sunda–Indonesia**. Platform ini memungkinkan pengelola konten untuk mengunggah, mengatur, dan mempublikasikan buku digital interaktif yang dapat dikonsumsi oleh aplikasi mobile.

Sistem ini dirancang agar konten buku menjadi lebih **hidup**, **modern**, dan **mudah diakses** oleh anak-anak melalui pengalaman membaca yang diperkaya audio bilingual dan elemen interaktif.

**Permasalahan yang diselesaikan:**
- ❌ Buku cerita tradisional tidak interaktif dan sulit diakses secara digital
- ❌ Konten dwibahasa sulit dikelola tanpa platform khusus
- ❌ Tidak ada media yang mendukung narasi audio per halaman

**Solusi yang ditawarkan:**
- ✅ Upload PDF → otomatis menjadi halaman digital teroptimasi
- ✅ Dukungan konten dwibahasa (Indonesia & Sunda) dalam satu platform
- ✅ Narasi audio, backsound, dan hotspot interaktif per halaman

---

## ✨ Fitur Utama

### 📚 Manajemen Buku
| Fitur | Keterangan |
|-------|-----------|
| Upload & Konversi PDF | File PDF otomatis dikonversi menjadi halaman gambar format WebP yang teroptimasi |
| Metadata Dwibahasa | Judul, deskripsi, dan informasi buku dalam Bahasa Indonesia & Sunda |
| Status Publikasi | Kelola buku dengan status **Draft** (belum terbit) atau **Terbit** (publik) |
| Pencarian & Filter | Temukan buku dengan cepat menggunakan fitur pencarian dan filter kategori |

### 📄 Manajemen Halaman
| Fitur | Keterangan |
|-------|-----------|
| Drag & Drop Reorder | Atur urutan halaman secara intuitif dengan antarmuka seret dan lepas |
| Narasi Audio Indonesia | Upload audio narasi berbahasa Indonesia per halaman |
| Narasi Audio Sunda | Upload audio narasi berbahasa Sunda per halaman |
| Backsound | Tambahkan musik latar yang memperindah pengalaman membaca |
| Hotspot Interaktif | Definisikan area klik pada halaman dengan koordinat persentase + audio khusus |

### 🎛️ Fitur Tambahan
- **Dashboard Informatif** — Ringkasan statistik konten secara real-time
- **Manajemen Audio Latar** — Library terpusat untuk mengelola seluruh aset audio
- **API Publik (JSON)** — Endpoint siap pakai untuk konsumsi aplikasi mobile
- **UI Responsif** — Antarmuka modern dengan Tailwind CSS + Alpine.js
- **Autentikasi Aman** — Sistem login berbasis Laravel Breeze dengan proteksi CSRF

---

## 🛠️ Teknologi

```
┌─────────────────────────────────────────────────────────────┐
│                    STACK TEKNOLOGI                           │
├──────────────────┬──────────────────────────────────────────┤
│  Backend         │  Laravel 13  ·  PHP 8.3+                 │
│  Frontend        │  Blade  ·  Tailwind CSS  ·  Alpine.js    │
│  Database        │  PostgreSQL                              │
│  Konversi Aset   │  ImageMagick  ·  PHP Imagick Extension   │
│  Build Tool      │  Vite                                    │
│  Autentikasi     │  Laravel Breeze                          │
└──────────────────┴──────────────────────────────────────────┘
```

---

## 🚀 Cara Instalasi

### Prasyarat

Pastikan sistem kamu sudah memiliki:

- **PHP** versi 8.3 atau lebih baru
- **Composer** (dependency manager PHP)
- **Node.js** & **npm** (untuk build aset frontend)
- **PostgreSQL** (database)
- **ImageMagick** + ekstensi PHP `imagick` (untuk konversi PDF)

> 💡 **Tips:** Pastikan ekstensi `imagick` sudah aktif di `php.ini` dengan menjalankan `php -m | grep imagick`.

### Langkah Instalasi

#### 1. Clone Repository

```bash
git clone https://github.com/DAliRIJTK/cms-kadoceria.git
cd cms-kadoceria
```

#### 2. Install Dependensi

```bash
# Install dependensi PHP
composer install

# Install dependensi JavaScript
npm install
```

#### 3. Setup Environment

```bash
# Salin file konfigurasi
cp .env.example .env

# Generate application key
php artisan key:generate
```

#### 4. Konfigurasi Database

Edit file `.env` dan sesuaikan kredensial database kamu (lihat [Konfigurasi Environment](#️-konfigurasi-environment)).

```bash
# Jalankan migrasi database
php artisan migrate
```

#### 5. Setup Storage & Build

```bash
# Buat symbolic link untuk storage publik
php artisan storage:link

# Build aset frontend
npm run build
```

✅ **Instalasi selesai!** Lanjutkan ke langkah menjalankan aplikasi.

---

## ⚙️ Konfigurasi Environment

Sesuaikan variabel berikut pada file `.env` di root proyek:

```env
# ── Konfigurasi Aplikasi ──────────────────────────────────
APP_NAME="CMS Kadoceria"
APP_ENV=local
APP_KEY=                        # Diisi otomatis oleh php artisan key:generate
APP_DEBUG=true
APP_URL=http://localhost:8000

# ── Konfigurasi Database ──────────────────────────────────
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=cms_kadoceria
DB_USERNAME=postgres
DB_PASSWORD=your_password       # Ganti dengan password PostgreSQL kamu

# ── Konfigurasi Storage ───────────────────────────────────
FILESYSTEM_DISK=local
```

> ⚠️ **Penting:** Jangan pernah commit file `.env` ke repository. File ini sudah ada di `.gitignore`.

---

## ▶️ Menjalankan Aplikasi

### Mode Development

Buka **dua terminal** dan jalankan masing-masing perintah:

```bash
# Terminal 1 — Laravel development server
php artisan serve

# Terminal 2 — Vite asset watcher (hot reload)
npm run dev
```

Akses aplikasi di browser:

```
http://127.0.0.1:8000
```

### Mode Production

```bash
# Build aset untuk produksi
npm run build

# Optimasi Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 📂 Struktur Folder

```
cms-kadoceria/
│
├── app/
│   ├── Http/
│   │   ├── Controllers/        # Logic request & response
│   │   └── Middleware/         # Autentikasi & otorisasi
│   ├── Models/                 # Eloquent ORM (Book, Page, Hotspot, dsb.)
│   └── Services/               # Business logic (PDF konversi, dsb.)
│
├── database/
│   ├── migrations/             # Skema database
│   └── seeders/                # Data awal (opsional)
│
├── public/                     # Entry point & aset publik
│
├── resources/
│   ├── views/                  # Template Blade
│   ├── js/                     # JavaScript (Alpine.js, dsb.)
│   └── css/                    # Tailwind CSS source
│
├── routes/
│   ├── web.php                 # Route antarmuka web
│   └── api.php                 # Route API publik
│
├── storage/
│   └── app/public/             # File upload (PDF, gambar, audio)
│
└── README.md
```

---

## 🔊 Sistem Audio & Hotspot

Setiap halaman buku mendukung lapisan audio dan interaktivitas yang kaya:

```
┌─────────────────────────────────────────────────────────────┐
│                    HALAMAN BUKU                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│   [Gambar Halaman WebP]                                     │
│                                                             │
│   🎙️  Narasi Indonesia   →  audio_id.mp3                   │
│   🎙️  Narasi Sunda       →  audio_su.mp3                   │
│   🎵  Backsound          →  background.mp3                  │
│                                                             │
│   📍 Hotspot A  (x: 25%, y: 40%)  →  hotspot_a.mp3        │
│   📍 Hotspot B  (x: 60%, y: 70%)  →  hotspot_b.mp3        │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**Cara Kerja Hotspot:**
1. Admin mendefinisikan area hotspot menggunakan koordinat persentase `(x%, y%)`
2. Audio khusus di-assign untuk setiap hotspot
3. Saat pengguna mengetuk area tersebut di aplikasi mobile, audio akan diputar secara otomatis

---

## 🖼️ Konversi PDF ke Gambar

CMS secara otomatis memproses setiap file PDF yang diunggah melalui pipeline berikut:

```
PDF Diunggah
     │
     ▼
┌─────────────┐
│  Imagick    │  ← Ekstrak setiap halaman PDF
│  Extension  │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  WebP       │  ← Konversi ke format gambar terkompresi
│  Conversion │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  Optimasi   │  ← Ukuran dioptimalkan untuk performa mobile
│  & Storage  │
└─────────────┘
       │
       ▼
  Siap Ditampilkan
```

**Keuntungan format WebP:**
- Ukuran file lebih kecil hingga **30%** dibanding JPEG
- Kualitas gambar tetap terjaga
- Didukung oleh seluruh browser dan platform mobile modern

---

## 🔐 Autentikasi

Sistem autentikasi dibangun di atas **Laravel Breeze** dengan keamanan berlapis:

| Komponen | Keterangan |
|----------|-----------|
| **Session Auth** | Login berbasis sesi yang aman |
| **CSRF Protection** | Proteksi terhadap serangan Cross-Site Request Forgery |
| **Middleware** | Pembatasan akses halaman berdasarkan status autentikasi |
| **Password Hashing** | Password di-hash dengan algoritma **bcrypt** |

---

## 🌐 API Publik

CMS menyediakan REST API berbasis JSON untuk digunakan oleh aplikasi mobile. Semua endpoint bersifat publik (tidak memerlukan autentikasi).

### Endpoint Tersedia

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/api/books` | Daftar semua buku yang telah terbit |
| `GET` | `/api/books/{id}` | Detail buku beserta daftar halaman |
| `GET` | `/api/pages/{id}` | Detail halaman beserta hotspot & audio |

### Contoh Response

**`GET /api/books/{id}`**

```json
{
  "id": 1,
  "title_id": "Kado Ceria",
  "title_su": "Kado Ceria",
  "cover_image": "/storage/covers/kado-ceria.webp",
  "status": "published",
  "pages": [
    {
      "id": 1,
      "page_number": 1,
      "image_url": "/storage/pages/page-1.webp",
      "audio_id": "/storage/audio/narasi-id-1.mp3",
      "audio_su": "/storage/audio/narasi-su-1.mp3",
      "backsound": "/storage/audio/backsound-1.mp3",
      "hotspots": [
        {
          "id": 1,
          "x": 25.5,
          "y": 40.0,
          "audio": "/storage/audio/hotspot-1.mp3"
        }
      ]
    }
  ]
}
```

---

## 📱 Integrasi Mobile

CMS Kadoceria dirancang sebagai **backend** untuk aplikasi mobile Android interaktif yang menampilkan:

```
┌────────────────────────────────────────────┐
│           APLIKASI MOBILE                  │
├────────────────────────────────────────────┤
│  📖 Flipbook interaktif dengan animasi     │
│  🎙️  Narasi audio dwibahasa               │
│  📍 Hotspot yang dapat diklik              │
│  🎵 Backsound otomatis per halaman         │
│  🌐 Konten diambil dari CMS via API        │
└────────────────────────────────────────────┘
               ↕  REST API (JSON)
┌────────────────────────────────────────────┐
│           CMS KADOCERIA                    │
└────────────────────────────────────────────┘
```

---

## 👨‍💻 Author

<div align="center">

**Ali RI**

D3 Teknik Informatika · Politeknik Negeri Bandung

[![GitHub](https://img.shields.io/badge/GitHub-DAliRIJTK-181717?style=for-the-badge&logo=github)](https://github.com/DAliRIJTK)

</div>

---

## 📄 Lisensi

Proyek ini dikembangkan untuk keperluan **akademik** dan **pengembangan internal** di lingkungan Politeknik Negeri Bandung.

---

## ⭐ Dukungan

Jika proyek ini bermanfaat bagimu, pertimbangkan untuk:

- ⭐ **Memberikan bintang** pada repository ini
- 🍴 **Fork** dan kembangkan untuk proyek kamu sendiri
- 📢 **Bagikan** kepada rekan yang membutuhkan referensi serupa
- 🐛 **Laporkan bug** melalui [Issues](https://github.com/DAliRIJTK/cms-kadoceria/issues)

---

<div align="center">

Dibuat dengan ❤️ untuk digitalisasi literasi anak dwibahasa Sunda–Indonesia

</div>
