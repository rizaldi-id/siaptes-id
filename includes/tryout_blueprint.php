<?php
// includes/tryout_blueprint.php
// ============================================================================
// BLUEPRINT STANDAR TRYOUT CAT & PENGACAKAN DARI BANK SOAL — SIAP TES
// Menjamin jumlah soal konsisten (CPNS = 110 soal: 30 TWK, 35 TIU, 45 TKP),
// passing grade resmi BKN (65 / 80 / 166), dan soal selalu diacak setiap sesi.
// ============================================================================

/**
 * Ambil konfigurasi blueprint resmi untuk paket tryout
 */
function getTryoutPackageBlueprint(array $package): ?array {
    $pkgId = (int)($package['id'] ?? 0);
    $group = strtolower(trim((string)($package['category_group'] ?? '')));

    if ($pkgId === 1 || ($group === 'cpns' && stripos((string)($package['title'] ?? ''), 'cpns') !== false)) {
        return [
            'total_questions' => 110,
            'duration_minutes' => 100,
            'is_bkn_cpns' => true,
            'passing_label' => 'Passing Grade BKN: TWK ≥ 65, TIU ≥ 80, TKP ≥ 166',
            'min_total_pct' => null,
            'sections' => [
                ['code' => 'TWK', 'count' => 30, 'passing_grade' => 65],
                ['code' => 'TIU', 'count' => 35, 'passing_grade' => 80],
                ['code' => 'TKP', 'count' => 45, 'passing_grade' => 166],
            ],
        ];
    }

    if ($pkgId === 2) {
        return [
            'total_questions' => 90,
            'duration_minutes' => 90,
            'is_bkn_cpns' => false,
            'passing_label' => 'Minimal 60% Skor Total (≥ 270 / 450)',
            'min_total_pct' => 60,
            'sections' => [
                ['code' => 'TKD_BUMN', 'count' => 40, 'passing_grade' => 120],
                ['code' => 'AKHLAK',   'count' => 25, 'passing_grade' => 80],
                ['code' => 'ENG_BUMN', 'count' => 25, 'passing_grade' => 70],
            ],
        ];
    }

    if ($pkgId === 3) {
        return [
            'total_questions' => 70,
            'duration_minutes' => 90,
            'is_bkn_cpns' => false,
            'passing_label' => 'Minimal 60% Skor Total (≥ 210 / 350)',
            'min_total_pct' => 60,
            'sections' => [
                ['code' => 'TWK',           'count' => 15, 'passing_grade' => 45],
                ['code' => 'TIU',           'count' => 15, 'passing_grade' => 45],
                ['code' => 'TKP',           'count' => 15, 'passing_grade' => 55],
                ['code' => 'KEDINASAN_TPA', 'count' => 25, 'passing_grade' => 75],
            ],
        ];
    }

    if ($pkgId === 4) {
        return [
            'total_questions' => 50,
            'duration_minutes' => 60,
            'is_bkn_cpns' => false,
            'passing_label' => 'Minimal 65% Skor Total (≥ 163 / 250)',
            'min_total_pct' => 65,
            'sections' => [
                ['code' => 'PSIKO_LOGIKA',  'count' => 20, 'passing_grade' => 65],
                ['code' => 'PSIKO_SPASIAL', 'count' => 15, 'passing_grade' => 45],
                ['code' => 'PSIKO_SJT',     'count' => 15, 'passing_grade' => 53],
            ],
        ];
    }

    if ($pkgId === 5) {
        return [
            'total_questions' => 30,
            'duration_minutes' => 30,
            'is_bkn_cpns' => false,
            'passing_label' => 'Minimal 60% Skor Total (≥ 90 / 150)',
            'min_total_pct' => 60,
            'sections' => [
                ['code' => 'TWK',          'count' => 5, 'passing_grade' => 15],
                ['code' => 'TIU',          'count' => 5, 'passing_grade' => 15],
                ['code' => 'TKP',          'count' => 5, 'passing_grade' => 18],
                ['code' => 'TKD_BUMN',     'count' => 5, 'passing_grade' => 15],
                ['code' => 'AKHLAK',       'count' => 5, 'passing_grade' => 18],
                ['code' => 'PSIKO_LOGIKA', 'count' => 5, 'passing_grade' => 15],
            ],
        ];
    }

    return null;
}

