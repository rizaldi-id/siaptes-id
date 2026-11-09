<?php
// database/export_to_mysql.php
require_once __DIR__ . '/../config/database.php';

$db = getDB();

function escapeVal($val) {
    if ($val === null) return "NULL";
    return "'" . str_replace(["\\", "'"], ["\\\\", "''"], (string)$val) . "'";
}

$sql = "-- ============================================================\n";
$sql .= "-- Database Dump for SIAP TES Platform (siaptes.id)\n";
$sql .= "-- Cocok untuk di-import langsung di DBeaver / phpMyAdmin / MySQL CLI\n";
$sql .= "-- Mendukung Multi-Tes: CPNS, BUMN, Kedinasan, Psikotes Kerja\n";
$sql .= "-- ============================================================\n\n";
$sql .= "CREATE DATABASE IF NOT EXISTS `cpns_exam` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
$sql .= "USE `cpns_exam`;\n\n";
$sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

// Table schemas in MySQL format
$sql .= "-- 1. Table users\n";
$sql .= "DROP TABLE IF EXISTS `users`;\n";
$sql .= "CREATE TABLE `users` (\n";
$sql .= "  `id` INT AUTO_INCREMENT PRIMARY KEY,\n";
$sql .= "  `name` VARCHAR(150) NOT NULL,\n";
$sql .= "  `email` VARCHAR(150) NOT NULL UNIQUE,\n";
$sql .= "  `password` VARCHAR(255) NOT NULL,\n";
$sql .= "  `role` VARCHAR(20) DEFAULT 'user',\n";
$sql .= "  `is_premium` TINYINT DEFAULT 0,\n";
$sql .= "  `google_id` VARCHAR(100) DEFAULT NULL,\n";
$sql .= "  `avatar` VARCHAR(255) DEFAULT NULL,\n";
$sql .= "  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP\n";
$sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n";

$sql .= "-- 2. Table categories\n";
$sql .= "DROP TABLE IF EXISTS `categories`;\n";
$sql .= "CREATE TABLE `categories` (\n";
$sql .= "  `id` INT AUTO_INCREMENT PRIMARY KEY,\n";
$sql .= "  `code` VARCHAR(20) NOT NULL UNIQUE,\n";
$sql .= "  `name` VARCHAR(100) NOT NULL,\n";
$sql .= "  `category_group` VARCHAR(30) DEFAULT 'cpns',\n";
$sql .= "  `description` TEXT,\n";
$sql .= "  `passing_grade` INT DEFAULT 65\n";
$sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n";

$sql .= "-- 3. Table packages\n";
$sql .= "DROP TABLE IF EXISTS `packages`;\n";
$sql .= "CREATE TABLE `packages` (\n";
$sql .= "  `id` INT AUTO_INCREMENT PRIMARY KEY,\n";
$sql .= "  `title` VARCHAR(200) NOT NULL,\n";
$sql .= "  `category_group` VARCHAR(30) DEFAULT 'all',\n";
$sql .= "  `description` TEXT,\n";
$sql .= "  `duration_minutes` INT DEFAULT 100,\n";
$sql .= "  `is_premium` TINYINT DEFAULT 0,\n";
$sql .= "  `price` INT DEFAULT 0,\n";
$sql .= "  `is_active` TINYINT DEFAULT 1,\n";
$sql .= "  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP\n";
$sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n";

$sql .= "-- 4. Table transactions\n";
$sql .= "DROP TABLE IF EXISTS `transactions`;\n";
$sql .= "CREATE TABLE `transactions` (\n";
$sql .= "  `id` INT AUTO_INCREMENT PRIMARY KEY,\n";
$sql .= "  `invoice_number` VARCHAR(100) NOT NULL UNIQUE,\n";
$sql .= "  `user_id` INT NOT NULL,\n";
$sql .= "  `package_id` INT NULL,\n";
$sql .= "  `type` VARCHAR(50) NOT NULL,\n";
$sql .= "  `amount` INT NOT NULL,\n";
$sql .= "  `payment_method` VARCHAR(100) NOT NULL,\n";
$sql .= "  `proof_image` VARCHAR(255) NULL,\n";
$sql .= "  `status` VARCHAR(30) DEFAULT 'pending',\n";
$sql .= "  `notes` TEXT NULL,\n";
$sql .= "  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,\n";
$sql .= "  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n";
$sql .= "  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,\n";
$sql .= "  FOREIGN KEY (`package_id`) REFERENCES `packages`(`id`) ON DELETE SET NULL\n";
$sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n";

$sql .= "-- 5. Table questions\n";
$sql .= "DROP TABLE IF EXISTS `questions`;\n";
$sql .= "CREATE TABLE `questions` (\n";
$sql .= "  `id` INT AUTO_INCREMENT PRIMARY KEY,\n";
$sql .= "  `category_id` INT NOT NULL,\n";
$sql .= "  `package_id` INT NULL,\n";
$sql .= "  `question_text` TEXT NOT NULL,\n";
$sql .= "  `option_a` TEXT NOT NULL,\n";
$sql .= "  `option_b` TEXT NOT NULL,\n";
$sql .= "  `option_c` TEXT NOT NULL,\n";
$sql .= "  `option_d` TEXT NOT NULL,\n";
$sql .= "  `option_e` TEXT NOT NULL,\n";
$sql .= "  `correct_option` VARCHAR(5) NULL,\n";
$sql .= "  `score_a` INT DEFAULT 0,\n";
$sql .= "  `score_b` INT DEFAULT 0,\n";
$sql .= "  `score_c` INT DEFAULT 0,\n";
$sql .= "  `score_d` INT DEFAULT 0,\n";
$sql .= "  `score_e` INT DEFAULT 0,\n";
$sql .= "  `explanation` TEXT,\n";
$sql .= "  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,\n";
$sql .= "  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,\n";
$sql .= "  FOREIGN KEY (`package_id`) REFERENCES `packages`(`id`) ON DELETE SET NULL\n";
$sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n";

