<?php
// admin/statistik.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/visitor_tracker.php';

requireAdmin();
$db = getDB();

$tz = new DateTimeZone('Asia/Jakarta');
$nowObj = new DateTime('now', $tz);
$todayYmd = $nowObj->format('Y-m-d');
$currentYm = $nowObj->format('Y-m');

$availableMonths = getAvailableVisitorMonths($db);

// Tentukan mode filter: 'days' (7/14/30), 'month' (YYYY-MM), atau 'range' (start..end)
$mode = $_GET['mode'] ?? '';
if (!in_array($mode, ['days', 'month', 'range'], true)) {
    if (!empty($_GET['month'])) {
        $mode = 'month';
    } elseif (!empty($_GET['start']) || !empty($_GET['end'])) {
        $mode = 'range';
    } else {
        $mode = 'days';
    }
}

$days = isset($_GET['days']) ? (int)$_GET['days'] : 14;
if (!in_array($days, [7, 14, 30], true)) {
    $days = 14;
}

$selectedMonth = isset($_GET['month']) && preg_match('/^\d{4}-\d{2}$/', (string)$_GET['month'])
    ? (string)$_GET['month']
    : $currentYm;

$startInput = isset($_GET['start']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$_GET['start'])
    ? (string)$_GET['start']
    : (clone $nowObj)->modify('-13 days')->format('Y-m-d');

$endInput = isset($_GET['end']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$_GET['end'])
    ? (string)$_GET['end']
    : $todayYmd;

// Hitung $startDate, $endDate, dan label periode aktif
if ($mode === 'month') {
    try {
        $mStart = new DateTime($selectedMonth . '-01', $tz);
    } catch (Throwable $e) {
        $selectedMonth = $currentYm;
        $mStart = new DateTime($selectedMonth . '-01', $tz);
    }
    $mEnd = (clone $mStart)->modify('last day of this month');
    $startDate = $mStart->format('Y-m-d');
    $endDate = $mEnd->format('Y-m-d');
    $periodTitle = 'Bulan ' . formatIndonesianMonthLabel($selectedMonth);
    $prevMonthYm = (clone $mStart)->modify('-1 month')->format('Y-m');
    $nextMonthYm = (clone $mStart)->modify('+1 month')->format('Y-m');
} elseif ($mode === 'range') {
    $startDate = $startInput;
    $endDate = $endInput;
    if ($startDate > $endDate) {
        $tmp = $startDate;
        $startDate = $endDate;
        $endDate = $tmp;
    }
    $periodTitle = date('d M Y', strtotime($startDate)) . ' – ' . date('d M Y', strtotime($endDate));
} else {
    $endDate = $todayYmd;
    $startDate = (clone $nowObj)->modify('-' . ($days - 1) . ' days')->format('Y-m-d');
    $periodTitle = $days . ' Hari Terakhir';
}

$summary = getVisitorSummaryStats($db);
$dailySeries = getDailyVisitorSeriesByRange($db, $startDate, $endDate);
$monthlySummary = getMonthlyVisitorSummary($db, 24);
$topPages = getTopVisitedPagesByRange($db, $startDate, $endDate, 12);
$topArticles = getTopVisitedArticlesByRange($db, $startDate, $endDate, 10);
$recentLogs = getVisitorLogsByRange($db, $startDate, $endDate, 25);

// Jika admin klik Export CSV, kirim file CSV sebelum output HTML apa pun
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $mode === 'month' ? $selectedMonth : ($startDate . '_sd_' . $endDate));
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="statistik-kunjungan-' . $safeName . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Tanggal', 'Hari', 'Pengunjung Unik', 'Pertumbuhan Harian (%)', 'Total Views', 'User Login']);
    foreach ($dailySeries as $r) {
        fputcsv($out, [
            $r['date'],
            $r['day_name'],
            (int)$r['unique_visitors'],
            $r['delta_pct'] === null ? '-' : $r['delta_pct'],
            (int)$r['page_views'],
            (int)$r['logged_in_users'],
        ]);
    }
    fclose($out);
    exit;
}

