# SIAP TES (siaptes.id) - Platform Persiapan & Tryout Ujian Seleksi Nasional Terpadu

Aplikasi web simulasi ujian berbasis PHP & MySQL/SQLite dengan antarmuka modern terstandarisasi **Computer Assisted Test (CAT)**. Platform **SIAP TES** dirancang sebagai ekosistem persiapan seleksi karier terlengkap dalam satu pintu (domain: `siaptes.id`), mencakup:
- 🏛️ **CPNS & PPPK (ASN)**: Standar resmi BKN & PermenPAN-RB (TWK, TIU, TKP).
- 🏢 **Rekrutmen Bersama BUMN**: Standar FHCI (TKD, Core Values AKHLAK, & Business English).
- 🎓 **Sekolah Kedinasan**: SPMB PKN STAN, IPDN, STIS, Poltekip/Poltekim, STIN (SKD + Tes Potensi Akademik/Matematika Terapan).
- 🧠 **Psikotes Kerja & Asesmen Perusahaan**: Penalaran Logika Deduktif, Spasial Gambar 3D, dan Situational Judgement Test (SJT).

---

## 🌟 Fitur Utama Platform

1. **Satu Akun untuk Semua Jalur Ujian**:
   - Menghubungkan seluruh materi dan ribuan bank soal dalam 1 akun terintegrasi.
   - Pilihan simulasi berwaktu sesuai format ujian resmi masing-masing instansi.

2. **Simulasi CAT Presisi Tinggi**:
   - **Timer Hitung Mundur** otomatis per paket simulasi.
   - **Cloud Auto-Save (AJAX)**: Setiap pilihan jawaban tersimpan instan tanpa jeda atau refresh.
   - **Fitur Ragu-Ragu**: Menandai nomor soal dengan warna kuning untuk ditinjau ulang sebelum kumpul.
   - **Panel Navigasi Interaktif**: Pindah antarsoal dengan sekali klik.

3. **Formula Penilaian & Evaluasi Passing Grade Otomatis**:
   - **CPNS**: TWK (Ambang batas 65), TIU (Ambang batas 80), TKP (skala bobot 1–5, ambang batas 166).
   - **BUMN**: Standar persentase kelulusan TKD, Core Values AKHLAK, dan Bahasa Inggris.
   - **Kedinasan**: Akumulasi nilai TPA dan SKD.
   - **Psikotes**: Rekap akurasi penalaran logika, spasial, dan SJT.

4. **Sistem Freemium (Free Trial vs PRO VIP All-Access)**:
   - **Trial Percobaan Gratis**: Pengguna baru dapat langsung mencoba Paket Mini Tryout All-in-One tanpa dipungut biaya.
   - **PRO VIP All-Access**: Sekali aktivasi untuk membuka akses seluruh tryout akbar seumur hidup.
   - **Metode Pembayaran**: Dukungan QRIS Instant (GoPay, OVO, Dana, ShopeePay) serta Virtual Account Bank BCA, Mandiri, BRI, BNI.

5. **Dukungan Dual-Database (MySQL & SQLite)**:
   - **MySQL / MariaDB**: Kompatibel penuh dengan DBeaver, phpMyAdmin, atau MySQL CLI (`database/cpns_exam_mysql.sql` dan `database/siaptes_mysql.sql`).
   - **SQLite**: Mode fallback otomatis sehingga web dapat dijalankan secara instan tanpa perlu install server database.

6. **Integrasi Google OAuth 2.0**:
   - Pendaftaran dan masuk akun cepat menggunakan akun Google (SSO) atau mode simulasi instan.

7. **Desain UI/UX Coretax DJP & Gov-Tech Modern**:
   - Warna Korporat Resmi Deep Navy (`#002b49`) & Gold (`#fab005`).
   - Tipografi *Plus Jakarta Sans*.

---

## 🚀 Cara Menjalankan Website Secara Lokal

