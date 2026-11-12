<?php
// includes/footer.php
?>
</div> <!-- End .container -->

<footer>
    <div class="footer-container">
        <div class="footer-top">
            <div class="footer-brand">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 0.75rem;">
                    <div style="background: var(--core-gold); color: var(--core-navy-dark); width: 34px; height: 34px; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-weight: 800;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                        </svg>
                    </div>
                    <h3 style="margin: 0; color: #ffffff;">SIAP<span>TES</span>.ID</h3>
                </div>
                <p style="color: #9ab4cc; line-height: 1.6; font-size: 0.85rem; max-width: 480px;">
                    Platform persiapan dan simulasi Computer Assisted Test (CAT) terpadu nomor satu di Indonesia.
                    Satu portal untuk latihan soal dan tryout seleksi <b>CPNS / PPPK</b>, <b>Rekrutmen Bersama BUMN</b>, <b>Sekolah Kedinasan</b>, serta <b>Psikotes Kerja & Asesmen Perusahaan</b>.
                </p>
                <div style="margin-top: 1rem; display: flex; gap: 1rem; align-items: center; font-size: 0.8rem; color: var(--core-gold); flex-wrap: wrap;">
                    <span>🏛️ Standar Resmi BKN & MenPAN-RB</span>
                    <span>🏢 Standar FHCI BUMN</span>
                    <span>🎓 Jalur Masuk Kedinasan</span>
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

        <div class="footer-bottom">
            <div>
                &copy; <?= date('Y') ?> <b>SIAP TES</b> (<b>siaptes.id</b>). Hak Cipta Dilindungi Undang-Undang Republik Indonesia.
            </div>
            <div>
                Platform Ekosistem Persiapan Ujian Seleksi Nasional & Karier Profesional &bull; <span style="color: var(--core-gold); font-size: 0.75rem;">v1.1 (Sync Aktif)</span>
            </div>
        </div>
    </div>
</footer>

</body>
</html>