// Hitung agregat periode terpilih & nilai maksimum untuk skala grafik
$periodUniqueSum = 0;
$periodViewsSum = 0;
$periodLoggedInSum = 0;
$maxVisitors = 1;
$maxViews = 1;
$peakDay = null;
$activeDaysCount = 0;

foreach ($dailySeries as $row) {
    $u = (int)$row['unique_visitors'];
    $v = (int)$row['page_views'];
    $l = (int)$row['logged_in_users'];
    $periodUniqueSum += $u;
    $periodViewsSum += $v;
    $periodLoggedInSum += $l;
    if ($u > 0 || $v > 0) {
        $activeDaysCount++;
    }
    if ($u > $maxVisitors) {
        $maxVisitors = $u;
    }
    if ($v > $maxViews) {
        $maxViews = $v;
    }
    if ($peakDay === null || $u > (int)$peakDay['unique_visitors']) {
        $peakDay = $row;
    }
}

$totalDaysInPeriod = max(1, count($dailySeries));
$avgDailyVisitors = round($periodUniqueSum / $totalDaysInPeriod, 1);
$chartScaleMax = max($maxViews, $maxVisitors, 5);

// Tren pertumbuhan (untuk mode bulan: bandingkan vs bulan sebelumnya jika tersedia; selainnya paruh kedua vs pertama)
$periodTrendLabel = 'Tren Periode';
$periodTrendPct = 0.0;
if ($mode === 'month') {
    $periodTrendLabel = 'vs Bln Lalu';
    $curMonthUv = $periodUniqueSum;
    $prevMonthUv = null;
    foreach ($monthlySummary as $ms) {
        if ($ms['month'] === ($prevMonthYm ?? '')) {
            $prevMonthUv = (int)$ms['unique_visitors'];
            break;
        }
    }
    if ($prevMonthUv !== null && $prevMonthUv > 0) {
        $periodTrendPct = round((($curMonthUv - $prevMonthUv) / $prevMonthUv) * 100, 1);
    } elseif ($curMonthUv > 0) {
        $periodTrendPct = 100.0;
    }
} else {
    $half = (int)floor(count($dailySeries) / 2);
    $firstHalfSum = 0;
    $secondHalfSum = 0;
    for ($i = 0; $i < count($dailySeries); $i++) {
        if ($i < $half) {
            $firstHalfSum += (int)$dailySeries[$i]['unique_visitors'];
        } else {
            $secondHalfSum += (int)$dailySeries[$i]['unique_visitors'];
        }
    }
    if ($firstHalfSum > 0) {
        $periodTrendPct = round((($secondHalfSum - $firstHalfSum) / $firstHalfSum) * 100, 1);
    } elseif ($secondHalfSum > 0) {
        $periodTrendPct = 100.0;
    }
}

$exportQuery = http_build_query([
    'mode' => $mode,
    'days' => $days,
    'month' => $selectedMonth,
    'start' => $startDate,
    'end' => $endDate,
    'export' => 'csv',
]);

$page_title = "Statistik Pengunjung & Pertumbuhan";
require_once __DIR__ . '/../includes/header.php';
?>

