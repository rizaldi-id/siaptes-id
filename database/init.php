<?php
// database/init.php
require_once __DIR__ . '/../config/database.php';

$db = getDB();

// 1. Users table
$db->exec("
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    role TEXT DEFAULT 'user', -- 'user' or 'admin'
    is_premium INTEGER DEFAULT 0, -- 0: Free / Trial, 1: Premium Member
    google_id TEXT NULL,
    avatar TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
");

// 2. Categories table (TWK, TIU, TKP, BUMN, Kedinasan, Psikotes)
$db->exec("
CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT UNIQUE NOT NULL, -- 'TWK', 'TIU', 'TKP', 'TKD_BUMN', 'AKHLAK', 'ENG_BUMN', 'KEDINASAN_TPA', 'PSIKO_LOGIKA', 'PSIKO_SPASIAL', 'PSIKO_SJT'
    name TEXT NOT NULL,
    category_group TEXT DEFAULT 'cpns', -- 'cpns', 'bumn', 'kedinasan', 'psikotes'
    description TEXT,
    passing_grade INTEGER DEFAULT 65
);
");

// 3. Tryout Packages table
$db->exec("
CREATE TABLE IF NOT EXISTS packages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    category_group TEXT DEFAULT 'all', -- 'all', 'cpns', 'bumn', 'kedinasan', 'psikotes'
    description TEXT,
    duration_minutes INTEGER DEFAULT 100,
    is_premium INTEGER DEFAULT 0, -- 0: Free Trial, 1: Premium / Berbayar
    price INTEGER DEFAULT 0, -- Harga paket (Rp)
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
");

// Transactions table
$db->exec("
CREATE TABLE IF NOT EXISTS transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_number TEXT UNIQUE NOT NULL,
    user_id INTEGER NOT NULL,
    package_id INTEGER NULL,
    type TEXT NOT NULL, -- 'package' or 'lifetime_premium'
    amount INTEGER NOT NULL,
    payment_method TEXT NOT NULL,
    proof_image TEXT NULL,
    status TEXT DEFAULT 'pending', -- 'pending', 'approved', 'rejected'
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE SET NULL
);
");

// 4. Questions table
$db->exec("
CREATE TABLE IF NOT EXISTS questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER NOT NULL,
    package_id INTEGER NULL, -- NULL means general bank/latihan, or can be assigned to package
    question_text TEXT NOT NULL,
    option_a TEXT NOT NULL,
    option_b TEXT NOT NULL,
    option_c TEXT NOT NULL,
    option_d TEXT NOT NULL,
    option_e TEXT NOT NULL,
    correct_option TEXT, -- 'A', 'B', 'C', 'D', 'E' (For TWK & TIU)
    score_a INTEGER DEFAULT 0, -- For TKP weighted scoring (1 to 5)
    score_b INTEGER DEFAULT 0,
    score_c INTEGER DEFAULT 0,
    score_d INTEGER DEFAULT 0,
    score_e INTEGER DEFAULT 0,
    explanation TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE SET NULL
);
");

// 5. Exam Sessions / Attempts
$db->exec("
CREATE TABLE IF NOT EXISTS exam_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    package_id INTEGER NULL, -- NULL if practice mode
    mode TEXT NOT NULL, -- 'tryout' or 'practice'
    start_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    end_time DATETIME NULL,
    duration_seconds INTEGER DEFAULT 0,
    status TEXT DEFAULT 'in_progress', -- 'in_progress', 'completed'
    score_twk INTEGER DEFAULT 0,
    score_tiu INTEGER DEFAULT 0,
    score_tkp INTEGER DEFAULT 0,
    total_score INTEGER DEFAULT 0,
    is_passed INTEGER DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE
);
");

// 6. User Answers
$db->exec("
CREATE TABLE IF NOT EXISTS user_answers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    attempt_id INTEGER NOT NULL,
    question_id INTEGER NOT NULL,
    selected_option TEXT NULL,
    score_obtained INTEGER DEFAULT 0,
    is_doubt INTEGER DEFAULT 0, -- Ragu-ragu (1 or 0)
    FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);
");

// Safe migrations for existing SQLite / MySQL schemas
try {
    $db->exec("ALTER TABLE categories ADD COLUMN category_group TEXT DEFAULT 'cpns'");
} catch (Exception $e) {}

try {
    $db->exec("ALTER TABLE packages ADD COLUMN category_group TEXT DEFAULT 'all'");
} catch (Exception $e) {}

// 7. Articles table & initial seed
require_once __DIR__ . '/../includes/articles_helper.php';
ensureArticlesTable($db);

echo "Tables created and migrated successfully.\n";
