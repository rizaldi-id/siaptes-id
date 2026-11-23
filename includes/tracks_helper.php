<?php
/**
 * includes/tracks_helper.php
 * Konfigurasi terpusat untuk 4 jalur seleksi utama SIAP TES (CPNS, BUMN, Kedinasan, Psikotes),
 * statistik jumlah bank soal/paket per jalur, serta smart search routing.
 */

if (!function_exists('getSelectionTracks')) {
    /**
     * Definisi 4 jalur kebutuhan tes utama pada portal depan & halaman jalur (jalur.php).
     */
    function getSelectionTracks(): array
    {
        return [
            'cpns' => [
                'slug' => 'cpns',
                'short_title' => 'CPNS & PPPK',
                'card_subtitle' => 'Tryout CAT SKD BKN, bank soal TWK, TIU, TKP & panduan ASN',
                'title' => 'Panduan & Persiapan Seleksi CPNS',
                'hero_desc' => 'Akses terpadu simulasi CAT SKD standar resmi BKN, ribuan bank soal TWK-TIU-TKP dengan pembahasan lengkap, serta artikel strategi kelulusan ASN.',
                'icon' => 'track-cpns',
                'tone' => 'navy',
                'accent' => '#2563eb',
                'badge_label' => 'Standar CAT BKN',
                'passing_summary' => 'Passing Grade SKD: TWK ≥ 65 • TIU ≥ 80 • TKP ≥ 166 (110 Soal / 100 Menit)',
                'package_groups' => ['cpns', 'all'],
                'bank_groups' => ['cpns'],
                'article_categories' => ['CPNS & PPPK', 'Tips & Trik CAT'],
                'bank_category_notes' => [
                    'TWK' => 'Wawasan Kebangsaan • Pilar Negara & Integritas',
                    'TIU' => 'Intelegensia Umum • Verbal, Numerik & Figural',
                    'TKP' => 'Karakteristik Pribadi • Skala Nilai 1–5',
                ],
                'quick_links' => [
                    ['label' => 'Simulasi Tryout CAT SKD CPNS', 'url' => 'jalur.php?kategori=cpns&tab=tryout'],
                    ['label' => 'Latihan Bank Soal TIU Numerik & Figural', 'url' => 'jalur.php?kategori=cpns&tab=bank&q=TIU'],
                    ['label' => 'Latihan Bank Soal TWK Nasionalisme', 'url' => 'jalur.php?kategori=cpns&tab=bank&q=TWK'],
                    ['label' => 'Latihan Bank Soal TKP Pelayanan Publik', 'url' => 'jalur.php?kategori=cpns&tab=bank&q=TKP'],
                    ['label' => 'Panduan Nilai Ambang Batas SKD Terbaru', 'url' => 'jalur.php?kategori=cpns&tab=artikel'],
                ],
                'keywords' => ['cpns', 'pppk', 'asn', 'bkn', 'skd', 'skb', 'twk', 'tiu', 'tkp', 'sscasn', 'pancasila', 'uud', 'figural', 'silogisme', 'pns'],
            ],
            'bumn' => [
                'slug' => 'bumn',
                'short_title' => 'Rekrutmen BUMN',
                'card_subtitle' => 'Tryout RBB FHCI, bank soal TKD, AKHLAK, English & tips lolos',
                'title' => 'Panduan & Persiapan Rekrutmen BUMN',
                'hero_desc' => 'Persiapan terstruktur menghadapi Rekrutmen Bersama BUMN (RBB) meliputi simulasi TKD, Core Values AKHLAK, Bahasa Inggris bisnis, dan tips seleksi tahap akhir.',
                'icon' => 'track-bumn',
                'tone' => 'teal',
                'accent' => '#0d9488',
                'badge_label' => 'Standar RBB FHCI',
                'passing_summary' => 'Komponen RBB: Tes Kemampuan Dasar (TKD), Core Values AKHLAK & English for Business',
                'package_groups' => ['bumn', 'all'],
                'bank_groups' => ['bumn'],
                'article_categories' => ['Rekrutmen BUMN', 'Tips & Trik CAT'],
                'bank_category_notes' => [
                    'TKD_BUMN' => 'Verbal Logical Reasoning, Number Sequence & Diagram',
                    'AKHLAK' => 'Amanah, Kompeten, Harmonis, Loyal, Adaptif, Kolaboratif',
                    'ENG_BUMN' => 'Structure, Written Expression & Reading Comprehension',
                ],
                'quick_links' => [
                    ['label' => 'Tryout RBB BUMN (TKD + AKHLAK + English)', 'url' => 'jalur.php?kategori=bumn&tab=tryout'],
                    ['label' => 'Bank Soal Core Values AKHLAK BUMN', 'url' => 'jalur.php?kategori=bumn&tab=bank&q=AKHLAK'],
                    ['label' => 'Bank Soal TKD BUMN Lengkap', 'url' => 'jalur.php?kategori=bumn&tab=bank&q=TKD'],
                    ['label' => 'Bank Soal English BUMN (TOEFL-style)', 'url' => 'jalur.php?kategori=bumn&tab=bank&q=ENG'],
                    ['label' => 'Artikel Strategi Lolos Rekrutmen BUMN', 'url' => 'jalur.php?kategori=bumn&tab=artikel'],
                ],
                'keywords' => ['bumn', 'rbb', 'fhci', 'akhlak', 'tkd', 'english', 'inggris', 'pertamina', 'mandiri', 'bri', 'bni', 'pln', 'telkom', 'tbi'],
            ],
            'kedinasan' => [
                'slug' => 'kedinasan',
                'short_title' => 'Sekolah Kedinasan',
                'card_subtitle' => 'Simulasi SKD & TPA SPMB PKN STAN, STIS, IPDN, Poltekip',
                'title' => 'Panduan & Persiapan Sekolah Kedinasan',
                'hero_desc' => 'Paket persiapan khusus calon praja dan taruna PKN STAN, Politeknik Statistika STIS, IPDN, Poltekip, Poltekim, dan STIN lengkap dengan TPA Terapan serta SKD Kedinasan.',
                'icon' => 'track-kedinasan',
                'tone' => 'amber',
                'accent' => '#b91c1c',
                'badge_label' => 'SPMB STAN, STIS & IPDN',
                'passing_summary' => 'Cakupan Materi: Tes Potensi Akademik (TPA) Matematika Terapan + SKD Kedinasan (TWK, TIU, TKP)',
                'package_groups' => ['kedinasan', 'cpns', 'all'],
                'bank_groups' => ['kedinasan', 'cpns'],
                'article_categories' => ['Sekolah Kedinasan', 'CPNS & PPPK', 'Tips & Trik CAT'],
                'bank_category_notes' => [
                    'KEDINASAN_TPA' => 'Subtes Utama • Aljabar, Geometri, Statistika & Analisis Data',
                    'TIU' => 'SKD Kedinasan • Penalaran Numerik, Verbal & Figural',
                    'TWK' => 'SKD Kedinasan • Wawasan Kebangsaan & Konstitusi',
                    'TKP' => 'SKD Kedinasan • Karakteristik Pelayanan Publik',
                ],
                'quick_links' => [
                    ['label' => 'Tryout Masuk Sekolah Kedinasan', 'url' => 'jalur.php?kategori=kedinasan&tab=tryout'],
                    ['label' => 'Bank Soal TPA Kedinasan (STAN & STIS)', 'url' => 'jalur.php?kategori=kedinasan&tab=bank&q=TPA'],
                    ['label' => 'Latihan Soal TIU SKD Kedinasan', 'url' => 'jalur.php?kategori=kedinasan&tab=bank&q=TIU'],
                    ['label' => 'Latihan Soal TWK SKD Kedinasan', 'url' => 'jalur.php?kategori=kedinasan&tab=bank&q=TWK'],
                    ['label' => 'Panduan Pendaftaran & Tahapan Sekolah Kedinasan', 'url' => 'jalur.php?kategori=kedinasan&tab=artikel'],
                ],
                'keywords' => ['kedinasan', 'stan', 'pkn stan', 'stis', 'ipdn', 'poltekip', 'poltekim', 'stin', 'stmk', 'spmb', 'taruna', 'praja', 'tpa'],
            ],
            'psikotes' => [
                'slug' => 'psikotes',
                'short_title' => 'Psikotes Kerja',
                'card_subtitle' => 'Latihan logika, spasial figural, SJT kerja & tes potensi gratis',
                'title' => 'Panduan & Persiapan Psikotes Kerja',
                'hero_desc' => 'Latih ketelitian, penalaran logika analitis, visualisasi spasial 3D, Situational Judgement Test (SJT), serta uji kemampuan lewat Tes IQ, Minat RIASEC & Game Kognitif gratis.',
                'icon' => 'track-psikotes',
                'tone' => 'indigo',
                'accent' => '#7c3aed',
                'badge_label' => 'Asesmen HR & Swasta',
                'passing_summary' => 'Komponen Psikotes: Penalaran Logika, Spasial Figural 3D, SJT Perilaku Kerja & Tes Potensi Gratis',
                'package_groups' => ['psikotes', 'all'],
                'bank_groups' => ['psikotes'],
                'article_categories' => ['Psikotes & Karier', 'Tips & Trik CAT'],
                'bank_category_notes' => [
                    'PSIKO_LOGIKA' => 'Deret Pola, Silogisme Analitis & Penalaran Deduktif',
                    'PSIKO_SPASIAL' => 'Jaring-Jaring Kubus, Pencerminan & Rotasi Gambar 3D',
                    'PSIKO_SJT' => 'Situational Judgement Test • Pengambilan Keputusan Kerja',
                ],
                'quick_links' => [
                    ['label' => 'Simulasi Asesmen Psikotes Perusahaan', 'url' => 'jalur.php?kategori=psikotes&tab=tryout'],
                    ['label' => 'Bank Soal Logika & Penalaran Psikotes', 'url' => 'jalur.php?kategori=psikotes&tab=bank&q=LOGIKA'],
                    ['label' => 'Bank Soal Spasial & Figural 3D', 'url' => 'jalur.php?kategori=psikotes&tab=bank&q=SPASIAL'],
                    ['label' => 'Tes IQ, Buta Warna & Game Kognitif Gratis', 'url' => 'tes-potensi.php'],
                    ['label' => 'Panduan Lolos Psikotes & Wawancara HR', 'url' => 'jalur.php?kategori=psikotes&tab=artikel'],
                ],
                'keywords' => ['psikotes', 'psikologi', 'spasial', 'kubus', 'jaring', 'rotasi', 'sjt', 'situational', 'logika', 'wartegg', 'kraepelin', 'pauli', 'kerja', 'hrd', 'swasta'],
            ],
        ];
    }
}

