<?php
// includes/visitor_tracker.php
// Sistem Pelacakan & Statistik Pengunjung Harian (Kompatibel MySQL & SQLite)

/**
 * Pastikan tabel statistik pengunjung tersedia (non-destruktif)
 */
function ensureVisitorTables(PDO $db): void {
    static $ready = false;
    if ($ready) {
        return;
    }
    $ready = true;

    try {
        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $pkDef = ($driver === 'mysql')
            ? 'INT AUTO_INCREMENT PRIMARY KEY'
            : 'INTEGER PRIMARY KEY AUTOINCREMENT';

        $db->exec("
            CREATE TABLE IF NOT EXISTS visitor_daily_stats (
                visit_date VARCHAR(10) PRIMARY KEY,
                unique_visitors INT DEFAULT 0,
                page_views INT DEFAULT 0,
                logged_in_users INT DEFAULT 0
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS visitor_logs (
                id $pkDef,
                visit_date VARCHAR(10) NOT NULL,
                visitor_hash VARCHAR(64) NOT NULL,
                user_id INT NULL,
                last_page VARCHAR(180) NULL,
                hits INT DEFAULT 1,
                first_seen VARCHAR(19) NOT NULL,
                last_seen VARCHAR(19) NOT NULL,
                UNIQUE (visit_date, visitor_hash)
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS visitor_page_stats (
                visit_date VARCHAR(10) NOT NULL,
                page_path VARCHAR(120) NOT NULL,
                views INT DEFAULT 1,
                PRIMARY KEY (visit_date, page_path)
            )
        ");
    } catch (Throwable $e) {
        // Jangan ganggu jalannya halaman web
    }
}

/**
 * Catat kunjungan pengguna pada halaman publik/peserta
 */
function trackSiteVisit(PDO $db): void {
    static $trackedThisRequest = false;
    if ($trackedThisRequest) {
        return;
    }
    $trackedThisRequest = true;

    try {
        ensureVisitorTables($db);

        // Lewati halaman admin, API/JSON endpoint, dan CLI
        if (php_sapi_name() === 'cli') {
            return;
        }
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        if (strpos($scriptName, '/admin/') !== false) {
            return;
        }
        $baseScript = basename($scriptName);
        if (in_array($baseScript, ['check_payment_status.php', 'payment_webhook.php', 'save_answer.php', 'sync_db.php'], true)) {
            return;
        }

        // Saring crawler/bot dasar agar data pengunjung lebih akurat
        $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
        if ($ua !== '' && preg_match('/bot|crawl|spider|slurp|lighthouse|headlesschrome/i', $ua)) {
            return;
        }

        // Gunakan zona waktu WIB (UTC+7) untuk pengelompokan harian
        $tz = new DateTimeZone('Asia/Jakarta');
        $nowObj = new DateTime('now', $tz);
        $today = $nowObj->format('Y-m-d');
        $nowStr = $nowObj->format('Y-m-d H:i:s');

        $ip = $_SERVER['HTTP_CF_CONNECTING_IP']
            ?? $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '0.0.0.0';
        if (strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }

        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
        $visitorHash = hash('sha256', $today . '|' . $ip . '|' . substr($ua, 0, 120));

        // Normalisasi nama halaman untuk statistik populer
        $pagePath = $baseScript ?: 'index.php';
        if ($pagePath === 'baca-artikel.php' && !empty($_GET['slug'])) {
            $pagePath = 'baca-artikel.php?slug=' . mb_substr((string)$_GET['slug'], 0, 60);
        } elseif ($pagePath === 'latihan.php' && !empty($_GET['group'])) {
            $pagePath = 'latihan.php?group=' . mb_substr((string)$_GET['group'], 0, 20);
        } elseif ($pagePath === 'tryout.php' && !empty($_GET['track'])) {
            $pagePath = 'tryout.php?track=' . mb_substr((string)$_GET['track'], 0, 20);
        }
        $pagePath = mb_substr($pagePath, 0, 115);

        // 1. Cek apakah pengunjung unik ini sudah tercatat hari ini
        $stmtCheck = $db->prepare("SELECT id, user_id FROM visitor_logs WHERE visit_date = ? AND visitor_hash = ? LIMIT 1");
        $stmtCheck->execute([$today, $visitorHash]);
        $existingLog = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        // Pastikan baris visitor_daily_stats hari ini ada
        $stmtDay = $db->prepare("SELECT visit_date FROM visitor_daily_stats WHERE visit_date = ? LIMIT 1");
        $stmtDay->execute([$today]);
        $dayExists = (bool)$stmtDay->fetchColumn();

        if (!$existingLog) {
            $isLoginInt = ($userId !== null) ? 1 : 0;
            try {
                $db->prepare("
                    INSERT INTO visitor_logs (visit_date, visitor_hash, user_id, last_page, hits, first_seen, last_seen)
                    VALUES (?, ?, ?, ?, 1, ?, ?)
                ")->execute([$today, $visitorHash, $userId, $pagePath, $nowStr, $nowStr]);

                if (!$dayExists) {
                    $db->prepare("
                        INSERT INTO visitor_daily_stats (visit_date, unique_visitors, page_views, logged_in_users)
                        VALUES (?, 1, 1, ?)
                    ")->execute([$today, $isLoginInt]);
                } else {
                    $db->prepare("
                        UPDATE visitor_daily_stats
                        SET unique_visitors = unique_visitors + 1,
                            page_views = page_views + 1,
                            logged_in_users = logged_in_users + ?
                        WHERE visit_date = ?
                    ")->execute([$isLoginInt, $today]);
                }
            } catch (Throwable $e) {
                // Abaikan jika terjadi race condition duplikat antar request bersamaan
            }
        } else {
            $newlyLoggedIn = ($existingLog['user_id'] === null && $userId !== null) ? 1 : 0;
            $db->prepare("
                UPDATE visitor_logs
                SET hits = hits + 1,
                    last_page = ?,
                    last_seen = ?,
                    user_id = COALESCE(user_id, ?)
                WHERE id = ?
            ")->execute([$pagePath, $nowStr, $userId, $existingLog['id']]);

            if (!$dayExists) {
                $db->prepare("
                    INSERT INTO visitor_daily_stats (visit_date, unique_visitors, page_views, logged_in_users)
                    VALUES (?, 1, 1, ?)
                ")->execute([$today, $newlyLoggedIn]);
            } else {
                $db->prepare("
                    UPDATE visitor_daily_stats
                    SET page_views = page_views + 1,
                        logged_in_users = logged_in_users + ?
                    WHERE visit_date = ?
                ")->execute([$newlyLoggedIn, $today]);
            }
        }

        // 2. Catat statistik per halaman
        $stmtPg = $db->prepare("SELECT views FROM visitor_page_stats WHERE visit_date = ? AND page_path = ? LIMIT 1");
        $stmtPg->execute([$today, $pagePath]);
        if ($stmtPg->fetchColumn() !== false) {
            $db->prepare("UPDATE visitor_page_stats SET views = views + 1 WHERE visit_date = ? AND page_path = ?")
               ->execute([$today, $pagePath]);
        } else {
            try {
                $db->prepare("INSERT INTO visitor_page_stats (visit_date, page_path, views) VALUES (?, ?, 1)")
                   ->execute([$today, $pagePath]);
            } catch (Throwable $e) {}
        }
    } catch (Throwable $e) {
        // Fail-safe agar tidak memblokir rendering halaman
    }
}

/**
 * Ringkasan angka statistik pengunjung (Online, Hari Ini, Kemarin, Bulan Ini, Total, Pertumbuhan)
 */
function getVisitorSummaryStats(PDO $db): array {
    ensureVisitorTables($db);

    $tz = new DateTimeZone('Asia/Jakarta');
    $now = new DateTime('now', $tz);
    $today = $now->format('Y-m-d');
    $yesterday = (clone $now)->modify('-1 day')->format('Y-m-d');
    $monthPrefix = $now->format('Y-m') . '%';
    $onlineThreshold = (clone $now)->modify('-5 minutes')->format('Y-m-d H:i:s');

    $summary = [
        'online_now' => 0,
        'today_visitors' => 0,
        'today_views' => 0,
        'today_logged_in' => 0,
        'yesterday_visitors' => 0,
        'yesterday_views' => 0,
        'month_visitors' => 0,
        'month_views' => 0,
        'total_visitors' => 0,
        'total_views' => 0,
        'growth_pct' => 0.0,
    ];

    try {
        // Pengunjung aktif 5 menit terakhir
        $stmtOnline = $db->prepare("SELECT COUNT(*) FROM visitor_logs WHERE last_seen >= ?");
        $stmtOnline->execute([$onlineThreshold]);
        $summary['online_now'] = max(1, (int)$stmtOnline->fetchColumn());

        // Hari ini
        $stmtToday = $db->prepare("SELECT unique_visitors, page_views, logged_in_users FROM visitor_daily_stats WHERE visit_date = ? LIMIT 1");
        $stmtToday->execute([$today]);
        if ($r = $stmtToday->fetch(PDO::FETCH_ASSOC)) {
            $summary['today_visitors'] = (int)$r['unique_visitors'];
            $summary['today_views'] = (int)$r['page_views'];
            $summary['today_logged_in'] = (int)$r['logged_in_users'];
        }

        // Kemarin
        $stmtYest = $db->prepare("SELECT unique_visitors, page_views FROM visitor_daily_stats WHERE visit_date = ? LIMIT 1");
        $stmtYest->execute([$yesterday]);
        if ($r = $stmtYest->fetch(PDO::FETCH_ASSOC)) {
            $summary['yesterday_visitors'] = (int)$r['unique_visitors'];
            $summary['yesterday_views'] = (int)$r['page_views'];
        }

        // Bulan ini
        $stmtMonth = $db->prepare("SELECT COALESCE(SUM(unique_visitors), 0) as uv, COALESCE(SUM(page_views), 0) as pv FROM visitor_daily_stats WHERE visit_date LIKE ?");
        $stmtMonth->execute([$monthPrefix]);
        if ($r = $stmtMonth->fetch(PDO::FETCH_ASSOC)) {
            $summary['month_visitors'] = (int)$r['uv'];
            $summary['month_views'] = (int)$r['pv'];
        }

        // Keseluruhan
        $rAll = $db->query("SELECT COALESCE(SUM(unique_visitors), 0) as uv, COALESCE(SUM(page_views), 0) as pv FROM visitor_daily_stats")->fetch(PDO::FETCH_ASSOC);
        if ($rAll) {
            $summary['total_visitors'] = (int)$rAll['uv'];
            $summary['total_views'] = (int)$rAll['pv'];
        }

        // Hitung persentase pertumbuhan hari ini vs kemarin
        if ($summary['yesterday_visitors'] > 0) {
            $summary['growth_pct'] = round((($summary['today_visitors'] - $summary['yesterday_visitors']) / $summary['yesterday_visitors']) * 100, 1);
        } elseif ($summary['today_visitors'] > 0) {
            $summary['growth_pct'] = 100.0;
        }
    } catch (Throwable $e) {}

    return $summary;
}

/**
 * Ambil deret waktu statistik harian untuk N hari terakhir (mengisi hari kosong dengan 0)
 */
function getDailyVisitorSeries(PDO $db, int $days = 14): array {
    ensureVisitorTables($db);
    $days = max(1, min(90, $days));

    $tz = new DateTimeZone('Asia/Jakarta');
    $end = new DateTime('now', $tz);
    $start = (clone $end)->modify('-' . ($days - 1) . ' days');

    $map = [];
    try {
        $stmt = $db->prepare("
            SELECT visit_date, unique_visitors, page_views, logged_in_users
            FROM visitor_daily_stats
            WHERE visit_date >= ?
            ORDER BY visit_date ASC
        ");
        $stmt->execute([$start->format('Y-m-d')]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[$row['visit_date']] = $row;
        }
    } catch (Throwable $e) {}

    $series = [];
    $prevVisitors = null;
    for ($i = 0; $i < $days; $i++) {
        $dStr = (clone $start)->modify("+$i days")->format('Y-m-d');
        $uv = isset($map[$dStr]) ? (int)$map[$dStr]['unique_visitors'] : 0;
        $pv = isset($map[$dStr]) ? (int)$map[$dStr]['page_views'] : 0;
        $li = isset($map[$dStr]) ? (int)$map[$dStr]['logged_in_users'] : 0;

        $deltaPct = null;
        if ($prevVisitors !== null) {
            if ($prevVisitors > 0) {
                $deltaPct = round((($uv - $prevVisitors) / $prevVisitors) * 100, 1);
            } elseif ($uv > 0) {
                $deltaPct = 100.0;
            } else {
                $deltaPct = 0.0;
            }
        }
        $prevVisitors = $uv;

        $series[] = [
            'date' => $dStr,
            'label' => date('d M', strtotime($dStr)),
            'unique_visitors' => $uv,
            'page_views' => $pv,
            'logged_in_users' => $li,
            'delta_pct' => $deltaPct,
        ];
    }

    return $series;
}

/**
 * Ambil halaman yang paling ramai dikunjungi dalam N hari terakhir
 */
function getTopVisitedPages(PDO $db, int $days = 7, int $limit = 10): array {
    ensureVisitorTables($db);
    $tz = new DateTimeZone('Asia/Jakarta');
    $since = (new DateTime('now', $tz))->modify('-' . max(0, $days - 1) . ' days')->format('Y-m-d');

    try {
        $stmt = $db->prepare("
            SELECT page_path, SUM(views) as total_views
            FROM visitor_page_stats
            WHERE visit_date >= ?
            GROUP BY page_path
            ORDER BY total_views DESC
            LIMIT " . intval($limit)
        );
        $stmt->execute([$since]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Ambil aktivitas pengunjung terbaru hari ini / terakhir
 */
function getRecentVisitorLogs(PDO $db, int $limit = 15): array {
    ensureVisitorTables($db);
    try {
        $stmt = $db->query("
            SELECT vl.*, u.name as user_name, u.email as user_email
            FROM visitor_logs vl
            LEFT JOIN users u ON vl.user_id = u.id
            ORDER BY vl.last_seen DESC
            LIMIT " . intval($limit)
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}