<style>
.stats-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1.25rem;
    margin-bottom: 1.5rem;
}
.stats-kpi-card {
    background: #fff;
    border: 1px solid var(--core-border, #e2e8f0);
    border-radius: 14px;
    padding: 1.25rem;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
    position: relative;
    overflow: hidden;
}
.stats-kpi-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
    background: var(--core-navy, #0B2545);
}
.stats-kpi-card.gold::before { background: var(--core-gold, #D4AF37); }
.stats-kpi-card.emerald::before { background: #10B981; }
.stats-kpi-card.sky::before { background: #0284C7; }

.stats-kpi-label {
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    color: var(--core-text-muted, #64748b);
    text-transform: uppercase;
    margin-bottom: 0.45rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}
.stats-kpi-value {
    font-size: 2rem;
    font-weight: 800;
    color: var(--core-navy, #0B2545);
    line-height: 1.15;
}
.stats-kpi-sub {
    margin-top: 0.5rem;
    font-size: 0.82rem;
    color: var(--core-text-muted, #64748b);
    display: flex;
    align-items: center;
    gap: 0.4rem;
    flex-wrap: wrap;
}
.growth-pill {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-size: 0.74rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 999px;
}
.growth-pill.up { background: #DCFCE7; color: #166534; }
.growth-pill.down { background: #FEE2E2; color: #991B1B; }
.growth-pill.flat { background: #F1F5F9; color: #475569; }

.stats-filter-card {
    background: #fff;
    border: 1px solid var(--core-border, #e2e8f0);
    border-radius: 14px;
    padding: 1rem 1.25rem;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
    margin-bottom: 1.5rem;
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: flex-end;
    justify-content: space-between;
}
.stats-filter-group {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
}
.stats-filter-form {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: flex-end;
}
.stats-input {
    border: 1px solid #CBD5E1;
    border-radius: 8px;
    padding: 0.42rem 0.65rem;
    font-size: 0.85rem;
    color: var(--core-navy, #0B2545);
    background: #F8FAFC;
    font-weight: 600;
}
.stats-field-label {
    display: block;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--core-text-muted, #64748b);
    margin-bottom: 0.25rem;
}

.stats-panel {
    background: #fff;
    border: 1px solid var(--core-border, #e2e8f0);
    border-radius: 14px;
    padding: 1.35rem;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
    margin-bottom: 1.5rem;
}
.stats-panel-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1rem;
}
.stats-panel-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--core-navy, #0B2545);
    margin: 0;
}
.stats-panel-subtitle {
    font-size: 0.85rem;
    color: var(--core-text-muted, #64748b);
    margin: 0.2rem 0 0;
}

.chart-legend {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    font-size: 0.8rem;
    color: var(--core-text-muted, #475569);
    align-items: center;
}
.chart-legend-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
}
.legend-swatch {
    width: 12px;
    height: 12px;
    border-radius: 3px;
    display: inline-block;
}
.legend-swatch.unique { background: #0B2545; }
.legend-swatch.views { background: #D4AF37; }

.visitor-chart-scroll {
    overflow-x: auto;
    padding-bottom: 0.5rem;
}
.visitor-chart-grid {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    min-height: 230px;
    padding: 1.25rem 0.75rem 0.5rem;
    border-bottom: 2px solid #E2E8F0;
    background: linear-gradient(to top, rgba(248, 250, 252, 0.9), rgba(255, 255, 255, 0));
    border-radius: 10px 10px 0 0;
}
.visitor-bar-col {
    flex: 1 0 32px;
    min-width: 32px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
}
.visitor-bar-pair {
    width: 100%;
    height: 170px;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    gap: 3px;
}
.visitor-bar {
    width: 12px;
    border-radius: 4px 4px 0 0;
    position: relative;
    transition: opacity 0.2s ease, transform 0.2s ease;
    min-height: 3px;
}
.visitor-bar.unique { background: linear-gradient(180deg, #1E3A8A, #0B2545); }
.visitor-bar.views { background: linear-gradient(180deg, #FBBF24, #D4AF37); }
.visitor-bar:hover { opacity: 0.85; transform: translateY(-2px); }
.visitor-bar-count {
    font-size: 0.7rem;
    font-weight: 700;
    color: var(--core-navy, #0B2545);
    line-height: 1;
}
.visitor-bar-date {
    font-size: 0.7rem;
    color: var(--core-text-muted, #64748b);
    white-space: nowrap;
}

.stats-two-col {
    display: grid;
    grid-template-columns: 1.35fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}
.table-responsive-wrap {
    width: 100%;
    overflow-x: auto;
}
.stats-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}
.stats-table th,
.stats-table td {
    padding: 0.68rem 0.75rem;
    border-bottom: 1px solid #EEF2F7;
    text-align: left;
    vertical-align: middle;
}
.stats-table th {
    font-size: 0.74rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--core-text-muted, #64748b);
    background: #F8FAFC;
    font-weight: 700;
}
.stats-table tbody tr:hover {
    background: #F8FAFC;
}
.stats-table tfoot td {
    background: #F1F5F9;
    font-weight: 800;
    color: var(--core-navy, #0B2545);
    border-top: 2px solid #CBD5E1;
}
.page-progress-track {
    width: 100%;
    height: 7px;
    background: #E2E8F0;
    border-radius: 999px;
    overflow: hidden;
    margin-top: 4px;
}
.page-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #0B2545, #2563EB);
    border-radius: 999px;
}

@media (max-width: 1024px) {
    .stats-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .stats-two-col { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
    .stats-kpi-grid { grid-template-columns: 1fr; }
    .visitor-bar-col { flex: 1 0 26px; min-width: 26px; }
    .visitor-bar { width: 10px; }
}
</style>

<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 0.3rem; flex-wrap: wrap;">
            <span class="badge badge-navy">ANALYTICS PENGUNJUNG</span>
            <span style="font-size: 0.82rem; color: var(--core-text-muted);">Laporan Lengkap Harian, Bulanan &amp; Artikel siaptes.id</span>
        </div>
        <h1 style="font-size: 1.8rem; font-weight: 800; color: var(--core-navy); margin: 0;">Statistik Kunjungan Website</h1>
        <p style="color: var(--core-text-muted); font-size: 0.92rem; margin: 0.35rem 0 0;">
            Periode aktif: <b><?= htmlspecialchars($periodTitle) ?></b> (<?= htmlspecialchars($startDate) ?> s.d. <?= htmlspecialchars($endDate) ?>)
        </p>
    </div>

    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <a href="statistik.php?<?= htmlspecialchars($exportQuery) ?>" class="btn btn-gold btn-sm">📥 Export CSV</a>
        <a href="index.php" class="btn btn-secondary btn-sm">← Kembali ke Admin</a>
    </div>
</div>

<!-- Bar Filter Lengkap: Preset Hari, Filter Per Bulan, & Rentang Tanggal -->
<div class="stats-filter-card">
    <div>
        <span class="stats-field-label">Preset Cepat</span>
        <div class="stats-filter-group">
            <a href="statistik.php?mode=days&amp;days=7" class="btn btn-sm <?= ($mode === 'days' && $days === 7) ? 'btn-primary' : 'btn-outline-navy' ?>">7 Hari</a>
            <a href="statistik.php?mode=days&amp;days=14" class="btn btn-sm <?= ($mode === 'days' && $days === 14) ? 'btn-primary' : 'btn-outline-navy' ?>">14 Hari</a>
            <a href="statistik.php?mode=days&amp;days=30" class="btn btn-sm <?= ($mode === 'days' && $days === 30) ? 'btn-primary' : 'btn-outline-navy' ?>">30 Hari</a>
            <a href="statistik.php?mode=month&amp;month=<?= urlencode($currentYm) ?>" class="btn btn-sm <?= ($mode === 'month' && $selectedMonth === $currentYm) ? 'btn-primary' : 'btn-outline-navy' ?>">Bulan Ini</a>
        </div>
    </div>

    <form method="GET" action="statistik.php" class="stats-filter-form">
        <input type="hidden" name="mode" value="month">
        <div>
            <label for="filterMonthSelect" class="stats-field-label">Filter Per Bulan</label>
            <div style="display: flex; gap: 0.35rem; align-items: center;">
                <?php if ($mode === 'month'): ?>
                    <a href="statistik.php?mode=month&amp;month=<?= urlencode($prevMonthYm) ?>" class="btn btn-secondary btn-sm" title="Bulan Sebelumnya">◀</a>
                <?php endif; ?>
                <select id="filterMonthSelect" name="month" class="stats-input" onchange="this.form.submit();">
                    <?php foreach ($availableMonths as $ymVal => $ymLabel): ?>
                        <option value="<?= htmlspecialchars($ymVal) ?>" <?= $selectedMonth === $ymVal ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ymLabel) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($mode === 'month'): ?>
                    <a href="statistik.php?mode=month&amp;month=<?= urlencode($nextMonthYm) ?>" class="btn btn-secondary btn-sm" title="Bulan Berikutnya">▶</a>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
            </div>
        </div>
    </form>

    <form method="GET" action="statistik.php" class="stats-filter-form">
        <input type="hidden" name="mode" value="range">
        <div>
            <label for="filterStartDate" class="stats-field-label">Dari Tanggal</label>
            <input type="date" id="filterStartDate" name="start" value="<?= htmlspecialchars($startDate) ?>" class="stats-input">
        </div>
        <div>
            <label for="filterEndDate" class="stats-field-label">Sampai Tanggal</label>
            <input type="date" id="filterEndDate" name="end" value="<?= htmlspecialchars($endDate) ?>" class="stats-input">
        </div>
        <button type="submit" class="btn btn-outline-navy btn-sm">Terapkan</button>
    </form>
</div>

<!-- KPI Summary Cards -->
<div class="stats-kpi-grid">
    <div class="stats-kpi-card emerald">
        <div class="stats-kpi-label">
            <span>Pengunjung Hari Ini</span>
            <span class="growth-pill <?= $summary['growth_pct'] > 0 ? 'up' : ($summary['growth_pct'] < 0 ? 'down' : 'flat') ?>">
                <?= $summary['growth_pct'] > 0 ? '▲ +' : ($summary['growth_pct'] < 0 ? '▼ ' : '• ') ?><?= number_format($summary['growth_pct'], 1) ?>%
            </span>
        </div>
        <div class="stats-kpi-value"><?= number_format($summary['today_visitors']) ?></div>
        <div class="stats-kpi-sub">
            <span>Kemarin: <b><?= number_format($summary['yesterday_visitors']) ?></b></span>
            <span>•</span>
            <span><?= number_format($summary['today_views']) ?> views</span>
        </div>
    </div>

    <div class="stats-kpi-card sky">
        <div class="stats-kpi-label">
            <span>Aktif Online (15 Menit)</span>
            <span style="display:inline-flex;align-items:center;gap:5px;color:#059669;font-size:0.74rem;">
                <span style="width:8px;height:8px;border-radius:50%;background:#10B981;display:inline-block;"></span> LIVE
            </span>
        </div>
        <div class="stats-kpi-value"><?= number_format($summary['online_now']) ?></div>
        <div class="stats-kpi-sub">
            <span>Login hari ini: <b><?= number_format($summary['today_logged_in']) ?></b> akun</span>
        </div>
    </div>

    <div class="stats-kpi-card gold">
        <div class="stats-kpi-label">
            <span><?= htmlspecialchars($periodTitle) ?></span>
            <span class="growth-pill <?= $periodTrendPct > 0 ? 'up' : ($periodTrendPct < 0 ? 'down' : 'flat') ?>">
                <?= htmlspecialchars($periodTrendLabel) ?>: <?= $periodTrendPct > 0 ? '+' : '' ?><?= number_format($periodTrendPct, 1) ?>%
            </span>
        </div>
        <div class="stats-kpi-value"><?= number_format($periodUniqueSum) ?></div>
        <div class="stats-kpi-sub">
            <span><?= number_format($periodViewsSum) ?> views</span>
            <span>•</span>
            <span>Rata2 <b><?= number_format($avgDailyVisitors, 1) ?></b>/hr</span>
        </div>
    </div>

    <div class="stats-kpi-card">
        <div class="stats-kpi-label">
            <span>Total Seluruh Waktu</span>
            <span>All-Time</span>
        </div>
        <div class="stats-kpi-value"><?= number_format($summary['total_visitors']) ?></div>
        <div class="stats-kpi-sub">
            <span>Total halaman dibuka: <b><?= number_format($summary['total_views']) ?></b></span>
        </div>
    </div>
</div>

<!-- Chart Perkembangan Harian -->
<div class="stats-panel">
    <div class="stats-panel-header">
        <div>
            <h2 class="stats-panel-title">Grafik Perkembangan Kunjungan — <?= htmlspecialchars($periodTitle) ?></h2>
            <p class="stats-panel-subtitle">
                Total <?= number_format($periodUniqueSum) ?> pengunjung harian &amp; <?= number_format($periodViewsSum) ?> page views (<?= $activeDaysCount ?> hari aktif dari <?= $totalDaysInPeriod ?> hari)
                <?php if ($peakDay !== null): ?>
                    • Puncak: <b><?= htmlspecialchars($peakDay['label']) ?></b> (<?= number_format((int)$peakDay['unique_visitors']) ?> pengunjung)
                <?php endif; ?>
            </p>
        </div>
        <div class="chart-legend">
            <span class="chart-legend-item"><span class="legend-swatch unique"></span> Pengunjung Unik Harian</span>
            <span class="chart-legend-item"><span class="legend-swatch views"></span> Total Halaman Dibuka (Views)</span>
        </div>
    </div>

    <div class="visitor-chart-scroll">
        <div class="visitor-chart-grid">
            <?php foreach ($dailySeries as $point):
                $u = (int)$point['unique_visitors'];
                $v = (int)$point['page_views'];
                $uPct = $chartScaleMax > 0 ? max(2, (int)round(($u / $chartScaleMax) * 100)) : 2;
                $vPct = $chartScaleMax > 0 ? max(2, (int)round(($v / $chartScaleMax) * 100)) : 2;
                if ($u === 0) { $uPct = 2; }
                if ($v === 0) { $vPct = 2; }
                $tooltip = $point['date'] . ' (' . $point['day_name'] . '): ' . $u . ' pengunjung unik, ' . $v . ' views, ' . (int)$point['logged_in_users'] . ' user login';
                $barLabel = ($mode === 'month') ? $point['day_num'] : $point['label'];
            ?>
            <div class="visitor-bar-col" title="<?= htmlspecialchars($tooltip) ?>">
                <div class="visitor-bar-count"><?= $u ?></div>
                <div class="visitor-bar-pair">
                    <div class="visitor-bar unique" style="height: <?= $uPct ?>%; opacity: <?= $u === 0 ? '0.25' : '1' ?>;"></div>
                    <div class="visitor-bar views" style="height: <?= $vPct ?>%; opacity: <?= $v === 0 ? '0.25' : '1' ?>;"></div>
                </div>
                <div class="visitor-bar-date"><?= htmlspecialchars($barLabel) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Rincian Harian & Rekap Per Bulan -->
<div class="stats-two-col">
    <div class="stats-panel" style="margin-bottom: 0;">
        <div class="stats-panel-header">
            <div>
                <h2 class="stats-panel-title">Rincian Lengkap Per Hari (<?= htmlspecialchars($periodTitle) ?>)</h2>
                <p class="stats-panel-subtitle">Menampilkan seluruh tanggal pada periode terpilih beserta persentase pertumbuhan harian.</p>
            </div>
        </div>
        <div class="table-responsive-wrap" style="max-height: 520px; overflow-y: auto;">
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Hari</th>
                        <th>Pengunjung Unik</th>
                        <th>Pertumbuhan</th>
                        <th>Views</th>
                        <th>User Login</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_reverse($dailySeries) as $row):
                        $delta = $row['delta_pct'];
                    ?>
                    <tr>
                        <td><b><?= htmlspecialchars($row['date']) ?></b></td>
                        <td style="color: var(--core-text-muted);"><?= htmlspecialchars($row['day_name']) ?></td>
                        <td><b><?= number_format((int)$row['unique_visitors']) ?></b></td>
                        <td>
                            <?php if ($delta === null): ?>
                                <span class="growth-pill flat">-</span>
                            <?php elseif ($delta > 0): ?>
                                <span class="growth-pill up">▲ +<?= number_format($delta, 1) ?>%</span>
                            <?php elseif ($delta < 0): ?>
                                <span class="growth-pill down">▼ <?= number_format($delta, 1) ?>%</span>
                            <?php else: ?>
                                <span class="growth-pill flat">0.0%</span>
                            <?php endif; ?>
                        </td>
                        <td><?= number_format((int)$row['page_views']) ?></td>
                        <td><?= number_format((int)$row['logged_in_users']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2">TOTAL PERIODE (<?= $totalDaysInPeriod ?> HARI)</td>
                        <td><?= number_format($periodUniqueSum) ?></td>
                        <td>Rata2 <?= number_format($avgDailyVisitors, 1) ?>/hr</td>
                        <td><?= number_format($periodViewsSum) ?></td>
                        <td><?= number_format($periodLoggedInSum) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Rekap Per Bulan -->
    <div class="stats-panel" style="margin-bottom: 0;">
        <div class="stats-panel-header">
            <div>
                <h2 class="stats-panel-title">Rekap Kunjungan Per Bulan</h2>
                <p class="stats-panel-subtitle">Klik nama bulan untuk langsung memfilter grafik &amp; rincian harian bulan tersebut.</p>
            </div>
        </div>
        <div class="table-responsive-wrap">
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Bulan</th>
                        <th>Pengunjung</th>
                        <th>Tren MoM</th>
                        <th>Views</th>
                        <th>Rata2/Hr</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($monthlySummary as $ms):
                        $isCurrentSelected = ($mode === 'month' && $selectedMonth === $ms['month']);
                        $mDelta = $ms['delta_pct'];
                    ?>
                    <tr style="<?= $isCurrentSelected ? 'background:#EFF6FF;' : '' ?>">
                        <td>
                            <a href="statistik.php?mode=month&amp;month=<?= urlencode($ms['month']) ?>" style="font-weight: 700; color: var(--core-navy); text-decoration: underline;">
                                <?= htmlspecialchars($ms['label']) ?>
                            </a>
                        </td>
                        <td><b><?= number_format((int)$ms['unique_visitors']) ?></b></td>
                        <td>
                            <?php if ($mDelta === null): ?>
                                <span class="growth-pill flat">-</span>
                            <?php elseif ($mDelta > 0): ?>
                                <span class="growth-pill up">▲ +<?= number_format($mDelta, 1) ?>%</span>
                            <?php elseif ($mDelta < 0): ?>
                                <span class="growth-pill down">▼ <?= number_format($mDelta, 1) ?>%</span>
                            <?php else: ?>
                                <span class="growth-pill flat">0.0%</span>
                            <?php endif; ?>
                        </td>
                        <td><?= number_format((int)$ms['page_views']) ?></td>
                        <td><?= number_format((float)$ms['avg_daily'], 1) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Statistik Artikel Dilihat & Halaman Populer -->
<div class="stats-two-col">
    <div class="stats-panel" style="margin-bottom: 0;">
        <div class="stats-panel-header">
            <div>
                <h2 class="stats-panel-title">Statistik Artikel Dibaca (<?= htmlspecialchars($periodTitle) ?>)</h2>
                <p class="stats-panel-subtitle">Berdasarkan kunjungan pembaca nyata (bot/crawler disaring). Bandingkan tayangan periode filter vs total kumulatif artikel di <a href="artikel.php" style="color:var(--core-navy);font-weight:700;">Kelola Artikel</a>.</p>
            </div>
        </div>
        <?php if (empty($topArticles)): ?>
            <p style="color: var(--core-text-muted); font-size: 0.9rem;">Belum ada kunjungan ke halaman baca artikel (`baca-artikel.php`) pada periode <?= htmlspecialchars($periodTitle) ?>.</p>
        <?php else: ?>
        <div class="table-responsive-wrap">
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Judul Artikel</th>
                        <th style="text-align:right;">Periode Ini</th>
                        <th style="text-align:right;">Total di Tabel Artikel</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topArticles as $art): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 700; color: var(--core-navy);"><?= htmlspecialchars($art['title']) ?></div>
                            <span style="font-size: 0.74rem; color: var(--core-text-muted);">
                                <?= htmlspecialchars($art['category']) ?> &bull;
                                <a href="../baca-artikel.php?slug=<?= urlencode($art['slug']) ?>" target="_blank" style="color: #0284c7;">Buka Artikel ↗</a>
                            </span>
                        </td>
                        <td style="text-align:right; font-weight: 800; color: #059669;"><?= number_format((int)$art['period_views']) ?></td>
                        <td style="text-align:right; font-weight: 700; color: var(--core-navy);">
                            <?= $art['total_article_views'] !== null ? number_format((int)$art['total_article_views']) : '-' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="stats-panel" style="margin-bottom: 0;">
        <div class="stats-panel-header">
            <div>
                <h2 class="stats-panel-title">Halaman Paling Sering Dikunjungi</h2>
                <p class="stats-panel-subtitle">Top <?= count($topPages) ?> halaman pada periode <?= htmlspecialchars($periodTitle) ?>.</p>
            </div>
        </div>
        <?php if (empty($topPages)): ?>
            <p style="color: var(--core-text-muted); font-size: 0.9rem;">Belum ada data kunjungan halaman pada periode ini.</p>
        <?php else:
            $topMax = max(1, (int)$topPages[0]['total_views']);
        ?>
        <div class="table-responsive-wrap">
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Halaman</th>
                        <th style="text-align:right;">Views</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topPages as $pg):
                        $pv = (int)$pg['total_views'];
                        $barW = (int)round(($pv / $topMax) * 100);
                    ?>
                    <tr>
                        <td>
                            <code style="font-size: 0.8rem; color: var(--core-navy); background: #F1F5F9; padding: 2px 6px; border-radius: 4px; word-break: break-all;"><?= htmlspecialchars($pg['page_path']) ?></code>
                            <div class="page-progress-track">
                                <div class="page-progress-fill" style="width: <?= $barW ?>%;"></div>
                            </div>
                        </td>
                        <td style="text-align:right; font-weight: 700; color: var(--core-navy);"><?= number_format($pv) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Aktivitas Pengunjung Pada Periode Filter -->
<div class="stats-panel">
    <div class="stats-panel-header">
        <div>
            <h2 class="stats-panel-title">Aktivitas Pengunjung Terbaru (<?= htmlspecialchars($periodTitle) ?>)</h2>
            <p class="stats-panel-subtitle">Maksimal 25 sesi pengunjung terakhir pada periode terpilih.</p>
        </div>
    </div>
    <?php if (empty($recentLogs)): ?>
        <p style="color: var(--core-text-muted); font-size: 0.9rem;">Belum ada log pengunjung pada periode <?= htmlspecialchars($periodTitle) ?>.</p>
    <?php else: ?>
    <div class="table-responsive-wrap">
        <table class="stats-table">
            <thead>
                <tr>
                    <th>Terakhir Aktif</th>
                    <th>Pengunjung</th>
                    <th>Halaman Terakhir</th>
                    <th>Total Klik (Hari Itu)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentLogs as $log): ?>
                <tr>
                    <td style="white-space: nowrap; font-size: 0.82rem; color: var(--core-text-muted);"><?= htmlspecialchars($log['last_seen']) ?></td>
                    <td>
                        <?php if (!empty($log['user_name'])): ?>
                            <b style="color: var(--core-navy);"><?= htmlspecialchars($log['user_name']) ?></b>
                            <span style="font-size: 0.78rem; color: var(--core-text-muted); display: block;"><?= htmlspecialchars($log['user_email'] ?? '') ?></span>
                        <?php else: ?>
                            <span class="badge" style="background:#F1F5F9;color:#475569;">Tamu #<?= htmlspecialchars(substr((string)$log['visitor_hash'], 0, 8)) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><code style="font-size: 0.82rem;"><?= htmlspecialchars($log['last_page']) ?></code></td>
                    <td><b><?= number_format((int)$log['hits']) ?></b>x</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