if (!function_exists('getTrackConfig')) {
    /**
     * Mengambil konfigurasi jalur berdasarkan slug (fallback null jika tidak valid).
     */
    function getTrackConfig(string $slug): ?array
    {
        $slug = strtolower(trim($slug));
        $tracks = getSelectionTracks();
        return $tracks[$slug] ?? null;
    }
}

if (!function_exists('getTrackQuestionCounts')) {
    /**
     * Menghitung jumlah butir soal dan paket aktif per jalur secara dinamis dari SQLite.
     */
    function getTrackQuestionCounts(PDO $db): array
    {
        $groupQuestions = [];
        try {
            $stmt = $db->query("
                SELECT c.category_group, COUNT(q.id) AS total_q
                FROM categories c
                LEFT JOIN questions q ON q.category_id = c.id
                GROUP BY c.category_group
            ");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $groupQuestions[$row['category_group']] = (int)$row['total_q'];
            }
        } catch (Throwable $e) {
            // Fallback diam bila tabel belum tersedia
        }

        $groupPackages = [];
        try {
            $stmtPkg = $db->query("SELECT category_group, COUNT(*) AS total_pkg FROM packages GROUP BY category_group");
            foreach ($stmtPkg->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $groupPackages[$row['category_group']] = (int)$row['total_pkg'];
            }
        } catch (Throwable $e) {
            // Fallback diam bila tabel belum tersedia
        }

        $tracks = getSelectionTracks();
        $result = [];
        foreach ($tracks as $slug => $cfg) {
            $qSum = 0;
            foreach ($cfg['bank_groups'] as $bg) {
                $qSum += ($groupQuestions[$bg] ?? 0);
            }
            $pkgSum = 0;
            foreach ($cfg['package_groups'] as $pg) {
                if ($pg === 'all' || ($slug === 'kedinasan' && $pg === 'cpns')) {
                    continue;
                }
                $pkgSum += ($groupPackages[$pg] ?? 0);
            }
            $result[$slug] = [
                'questions' => $qSum,
                'packages' => max(1, $pkgSum),
            ];
        }

        return $result;
    }
}

