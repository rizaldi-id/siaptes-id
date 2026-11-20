<?php
// config/database.php

/**
 * Konfigurasi Driver Database
 * Pilihan 'DB_DRIVER':
 * - 'mysql'  : Menggunakan MySQL / MariaDB (Dapat dikelola via DBeaver / phpMyAdmin)
 * - 'sqlite' : Menggunakan SQLite file bawaan lokal (Otomatis tanpa install server MySQL)
 */
define('DB_DRIVER', getenv('DB_DRIVER') ?: 'mysql'); // Ubah ke 'sqlite' jika MySQL belum dinyalakan

// Konfigurasi MySQL
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'cpns_exam');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: ''); // Isi kata sandi root MySQL Anda jika ada

// Konfigurasi SQLite (Fallback)
define('DB_FILE', __DIR__ . '/../database/cpns_exam.sqlite');

function getDB() {
    static $db = null;
    if ($db === null) {
        $driver = DB_DRIVER;

        if ($driver === 'mysql') {
            try {
                $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
                $db = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 1,
                ]);
            } catch (PDOException $e) {
                // Jika koneksi MySQL belum aktif / database belum dibuat,
                // beri pesan informatif atau fallback ke SQLite agar web tetap berjalan
                if (file_exists(DB_FILE)) {
                    // Fallback aman ke sqlite
                    $db = new PDO('sqlite:' . DB_FILE);
                    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                    $db->exec('PRAGMA foreign_keys = ON;');
                } else {
                    die("Gagal terkoneksi ke MySQL: " . $e->getMessage() . "<br>Pastikan server MySQL berjalan dan database 'cpns_exam' sudah dibuat via DBeaver.");
                }
            }
        } else {
            // SQLite mode
            $db = new PDO('sqlite:' . DB_FILE);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $db->exec('PRAGMA foreign_keys = ON;');
        }

        // Self-healing: Pastikan kolom category_group ada di tabel categories dan packages
        // baik di MySQL maupun SQLite agar query tidak pernah crash
        ensureSchemaCompatibility($db);
    }
    return $db;
}

/**
 * Memastikan kolom yang dibutuhkan sistem multi-tes selalu ada
 */
function ensureSchemaCompatibility(PDO $db) {
    static $migrated = false;
    if ($migrated) return;
    $migrated = true;

    try {
        $db->query("SELECT category_group FROM categories LIMIT 1");
    } catch (Exception $e) {
        try {
            $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
            $colDef = ($driver === 'mysql') ? "VARCHAR(30) DEFAULT 'cpns'" : "TEXT DEFAULT 'cpns'";
            $db->exec("ALTER TABLE categories ADD COLUMN category_group $colDef");
            // Set default category_group mapping berdasarkan code jika baru ditambahkan
            $db->exec("UPDATE categories SET category_group = 'cpns' WHERE code IN ('TWK', 'TIU', 'TKP')");
            $db->exec("UPDATE categories SET category_group = 'bumn' WHERE code IN ('TKD_BUMN', 'AKHLAK', 'ENG_BUMN')");
            $db->exec("UPDATE categories SET category_group = 'kedinasan' WHERE code IN ('KEDINASAN_TPA')");
            $db->exec("UPDATE categories SET category_group = 'psikotes' WHERE code IN ('PSIKO_LOGIKA', 'PSIKO_SPASIAL', 'PSIKO_SJT')");
        } catch (Exception $ex) {}
    }

    try {
        $db->query("SELECT category_group FROM packages LIMIT 1");
    } catch (Exception $e) {
        try {
            $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
            $colDef = ($driver === 'mysql') ? "VARCHAR(30) DEFAULT 'all'" : "TEXT DEFAULT 'all'";
            $db->exec("ALTER TABLE packages ADD COLUMN category_group $colDef");
        } catch (Exception $ex) {}
    }

    // Pastikan kolom referensi payment gateway tersedia pada tabel transactions
    try {
        $db->query("SELECT gateway_reference FROM transactions LIMIT 1");
    } catch (Exception $e) {
        try {
            $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
            $varcharCol = ($driver === 'mysql') ? "VARCHAR(120) NULL" : "TEXT NULL";
            $textCol = "TEXT NULL";
            $db->exec("ALTER TABLE transactions ADD COLUMN gateway_reference $varcharCol");
            $db->exec("ALTER TABLE transactions ADD COLUMN gateway_qr_url $textCol");
            $db->exec("ALTER TABLE transactions ADD COLUMN gateway_checkout_url $textCol");
        } catch (Exception $ex) {}
    }

    // Sinkronisasi standar nama paket "Tryout CPNS" (110 soal) & Passing Grade resmi BKN
    try {
        $stmtPkg1 = $db->prepare("SELECT id, title FROM packages WHERE id = 1 LIMIT 1");
        $stmtPkg1->execute();
        $pkg1 = $stmtPkg1->fetch(PDO::FETCH_ASSOC);
        if ($pkg1 && (stripos((string)$pkg1['title'], 'Tryout Akbar') !== false || strpos((string)$pkg1['title'], '2024/2025') !== false)) {
            $newDesc = 'Simulasi resmi CAT BKN lengkap 110 butir soal acak dari bank soal: TWK (30 soal, PG 65), TIU (35 soal, PG 80), dan TKP (45 soal, PG 166) dalam 100 menit sesuai standar BKN.';
            $db->prepare("UPDATE packages SET title = ?, description = ?, duration_minutes = 100, category_group = 'cpns' WHERE id = 1")
               ->execute(['Tryout CPNS', $newDesc]);
        }

        // Pastikan passing grade resmi BKN selalu konsisten pada tabel categories
        $db->exec("UPDATE categories SET passing_grade = 65 WHERE code = 'TWK' AND passing_grade != 65");
        $db->exec("UPDATE categories SET passing_grade = 80 WHERE code = 'TIU' AND passing_grade != 80");
        $db->exec("UPDATE categories SET passing_grade = 166 WHERE code = 'TKP' AND passing_grade != 166");
    } catch (Throwable $e) {}
}

/**
 * Helper untuk fungsi random ordering yang kompatibel dengan MySQL & SQLite
 */
function sqlRandomOrder($db = null) {
    if ($db === null) {
        $db = getDB();
    }
    try {
        $driverName = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        return ($driverName === 'mysql') ? 'RAND()' : 'RANDOM()';
    } catch (Throwable $e) {
        return (DB_DRIVER === 'mysql') ? 'RAND()' : 'RANDOM()';
    }
}

/**
 * Helper untuk format waktu sekarang yang kompatibel
 */
function sqlCurrentTimestamp() {
    return date('Y-m-d H:i:s');
}