/**
 * Pengacakan Fisher-Yates dengan CSPRNG random_int() agar urutan soal selalu berbeda di setiap akses
 */
function cryptoShuffleArray(array $items): array {
    $n = count($items);
    for ($i = $n - 1; $i > 0; $i--) {
        $j = random_int(0, $i);
        $tmp = $items[$i];
        $items[$i] = $items[$j];
        $items[$j] = $tmp;
    }
    return $items;
}

/**
 * Ambil daftar ID soal secara ACAK dari seluruh bank soal sesuai blueprint paket.
 * Setiap kali peserta memulai tryout, soal diambil acak baru dari seluruh bank soal per kategori.
 */
function generateRandomizedPackageQuestions(PDO $db, array $package): array {
    $randOrder = function_exists('sqlRandomOrder') ? sqlRandomOrder($db) : 'RANDOM()';
    $blueprint = getTryoutPackageBlueprint($package);
    $selectedIds = [];
    $usedMap = [];

    if ($blueprint !== null && !empty($blueprint['sections'])) {
        foreach ($blueprint['sections'] as $sec) {
            $code = (string)$sec['code'];
            $need = max(1, (int)$sec['count']);

            // Ambil seluruh pool ID bank soal untuk kategori ini, lalu acak dengan random_int
            $stmt = $db->prepare("
                SELECT q.id
                FROM questions q
                JOIN categories c ON q.category_id = c.id
                WHERE c.code = ?
            ");
            $stmt->execute([$code]);
            $pool = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($pool)) {
                continue;
            }

            $pool = cryptoShuffleArray(array_map('intval', $pool));
            $picked = 0;
            foreach ($pool as $qid) {
                if (isset($usedMap[$qid])) {
                    continue;
                }
                $usedMap[$qid] = true;
                $selectedIds[] = $qid;
                $picked++;
                if ($picked >= $need) {
                    break;
                }
            }
        }

        if (!empty($selectedIds)) {
            return $selectedIds;
        }
    }

    // Fallback untuk paket custom buatan Admin: ambil soal milik paket tersebut secara ACAK
    $pkgId = (int)($package['id'] ?? 0);
    if ($pkgId > 0) {
        $stmt = $db->prepare("SELECT id FROM questions WHERE package_id = ? ORDER BY $randOrder");
        $stmt->execute([$pkgId]);
        $pool = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($pool)) {
            return cryptoShuffleArray(array_map('intval', $pool));
        }
    }

    // Fallback terakhir: ambil acak dari grup kategori paket
    $group = strtolower(trim((string)($package['category_group'] ?? 'all')));
    if ($group !== '' && $group !== 'all') {
        $stmt = $db->prepare("
            SELECT q.id
            FROM questions q
            JOIN categories c ON q.category_id = c.id
            WHERE c.category_group = ?
            ORDER BY $randOrder
            LIMIT 50
        ");
        $stmt->execute([$group]);
        $pool = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($pool)) {
            return cryptoShuffleArray(array_map('intval', $pool));
        }
    }

    $pool = $db->query("SELECT id FROM questions ORDER BY $randOrder LIMIT 50")->fetchAll(PDO::FETCH_COLUMN);
    return cryptoShuffleArray(array_map('intval', $pool));
}

/**
 * Ambil statistik jumlah soal bank per kode kategori (untuk menampilkan kapasitas bank soal acak di UI)
 */
function getBankQuestionCountsByCode(PDO $db): array {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $cache = [];
    try {
        $rows = $db->query("
            SELECT c.code, COUNT(q.id) as cnt
            FROM categories c
            LEFT JOIN questions q ON q.category_id = c.id
            GROUP BY c.id, c.code
        ")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $cache[(string)$r['code']] = (int)$r['cnt'];
        }
    } catch (Throwable $e) {}
    return $cache;
}

/**
 * Hitung metadata tampilan kartu paket (jumlah soal efektif, komposisi, dan ukuran bank soal)
 */