1. Buka terminal pada folder proyek:
   ```bash
   cd /Users/rizal/Documents/tryout_cpns
   ```

2. Jalankan built-in web server PHP:
   ```bash
   php -S 127.0.0.1:8000
   ```

3. Buka peramban (browser) di alamat:
   👉 **`http://127.0.0.1:8000`**

---

## 🗄️ Panduan Import Database ke DBeaver (MySQL)

1. Buka aplikasi **DBeaver** dan buka koneksi MySQL Anda.
2. Buat database:
   ```sql
   CREATE DATABASE IF NOT EXISTS cpns_exam CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Klik kanan pada database `cpns_exam` $\rightarrow$ pilih **Tools** $\rightarrow$ **Execute Script...** (atau buka SQL Editor).
4. Pilih berkas [`database/siaptes_mysql.sql`](database/siaptes_mysql.sql) atau [`database/cpns_exam_mysql.sql`](database/cpns_exam_mysql.sql) lalu klik **Start**.
5. Konfigurasi koneksi MySQL di [`config/database.php`](config/database.php) jika menggunakan password kustom.

---

## 🔑 Akun Demo Pengujian

| Peran (Role) | Email | Kata Sandi | Status Akses |
|---|---|---|---|
| **Administrator** | `admin@cpns.go.id` | `admin123` | Akses Penuh Panel Pengawas & Bank Soal |
| **Peserta Demo** | `peserta@cpns.go.id` | `peserta123` | Akses Member PRO VIP |

*Pengguna baru dapat langsung mendaftar akun gratis di menu **Daftar Akun** atau menggunakan tombol **Masuk dengan Akun Google**.*

---

## 📁 Struktur Direktori Proyek

```text
tryout_cpns/
├── assets/
│   └── css/
│       └── style.css            # Desain antarmuka Coretax Navy & Gold
├── config/
│   ├── database.php             # Konfigurasi dual driver MySQL & SQLite
│   └── google_oauth.php         # Konfigurasi integrasi Google OAuth 2.0
├── database/
│   ├── init.php                 # Skrip inisialisasi skema tabel
│   ├── seed_siaptes.php         # Seeder 255 butir soal multi-tes
│   ├── export_to_mysql.php      # Generator dump SQL MySQL
│   ├── siaptes_mysql.sql        # Berkas dump MySQL untuk DBeaver (217 KB)
│   ├── cpns_exam_mysql.sql      # Berkas dump MySQL (kompatibilitas)
│   └── cpns_exam.sqlite         # Database SQLite lokal aktif
├── includes/
│   ├── auth.php                 # Helper sesi autentikasi pengguna
│   ├── header.php               # Template navigasi atas SIAP TES (siaptes.id)
│   └── footer.php               # Template footer informatif
├── admin/
│   ├── index.php                # Dashboard metrik & kontrol administrator
│   ├── soal.php                 # Manajemen bank soal multi-kategori
│   ├── tambah_soal.php          # Form penambahan soal baru
│   └── paket.php                # Pengaturan paket simulasi tryout
├── index.php                    # Halaman beranda portal SIAP TES (siaptes.id)
├── latihan.php                  # Bank soal mandiri per kategori (10 kategori)
├── tryout.php                   # Pilihan paket simulasi berwaktu (5 paket)
├── exam.php                     # Lembar ujian interaktif CAT berwaktu
├── save_answer.php              # Endpoint AJAX autosave jawaban real-time
├── hasil.php                    # Rekap skor, grafik akurasi & pembahasan soal
├── riwayat.php                  # Riwayat skor ujian dan transkrip
├── login.php                    # Masuk portal
├── register.php                 # Registrasi peserta baru
├── logout.php                   # Logout
├── google-login.php             # Gerbang login Google SSO
├── google-callback.php          # Callback handler akun Google
├── upgrade.php                  # Halaman aktivasi paket PRO VIP All-Access
└── README.md                    # Dokumentasi resmi proyek
```
