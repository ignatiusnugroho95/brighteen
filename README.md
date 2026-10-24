# Brighteen - School Journalism & Student Magazine Platform

Brighteen adalah platform jurnalisme sekolah dan majalah digital modern yang dirancang untuk mendukung ekosistem penerbitan berita, artikel, dan karya kreatif siswa secara terstruktur, kolaboratif, dan interaktif.

## Fitur Utama

- **Public Frontpage**:
  - Hero Cover Headline & Ticker Pengumuman Resmi Berjalan (*running marquee*).
  - Kurasi 4 Berita Pilihan Sekolah (*Curated School Stories*).
  - Tampilan Feed Berita Terbaru, Kategori, dan Top Stories.
  - Artikel Pembaca & Komentar Interaktif.
- **Editorial Desk (`/dashboard/editorial`)**:
  - Manajemen Penugasan Liputan (*Story Assignments*) untuk jurnalis siswa lengkap dengan batas waktu dan pelacakan status.
  - Antrean Moderasi & Review (*Submissions Queue*) dengan fitur inspeksi, approval, dan penolakan disertai catatan reviewer.
  - Pengelolaan Berita Terbit (*Published Stories*), promosi Headline Hero, dan pin ke Berita Terpilih.
  - Mobile-responsive collapsible accordion view untuk menghemat ruang pada perangkat ponsel.
- **Story Creation & Publishing**:
  - Editor artikel kaya fitur dengan integrasi kategori beat, tagging, dan cover foto.
  - Dialog pemilihan penugasan saat pembuatan draf artikel.
- **Roles & Permissions**:
  - Management, Editor, Journalist, Contributor, dan Reader.
- **Light & Dark Mode**:
  - Glassmorphic UI design system dengan Tailwind CSS dan Alpine.js.

## Struktur Direktori

- `app/`: Controller, Model, View Blade Renderer, dan Helper.
- `bootstrap/`: Inisialisasi framework, database PDO, dan sistem autentikasi.
- `config/`: Konfigurasi sistem dan aplikasi.
- `database/`: Skema SQL, seeder, dan file pendukung database.
- `resources/views/`: Template Blade untuk halaman publik, dashboard editorial, dan manajemen artikel.
- `routes/`: Rute web dan routing request.
- `public/`: Asset statis (CSS, JS, gambar).

## Persyaratan Sistem

- PHP 8.0+
- MySQL / MariaDB (XAMPP / Laragon)
- Ekstensi PHP: `pdo_mysql`, `mbstring`, `json`
- Apache Web Server dengan `mod_rewrite` aktif

## Cara Menjalankan

1. Salin repository ke direktori web root (contoh: `c:/xampp/htdocs/brighteen`).
2. Duplikasi file konfigurasi `.env.example` menjadi `.env`:
   ```bash
   cp .env.example .env
   ```
3. Sesuaikan koneksi database di file `.env`.
4. Import skema database dari `database/sql/schema.sql` dan data awal dari `database/sql/seeders.sql`.
5. Akses platform melalui browser di:
   ```
   http://localhost:8080/brighteen
   ```