function getPackageDisplayMeta(PDO $db, array $package, array $fallbackComposition = []): array {
    $blueprint = getTryoutPackageBlueprint($package);
    $bankCounts = getBankQuestionCountsByCode($db);

    if ($blueprint !== null) {
        $effectiveTotal = 0;
        $poolTotal = 0;
        $labels = [];
        foreach ($blueprint['sections'] as $sec) {
            $code = (string)$sec['code'];
            $avail = $bankCounts[$code] ?? 0;
            $actualCount = min((int)$sec['count'], $avail > 0 ? $avail : (int)$sec['count']);
            $effectiveTotal += $actualCount;
            $poolTotal += $avail;

            if (!empty($blueprint['is_bkn_cpns'])) {
                $labels[] = $code . ' (' . $actualCount . ' Soal • PG ' . (int)$sec['passing_grade'] . ')';
            } else {
                $labels[] = $code . ' (' . $actualCount . ' Soal)';
            }
        }
        return [
            'question_count' => $effectiveTotal,
            'bank_pool_count' => $poolTotal,
            'composition_labels' => $labels,
            'passing_label' => $blueprint['passing_label'],
            'is_bkn_cpns' => !empty($blueprint['is_bkn_cpns']),
        ];
    }

    return [
        'question_count' => (int)($package['question_count'] ?? 0),
        'bank_pool_count' => (int)($package['question_count'] ?? 0),
        'composition_labels' => $fallbackComposition,
        'passing_label' => 'Passing Grade Minimal 60%',
        'is_bkn_cpns' => false,
    ];
}

/**
 * Evaluasi kelulusan konsisten antara exam.php (saat finish) dan hasil.php (saat tampil rincian)
 * Mengembalikan:
 * - is_passed (bool)
 * - is_bkn_cpns (bool)
 * - category_targets (map code => ['target' => int, 'passed' => bool, 'label' => string])
 */
function evaluateTryoutPassing(array $attempt, array $catScoresMap, array $catMaxMap, int $totalScore): array {
    $mode = (string)($attempt['mode'] ?? 'tryout');
    $pkgId = (int)($attempt['package_id'] ?? 0);
    $blueprint = ($mode === 'tryout')
        ? getTryoutPackageBlueprint([
            'id' => $pkgId,
            'title' => $attempt['package_title'] ?? '',
            'category_group' => $attempt['category_group'] ?? '',
        ])
        : null;

    $isBknCpns = ($blueprint !== null && !empty($blueprint['is_bkn_cpns']));
    $categoryTargets = [];

    if ($isBknCpns) {
        $bknThresholds = ['TWK' => 65, 'TIU' => 80, 'TKP' => 166];
        $allPassed = true;
        foreach ($bknThresholds as $code => $pg) {
            $sc = (int)($catScoresMap[$code] ?? 0);
            $ok = ($sc >= $pg);
            if (!$ok) {
                $allPassed = false;
            }
            $categoryTargets[$code] = [
                'target' => $pg,
                'passed' => $ok,
                'label' => 'Ambang Batas BKN: ' . $pg,
            ];
        }
        return [
            'is_passed' => $allPassed,
            'is_bkn_cpns' => true,
            'category_targets' => $categoryTargets,
            'passing_label' => 'Standar BKN: TWK ≥ 65, TIU ≥ 80, TKP ≥ 166',
        ];
    }

    // Paket non-CPNS atau Latihan Mandiri
    $totalMax = array_sum($catMaxMap);
    $minPct = ($blueprint !== null && !empty($blueprint['min_total_pct'])) ? (int)$blueprint['min_total_pct'] : 60;
    $minScore = (int)ceil($totalMax * ($minPct / 100));

    $sectionPgMap = [];
    if ($blueprint !== null) {
        foreach ($blueprint['sections'] as $sec) {
            $sectionPgMap[$sec['code']] = (int)$sec['passing_grade'];
        }
    }

    foreach ($catScoresMap as $code => $sc) {
        $cMax = (int)($catMaxMap[$code] ?? 0);
        $target = isset($sectionPgMap[$code]) ? $sectionPgMap[$code] : (int)ceil($cMax * 0.6);
        $categoryTargets[$code] = [
            'target' => $target,
            'passed' => ($sc >= $target),
            'label' => 'Target Minimal: ' . $target,
        ];
    }

    return [
        'is_passed' => ($totalMax > 0 && $totalScore >= $minScore),
        'is_bkn_cpns' => false,
        'category_targets' => $categoryTargets,
        'passing_label' => 'Minimal ' . $minPct . '% Skor Total (≥ ' . $minScore . ' / ' . $totalMax . ')',
    ];
}
