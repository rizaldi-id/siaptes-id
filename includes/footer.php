<?php
// includes/footer.php
?>
</div> <!-- End .container -->

<footer>
    <div class="footer-container">
        <div class="footer-top">
            <div class="footer-brand">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 0.75rem;">
                    <div style="background: linear-gradient(135deg, #3559a3 0%, #4e75c4 100%); color: #ffffff; width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-weight: 800;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                        </svg>
                    </div>
                    <h3 style="margin: 0; color: #f8fafc;">SIAP<span>TES</span>.ID</h3>
                </div>
                <p style="color: #94a3b8; line-height: 1.65; font-size: 0.85rem; max-width: 480px;">
                    Platform persiapan dan simulasi Computer Assisted Test (CAT) terpadu di Indonesia.
                    Satu portal nyaman untuk latihan soal dan tryout seleksi <b>CPNS / PPPK</b>, <b>Rekrutmen Bersama BUMN</b>, <b>Sekolah Kedinasan</b>, serta <b>Psikotes Kerja & Asesmen Perusahaan</b>.
                </p>
                <div style="margin-top: 1rem; display: flex; gap: 0.75rem; align-items: center; font-size: 0.78rem; color: #cbd5e1; flex-wrap: wrap;">
                    <span>🏛️ Standar BKN & MenPAN-RB</span>
                    <span>🏢 Standar FHCI BUMN</span>
                    <span>🎓 Jalur Kedinasan</span>
                    <span>🧠 Psikotes Kerja</span>
                </div>
            </div>

            <div class="footer-links">
                <h4>Kategori Ujian</h4>
                <ul>
                    <li><a href="<?= $root_url ?>/tryout.php?track=cpns">Simulasi CAT SKD CPNS</a></li>
                    <li><a href="<?= $root_url ?>/tryout.php?track=bumn">Rekrutmen BUMN (RBB)</a></li>
                    <li><a href="<?= $root_url ?>/tryout.php?track=kedinasan">Sekolah Kedinasan</a></li>
                    <li><a href="<?= $root_url ?>/tryout.php?track=psikotes">Psikotes Kerja</a></li>
                    <li><a href="<?= $root_url ?>/latihan.php">Bank Soal Semua Materi</a></li>
                </ul>
            </div>

            <div class="footer-links">
                <h4>Layanan & Fitur</h4>
                <ul>
                    <li><a href="<?= $root_url ?>/artikel.php">Artikel & Tips Lulus</a></li>
                    <li><a href="<?= $root_url ?>/upgrade.php">Paket PRO VIP All-Access</a></li>
                    <li><a href="<?= $root_url ?>/tryout.php">Coba Free Trial Gratis</a></li>
                    <li><a href="<?= $root_url ?>/riwayat.php">Sertifikat & Riwayat Ujian</a></li>
                </ul>
            </div>

            <div class="footer-links">
                <h4>Bantuan & Legalitas</h4>
                <ul>
                    <li><a href="<?= $root_url ?>/syarat-ketentuan.php">Syarat & Ketentuan</a></li>
                    <li><a href="<?= $root_url ?>/kebijakan-privasi.php">Kebijakan Privasi</a></li>
                    <li><a href="<?= $root_url ?>/kebijakan-pengembalian-dana.php">Kebijakan Refund</a></li>
                    <li><a href="<?= $root_url ?>/kontak.php">Pusat Bantuan & Kontak</a></li>
                </ul>
            </div>
        </div>

        <?php
        $vSummary = null;
        try {
            if (function_exists('getVisitorSummaryStats')) {
                $vSummary = getVisitorSummaryStats(getDB());
            }
        } catch (Throwable $e) {}
        ?>
        <div class="footer-bottom" style="align-items: flex-end;">
            <div style="display: flex; flex-direction: column; gap: 0.75rem; max-width: 100%;">
                <?php if ($vSummary): 
                    $gPct = $vSummary['growth_pct'];
                    $gBadge = ($gPct > 0)
                        ? '<span style="color:#4ade80; font-weight:700;">▲ +' . $gPct . '%</span>'
                        : (($gPct < 0)
                            ? '<span style="color:#fca5a5; font-weight:700;">▼ ' . $gPct . '%</span>'
                            : '<span style="color:#94a3b8;">• 0%</span>');
                ?>
                    <div class="visitor-footer-widget">
                        <div class="vfw-header">
                            <span class="vfw-dot"></span>
                            <span>STATISTIK PENGUNJUNG</span>
                            <?php if (function_exists('isAdmin') && isAdmin()): ?>
                                <a href="<?= $root_url ?>/admin/statistik.php" style="color: var(--core-gold); font-size: 0.72rem; margin-left: auto; font-weight: 700; text-decoration: underline;">
                                    Detail Grafik →
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="vfw-metrics">
                            <div class="vfw-item">
                                <span class="vfw-label">Online</span>
                                <b class="vfw-val" style="color: #4ade80;"><?= number_format($vSummary['online_now']) ?></b>
                            </div>
                            <div class="vfw-item">
                                <span class="vfw-label">Hari Ini</span>
                                <b class="vfw-val"><?= number_format($vSummary['today_visitors']) ?> <small style="font-weight:500; font-size:0.68rem;">(<?= $gBadge ?>)</small></b>
                            </div>
                            <div class="vfw-item">
                                <span class="vfw-label">Bulan Ini</span>
                                <b class="vfw-val"><?= number_format($vSummary['month_visitors']) ?></b>
                            </div>
                            <div class="vfw-item">
                                <span class="vfw-label">Total Views</span>
                                <b class="vfw-val" style="color: var(--core-gold);"><?= number_format($vSummary['total_views']) ?></b>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div>
                    &copy; <?= date('Y') ?> <b>SIAP TES</b> (<b>siaptes.id</b>). Hak Cipta Dilindungi Undang-Undang Republik Indonesia.
                </div>
            </div>
            <div>
                Platform Ekosistem Persiapan Ujian Seleksi Nasional & Karier Profesional &bull; <span style="color: var(--core-gold); font-size: 0.75rem;">v1.1 (Sync Aktif)</span>
            </div>
        </div>
    </div>
</footer>

</body>
</html>
