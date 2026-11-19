<?php
// admin/statistik.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/visitor_tracker.php';

requireAdmin();
$db = getDB();

$allowedDays = [7, 14, 30];
$days = isset($_GET['days']) ? (int)$_GET['days'] : 14;
if (!in_array($days, $allowedDays, true)) {
    $days = 14;
}

$summary = getVisitorSummaryStats($db);
$dailySeries = getDailyVisitorSeries($db, $days);
$topPages = getTopVisitedPages($db, $days, 10);
$recentLogs = getRecentVisitorLogs($db, 15);

// Hitung agregat periode terpilih & nilai maksimum untuk skala grafik
$periodUniqueSum = 0;
$periodViewsSum = 0;
$periodLoggedInSum = 0;
$maxVisitors = 1;
$maxViews = 1;
$peakDay = null;

foreach ($dailySeries as $row) {
    $u = (int)$row['unique_visitors'];
    $v = (int)$row['page_views'];
    $l = (int)$row['logged_in_users'];
    $periodUniqueSum += $u;
    $periodViewsSum += $v;
    $periodLoggedInSum += $l;
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

$avgDailyVisitors = count($dailySeries) > 0 ? round($periodUniqueSum / count($dailySeries), 1) : 0;
$chartScaleMax = max($maxViews, $maxVisitors, 5);

// Bandingkan paruh kedua periode vs paruh pertama periode untuk tren perkembangan
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
$periodTrendPct = 0.0;
if ($firstHalfSum > 0) {
    $periodTrendPct = round((($secondHalfSum - $firstHalfSum) / $firstHalfSum) * 100, 1);
} elseif ($secondHalfSum > 0) {
    $periodTrendPct = 100.0;
}

$page_title = "Statistik Pengunjung & Pertumbuhan";
require_once __DIR__ . '/../includes/header.php';
?>

<style>
.stats-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1.25rem;
    margin-bottom: 1.75rem;
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

.stats-filter-bar {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    align-items: center;
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
    gap: 10px;
    min-height: 230px;
    padding: 1.25rem 0.75rem 0.5rem;
    border-bottom: 2px solid #E2E8F0;
    background: linear-gradient(to top, rgba(248, 250, 252, 0.9), rgba(255, 255, 255, 0));
    border-radius: 10px 10px 0 0;
}
.visitor-bar-col {
    flex: 1 0 38px;
    min-width: 38px;
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
    gap: 4px;
}
.visitor-bar {
    width: 14px;
    border-radius: 5px 5px 0 0;
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
    font-size: 0.72rem;
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
    padding: 0.7rem 0.75rem;
    border-bottom: 1px solid #EEF2F7;
    text-align: left;
    vertical-align: middle;
}
.stats-table th {
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--core-text-muted, #64748b);
    background: #F8FAFC;
    font-weight: 700;
}
.stats-table tbody tr:hover {
    background: #F8FAFC;
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
    .visitor-bar-col { flex: 1 0 32px; min-width: 32px; }
    .visitor-bar { width: 11px; }
}
</style>

<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.75rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 0.3rem; flex-wrap: wrap;">
            <span class="badge badge-navy">ANALYTICS PENGUNJUNG</span>
            <span style="font-size: 0.82rem; color: var(--core-text-muted);">Pemantauan Trafik &amp; Pertumbuhan Harian siaptes.id</span>
        </div>
        <h1 style="font-size: 1.8rem; font-weight: 800; color: var(--core-navy); margin: 0;">Statistik Kunjungan Website</h1>
        <p style="color: var(--core-text-muted); font-size: 0.92rem; margin: 0.35rem 0 0;">Pantau jumlah pengguna yang mengunjungi website per hari, halaman terpopuler, dan tren pertumbuhannya.</p>
    </div>

    <div class="stats-filter-bar">
        <a href="statistik.php?days=7" class="btn btn-sm <?= $days === 7 ? 'btn-primary' : 'btn-outline-navy' ?>">7 Hari</a>
        <a href="statistik.php?days=14" class="btn btn-sm <?= $days === 14 ? 'btn-primary' : 'btn-outline-navy' ?>">14 Hari</a>
        <a href="statistik.php?days=30" class="btn btn-sm <?= $days === 30 ? 'btn-primary' : 'btn-outline-navy' ?>">30 Hari</a>
        <a href="index.php" class="btn btn-secondary btn-sm">← Kembali ke Admin</a>
    </div>
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
            <span>Kunjungan Bulan Ini</span>
            <span class="growth-pill <?= $periodTrendPct > 0 ? 'up' : ($periodTrendPct < 0 ? 'down' : 'flat') ?>">
                Tren <?= $days ?>H: <?= $periodTrendPct > 0 ? '+' : '' ?><?= number_format($periodTrendPct, 1) ?>%
            </span>
        </div>
        <div class="stats-kpi-value"><?= number_format($summary['month_visitors']) ?></div>
        <div class="stats-kpi-sub">
            <span>Total views bulan ini: <b><?= number_format($summary['month_views']) ?></b></span>
        </div>
    </div>

    <div class="stats-kpi-card">
        <div class="stats-kpi-label">
            <span>Total Seluruh Waktu</span>
            <span>Rata2 <?= number_format($avgDailyVisitors, 1) ?>/hr</span>
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
            <h2 class="stats-panel-title">Grafik Perkembangan Kunjungan (<?= $days ?> Hari Terakhir)</h2>
            <p class="stats-panel-subtitle">
                Total <?= number_format($periodUniqueSum) ?> pengunjung harian &amp; <?= number_format($periodViewsSum) ?> page views dalam <?= $days ?> hari terakhir
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
                $tooltip = $point['date'] . ': ' . $u . ' pengunjung unik, ' . $v . ' views, ' . (int)$point['logged_in_users'] . ' user login';
            ?>
            <div class="visitor-bar-col" title="<?= htmlspecialchars($tooltip) ?>">
                <div class="visitor-bar-count"><?= $u ?></div>
                <div class="visitor-bar-pair">
                    <div class="visitor-bar unique" style="height: <?= $uPct ?>%; opacity: <?= $u === 0 ? '0.25' : '1' ?>;"></div>
                    <div class="visitor-bar views" style="height: <?= $vPct ?>%; opacity: <?= $v === 0 ? '0.25' : '1' ?>;"></div>
                </div>
                <div class="visitor-bar-date"><?= htmlspecialchars($point['label']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Rincian Harian & Halaman Populer -->
<div class="stats-two-col">
    <div class="stats-panel" style="margin-bottom: 0;">
        <div class="stats-panel-header">
            <div>
                <h2 class="stats-panel-title">Rincian Perkembangan Per Hari</h2>
                <p class="stats-panel-subtitle">Perbandingan pertumbuhan pengunjung dari hari sebelumnya.</p>
            </div>
        </div>
        <div class="table-responsive-wrap">
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
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
            </table>
        </div>
    </div>

    <div class="stats-panel" style="margin-bottom: 0;">
        <div class="stats-panel-header">
            <div>
                <h2 class="stats-panel-title">Halaman Paling Sering Dikunjungi</h2>
                <p class="stats-panel-subtitle">Top <?= count($topPages) ?> halaman dalam <?= $days ?> hari terakhir.</p>
            </div>
        </div>
        <?php if (empty($topPages)): ?>
            <p style="color: var(--core-text-muted); font-size: 0.9rem;">Belum ada data kunjungan halaman yang tercatat.</p>
        <?php else:
            $topMax = max(1, (int)$topPages[0]['total_views']);
        ?>
        <div class="table-responsive-wrap">
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Halaman</th>
                        <th style="text-align:right;">Total Views</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topPages as $pg):
                        $pv = (int)$pg['total_views'];
                        $barW = (int)round(($pv / $topMax) * 100);
                    ?>
                    <tr>
                        <td>
                            <code style="font-size: 0.82rem; color: var(--core-navy); background: #F1F5F9; padding: 2px 6px; border-radius: 4px;"><?= htmlspecialchars($pg['page_path']) ?></code>
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

<!-- Aktivitas Pengunjung Terbaru -->
<div class="stats-panel">
    <div class="stats-panel-header">
        <div>
            <h2 class="stats-panel-title">Aktivitas Pengunjung Terbaru</h2>
            <p class="stats-panel-subtitle">15 sesi pengunjung terakhir yang membuka halaman publik atau area peserta.</p>
        </div>
    </div>
    <?php if (empty($recentLogs)): ?>
        <p style="color: var(--core-text-muted); font-size: 0.9rem;">Belum ada log pengunjung hari ini.</p>
    <?php else: ?>
    <div class="table-responsive-wrap">
        <table class="stats-table">
            <thead>
                <tr>
                    <th>Terakhir Aktif</th>
                    <th>Pengunjung</th>
                    <th>Halaman Terakhir</th>
                    <th>Total Klik (Hari Ini)</th>
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