if (!function_exists('resolvePortalSearchRedirect')) {
    /**
     * Menentukan URL tujuan cerdas berdasarkan kata kunci pencarian dari beranda atau jalur.
     */
    function resolvePortalSearchRedirect(string $query): string
    {
        $raw = trim($query);
        if ($raw === '') {
            return 'jalur.php?kategori=cpns';
        }

        $q = strtolower($raw);

        // 1. Tes Potensi Gratis (Buta Warna, IQ, Minat RIASEC, Game Kognitif)
        foreach (['buta warna', 'ishihara', 'tes iq', 'iq online', 'riasec', 'holland', 'minat karier', 'game kognitif', 'tes potensi'] as $kw) {
            if (strpos($q, $kw) !== false) {
                return 'tes-potensi.php';
            }
        }

        // 2. Deteksi preferensi tab dari kata kunci
        $tab = '';
        if (strpos($q, 'artikel') !== false || strpos($q, 'panduan') !== false || strpos($q, 'tips') !== false || strpos($q, 'passing grade') !== false || strpos($q, 'ambang batas') !== false) {
            $tab = 'artikel';
        } elseif (strpos($q, 'bank soal') !== false || strpos($q, 'latihan') !== false || strpos($q, 'materi') !== false) {
            $tab = 'bank';
        } elseif (strpos($q, 'tryout') !== false || strpos($q, 'simulasi') !== false || strpos($q, 'cat') !== false) {
            $tab = 'tryout';
        }

        // 3. Deteksi kategori jalur prioritas
        $tracks = getSelectionTracks();
        // Urutan spesifik terlebih dahulu agar query seperti "tpa kedinasan" / "psikotes logika" tepat sasaran
        foreach (['kedinasan', 'bumn', 'psikotes', 'cpns'] as $slug) {
            foreach ($tracks[$slug]['keywords'] as $kw) {
                if (strpos($q, $kw) !== false) {
                    $params = ['kategori' => $slug];
                    if ($tab !== '') {
                        $params['tab'] = $tab;
                    }
                    $params['q'] = $raw;
                    return 'jalur.php?' . http_build_query($params);
                }
            }
        }

        // 4. Jika kata kunci umum terkait artikel/tips tanpa menyebut jalur
        if ($tab === 'artikel') {
            return 'artikel.php?q=' . urlencode($raw);
        }

        // Default ke portal CPNS dengan filter query tetap aktif
        $params = ['kategori' => 'cpns', 'q' => $raw];
        if ($tab !== '') {
            $params['tab'] = $tab;
        }
        return 'jalur.php?' . http_build_query($params);
    }
}