$sql .= "-- 6. Table exam_attempts\n";
$sql .= "DROP TABLE IF EXISTS `exam_attempts`;\n";
$sql .= "CREATE TABLE `exam_attempts` (\n";
$sql .= "  `id` INT AUTO_INCREMENT PRIMARY KEY,\n";
$sql .= "  `user_id` INT NOT NULL,\n";
$sql .= "  `package_id` INT NULL,\n";
$sql .= "  `mode` VARCHAR(30) NOT NULL,\n";
$sql .= "  `start_time` DATETIME DEFAULT CURRENT_TIMESTAMP,\n";
$sql .= "  `end_time` DATETIME NULL,\n";
$sql .= "  `duration_seconds` INT DEFAULT 0,\n";
$sql .= "  `status` VARCHAR(30) DEFAULT 'in_progress',\n";
$sql .= "  `score_twk` INT DEFAULT 0,\n";
$sql .= "  `score_tiu` INT DEFAULT 0,\n";
$sql .= "  `score_tkp` INT DEFAULT 0,\n";
$sql .= "  `total_score` INT DEFAULT 0,\n";
$sql .= "  `is_passed` TINYINT DEFAULT 0,\n";
$sql .= "  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,\n";
$sql .= "  FOREIGN KEY (`package_id`) REFERENCES `packages`(`id`) ON DELETE CASCADE\n";
$sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n";

$sql .= "-- 7. Table user_answers\n";
$sql .= "DROP TABLE IF EXISTS `user_answers`;\n";
$sql .= "CREATE TABLE `user_answers` (\n";
$sql .= "  `id` INT AUTO_INCREMENT PRIMARY KEY,\n";
$sql .= "  `attempt_id` INT NOT NULL,\n";
$sql .= "  `question_id` INT NOT NULL,\n";
$sql .= "  `selected_option` VARCHAR(5) NULL,\n";
$sql .= "  `score_obtained` INT DEFAULT 0,\n";
$sql .= "  `is_doubt` TINYINT DEFAULT 0,\n";
$sql .= "  FOREIGN KEY (`attempt_id`) REFERENCES `exam_attempts`(`id`) ON DELETE CASCADE,\n";
$sql .= "  FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE\n";
$sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n";

// 8. Table articles
$sql .= "-- 8. Table articles (SEO & Content Marketing)\n";
$sql .= "DROP TABLE IF EXISTS `articles`;\n";
$sql .= "CREATE TABLE `articles` (\n";
$sql .= "  `id` INT AUTO_INCREMENT PRIMARY KEY,\n";
$sql .= "  `title` VARCHAR(255) NOT NULL,\n";
$sql .= "  `slug` VARCHAR(255) NOT NULL UNIQUE,\n";
$sql .= "  `category` VARCHAR(80) NOT NULL DEFAULT 'CPNS & PPPK',\n";
$sql .= "  `excerpt` TEXT NOT NULL,\n";
$sql .= "  `content` LONGTEXT NOT NULL,\n";
$sql .= "  `cover_image` VARCHAR(500) DEFAULT NULL,\n";
$sql .= "  `author_name` VARCHAR(120) DEFAULT 'Tim Redaksi SIAP TES',\n";
$sql .= "  `reading_time` INT DEFAULT 5,\n";
$sql .= "  `views` INT DEFAULT 0,\n";
$sql .= "  `is_featured` TINYINT DEFAULT 0,\n";
$sql .= "  `is_published` TINYINT DEFAULT 1,\n";
$sql .= "  `tags` VARCHAR(255) DEFAULT NULL,\n";
$sql .= "  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,\n";
$sql .= "  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP\n";
$sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n";

require_once __DIR__ . '/../includes/articles_helper.php';
ensureArticlesTable($db);

// Dump current data from sqlite to MySQL inserts
foreach (["users", "categories", "packages", "questions", "articles"] as $t) {
    $rows = $db->query("SELECT * FROM $t")->fetchAll();
    if (!empty($rows)) {
        $sql .= "-- Data for table `$t`\n";
        $cols = array_keys($rows[0]);
        $colNames = implode("`, `", $cols);
        foreach ($rows as $row) {
            $vals = [];
            foreach ($row as $v) {
                $vals[] = escapeVal($v);
            }
            $sql .= "INSERT INTO `$t` (`" . $colNames . "`) VALUES (" . implode(", ", $vals) . ");\n";
        }
        $sql .= "\n";
    }
}

$sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";

file_put_contents(__DIR__ . '/cpns_exam_mysql.sql', $sql);
file_put_contents(__DIR__ . '/siaptes_mysql.sql', $sql);
echo "File database/cpns_exam_mysql.sql & database/siaptes_mysql.sql created successfully (" . strlen($sql) . " bytes).\n";
