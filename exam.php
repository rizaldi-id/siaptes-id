<?php
// exam.php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

requireLogin();
$db = getDB();
$user = currentUser();

$action = $_GET['action'] ?? '';

// 1. ACTION: START PACKAGE (TRYOUT)
if ($action === 'start_package') {
    try {
        $package_id = intval($_GET['package_id'] ?? 0);
        $package = $db->query("SELECT * FROM packages WHERE id = $package_id AND is_active = 1")->fetch();

        if (!$package) {
            header('Location: tryout.php?msg=' . urlencode('Paket tidak ditemukan'));
            exit;
        }

        // Check Premium requirement
        if (!empty($package['is_premium']) && !isPremiumUser()) {
            header('Location: upgrade.php?package_id=' . $package_id . '&msg=' . urlencode('Konten ini khusus Member PRO VIP. Silakan lakukan pembayaran Rp 29.000 via QRIS terlebih dahulu untuk membuka akses penuh.'));
            exit;
        }

        // Create new attempt
        $now = date('Y-m-d H:i:s');
        $stmt = $db->prepare("INSERT INTO exam_attempts (user_id, package_id, mode, status, start_time) VALUES (?, ?, 'tryout', 'in_progress', ?)");
        $stmt->execute([$user['id'], $package_id, $now]);
        $attempt_id = $db->lastInsertId();

        // Select questions assigned to this package OR general questions
        $questions = $db->query("SELECT id FROM questions WHERE package_id = $package_id ORDER BY category_id, id")->fetchAll(PDO::FETCH_COLUMN);
        
        // If no questions specific to package, fallback to all questions
        if (empty($questions)) {
            $questions = $db->query("SELECT id FROM questions ORDER BY category_id, id")->fetchAll(PDO::FETCH_COLUMN);
        }

        $ansStmt = $db->prepare("INSERT INTO user_answers (attempt_id, question_id) VALUES (?, ?)");
        foreach ($questions as $qid) {
            $ansStmt->execute([$attempt_id, $qid]);
        }

        header("Location: exam.php?attempt_id=$attempt_id");
        exit;
    } catch (Throwable $e) {
        header('Location: tryout.php?msg=' . urlencode('Terjadi kendala memulai tryout: ' . $e->getMessage()));
        exit;
    }
}

// 2. ACTION: START PRACTICE
if ($action === 'start_practice') {
    try {
        $category_id = intval($_GET['category_id'] ?? 0);
        $limit = intval($_GET['limit'] ?? 5);
        if ($limit <= 0) $limit = 5;

        // Jika akun belum PRO VIP dan memilih kuota > 5 soal, arahkan ke pembayaran QRIS Rp 29.000
        if ($limit > 5 && !isPremiumUser()) {
            header('Location: upgrade.php?msg=' . urlencode('Kuota latihan di atas 5 soal khusus Member PRO VIP. Silakan bayar Rp 29.000 via QRIS untuk membuka seluruh bank soal tanpa batas!'));
            exit;
        }

        $category = $db->query("SELECT * FROM categories WHERE id = $category_id")->fetch();
        if (!$category) {
            header('Location: latihan.php?msg=' . urlencode('Kategori tidak ditemukan'));
            exit;
        }

        // Create attempt
        $now = date('Y-m-d H:i:s');
        $stmt = $db->prepare("INSERT INTO exam_attempts (user_id, package_id, mode, status, start_time) VALUES (?, NULL, 'practice', 'in_progress', ?)");
        $stmt->execute([$user['id'], $now]);
        $attempt_id = $db->lastInsertId();

        // Select randomized questions for practice (kompatibel penuh MySQL & SQLite)
        $randOrder = function_exists('sqlRandomOrder') ? sqlRandomOrder($db) : 'RANDOM()';
        $safeLimit = max(1, intval($limit));
        $qStmt = $db->prepare("SELECT id FROM questions WHERE category_id = ? ORDER BY $randOrder LIMIT " . $safeLimit);
        $qStmt->execute([$category_id]);
        $questions = $qStmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($questions)) {
            header('Location: latihan.php?msg=' . urlencode('Belum ada soal pada kategori ini'));
            exit;
        }

        $ansStmt = $db->prepare("INSERT INTO user_answers (attempt_id, question_id) VALUES (?, ?)");
        foreach ($questions as $qid) {
            $ansStmt->execute([$attempt_id, $qid]);
        }

        header("Location: exam.php?attempt_id=$attempt_id");
        exit;
    } catch (Throwable $e) {
        header('Location: latihan.php?msg=' . urlencode('Terjadi kendala memulai latihan: ' . $e->getMessage()));
        exit;
    }
}

// 3. ACTION: FINISH EXAM
if ($action === 'finish') {
    $attempt_id = intval($_GET['attempt_id'] ?? 0);
    $attempt = $db->query("SELECT * FROM exam_attempts WHERE id = $attempt_id AND user_id = {$user['id']}")->fetch();

    if ($attempt && $attempt['status'] === 'in_progress') {
        // Calculate score
        $answers = $db->query("
            SELECT ua.*, q.category_id, c.code as cat_code, q.correct_option,
                   q.score_a, q.score_b, q.score_c, q.score_d, q.score_e
            FROM user_answers ua
            JOIN questions q ON ua.question_id = q.id
            JOIN categories c ON q.category_id = c.id
            WHERE ua.attempt_id = $attempt_id
        ")->fetchAll();

        $score_twk = 0;
        $score_tiu = 0;
        $score_tkp = 0;
        $cat_scores = [];

        $updAns = $db->prepare("UPDATE user_answers SET score_obtained = ? WHERE id = ?");

        foreach ($answers as $ans) {
            $sel = $ans['selected_option'];
            $pts = 0;
            $cat = $ans['cat_code'];

            if (!isset($cat_scores[$cat])) {
                $cat_scores[$cat] = 0;
            }

            // Check if question has weighted score (TKP, AKHLAK, SJT)
            $isWeighted = ($ans['score_a'] > 0 || $ans['score_b'] > 0);

            if ($isWeighted) {
                if ($sel === 'A') $pts = (int)$ans['score_a'];
                elseif ($sel === 'B') $pts = (int)$ans['score_b'];
                elseif ($sel === 'C') $pts = (int)$ans['score_c'];
                elseif ($sel === 'D') $pts = (int)$ans['score_d'];
                elseif ($sel === 'E') $pts = (int)$ans['score_e'];
            } else {
                // Standard Right/Wrong (+5 or 0)
                if ($sel !== null && $sel !== '' && $sel === $ans['correct_option']) {
                    $pts = 5;
                }
            }

            $cat_scores[$cat] += $pts;
            $updAns->execute([$pts, $ans['id']]);
        }

        $score_twk = $cat_scores['TWK'] ?? 0;
        $score_tiu = $cat_scores['TIU'] ?? 0;
        $score_tkp = $cat_scores['TKP'] ?? 0;

        $total_score = array_sum($cat_scores);

        // Passing Grade standards per package
        $pkgId = (int)($attempt['package_id'] ?? 0);
        $is_passed = 0;

        if ($attempt['mode'] === 'tryout') {
            if ($pkgId === 1) {
                // Official BKN CPNS SKD
                if ($score_twk >= 65 && $score_tiu >= 80 && $score_tkp >= 166) {
                    $is_passed = 1;
                }
            } elseif ($pkgId === 2) {
                // BUMN RBB (Min 55% score)
                $is_passed = ($total_score >= 130) ? 1 : 0;
            } elseif ($pkgId === 3) {
                // Kedinasan (Min 60% score)
                $is_passed = ($total_score >= 105) ? 1 : 0;
            } elseif ($pkgId === 4) {
                // Psikotes Kerja (Min 65% score)
                $is_passed = ($total_score >= 95) ? 1 : 0;
            } elseif ($pkgId === 5) {
                // Mini Trial All-in-One (Min 60%)
                $is_passed = ($total_score >= 80) ? 1 : 0;
            } else {
                $is_passed = ($total_score >= (count($answers) * 5 * 0.6)) ? 1 : 0;
            }
        } else {
            // For single practice category (Passing grade 60% of max potential score)
            $maxScore = count($answers) * 5;
            $is_passed = ($total_score >= ($maxScore * 0.6)) ? 1 : 0;
        }

        $end_time = date('Y-m-d H:i:s');
        $updAttempt = $db->prepare("
            UPDATE exam_attempts 
            SET status = 'completed', end_time = ?, score_twk = ?, score_tiu = ?, score_tkp = ?, total_score = ?, is_passed = ?
            WHERE id = ?
        ");
        $updAttempt->execute([$end_time, $score_twk, $score_tiu, $score_tkp, $total_score, $is_passed, $attempt_id]);
    }

    header("Location: hasil.php?attempt_id=$attempt_id");
    exit;
}

// 4. DISPLAY EXAM INTERFACE
$attempt_id = intval($_GET['attempt_id'] ?? 0);
$attempt = $db->query("
    SELECT ea.*, p.title as package_title, p.duration_minutes
    FROM exam_attempts ea
    LEFT JOIN packages p ON ea.package_id = p.id
    WHERE ea.id = $attempt_id AND ea.user_id = {$user['id']}
")->fetch();

if (!$attempt) {
    header('Location: tryout.php?msg=Sesi ujian tidak valid');
    exit;
}

if ($attempt['status'] === 'completed') {
    header("Location: hasil.php?attempt_id=$attempt_id");
    exit;
}

// Fetch all questions for this attempt
$questions = $db->query("
    SELECT ua.id as answer_id, ua.selected_option, ua.is_doubt,
           q.id as question_id, q.question_text, q.option_a, q.option_b, q.option_c, q.option_d, q.option_e,
           c.code as cat_code, c.name as cat_name
    FROM user_answers ua
    JOIN questions q ON ua.question_id = q.id
    JOIN categories c ON q.category_id = c.id
    WHERE ua.attempt_id = $attempt_id
    ORDER BY ua.id ASC
")->fetchAll();

$total_q = count($questions);

// Calculate remaining time
$duration_minutes = $attempt['duration_minutes'] ?: 60; // default 60 min if practice
$startTime = strtotime($attempt['start_time']);
$endTimeLimit = $startTime + ($duration_minutes * 60);
$remainingSeconds = max(0, $endTimeLimit - time());

$page_title = "Ujian CAT: " . ($attempt['package_title'] ?? 'Latihan Soal');
require_once __DIR__ . '/includes/header.php';
?>

<div class="exam-header">
    <div>
        <div style="font-size: 0.75rem; font-weight: 800; color: var(--core-gold); letter-spacing: 0.5px; text-transform: uppercase; margin-bottom: 2px;">
            LEMBAR UJIAN RESMI COMPUTER ASSISTED TEST (CAT)
        </div>
        <h1 style="font-size: 1.35rem; font-weight: 800; color: #ffffff;">
            <?= htmlspecialchars($attempt['package_title'] ?? 'Latihan Soal ' . $attempt['mode']) ?>
        </h1>
        <span style="font-size: 0.825rem; color: #b9d3eb;">
            Mode Ujian: <b><?= strtoupper($attempt['mode']) ?></b> &bull; Alokasi: <?= $total_q ?> Butir Soal
        </span>
    </div>

    <!-- Timer Box -->
    <div class="timer-box">
        <span>⏱️</span>
        <span id="exam-timer">--:--:--</span>
    </div>
</div>

<div class="exam-layout">
    <!-- Area Pertanyaan -->
    <div>
        <?php foreach ($questions as $index => $q): ?>
            <div class="card question-container" id="q-card-<?= $index ?>" style="<?= $index === 0 ? '' : 'display:none;' ?>">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--gray-200); padding-bottom: 0.75rem;">
                    <div>
                        <span style="font-size: 1.1rem; font-weight: 700; color: var(--primary);">Soal Nomor <?= $index + 1 ?></span>
                        <span style="color: var(--gray-600); font-size: 0.85rem;">/ <?= $total_q ?></span>
                    </div>
                    <span class="badge badge-<?= strtolower($q['cat_code']) ?>"><?= $q['cat_code'] ?> - <?= $q['cat_name'] ?></span>
                </div>

                <div class="question-box">
                    <?= nl2br(htmlspecialchars($q['question_text'])) ?>
                </div>

                <div class="option-list">
                    <?php 
                    $options = [
                        'A' => $q['option_a'],
                        'B' => $q['option_b'],
                        'C' => $q['option_c'],
                        'D' => $q['option_d'],
                        'E' => $q['option_e'],
                    ];
                    foreach ($options as $optKey => $optVal): 
                        $isSelected = ($q['selected_option'] === $optKey);
                    ?>
                        <div class="option-item <?= $isSelected ? 'selected' : '' ?>" 
                             onclick="selectAnswer(<?= $q['answer_id'] ?>, '<?= $optKey ?>', <?= $index ?>)">
                            <div class="option-letter"><?= $optKey ?></div>
                            <div style="font-size: 0.95rem; line-height: 1.5; padding-top: 3px;">
                                <?= htmlspecialchars($optVal) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Kontrol Navigasi Soal -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 2rem; border-top: 1px solid var(--gray-200); padding-top: 1.25rem;">
                    <div>
                        <?php if ($index > 0): ?>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="showQuestion(<?= $index - 1 ?>)">
                                ⬅ Sebelumnya
                            </button>
                        <?php endif; ?>
                    </div>

                    <div style="display: flex; gap: 0.75rem;">
                        <button type="button" 
                                class="btn btn-warning btn-sm" 
                                id="doubt-btn-<?= $index ?>" 
                                onclick="toggleDoubt(<?= $q['answer_id'] ?>, <?= $index ?>)">
                            <?= $q['is_doubt'] ? '✓ Hilangkan Ragu' : '⚠️ Ragu-Ragu' ?>
                        </button>

                        <?php if ($index < $total_q - 1): ?>
                            <button type="button" class="btn btn-primary btn-sm" onclick="showQuestion(<?= $index + 1 ?>)">
                                Selanjutnya ➔
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-success btn-sm" onclick="finishExam()">
                                Selesaikan Ujian ✔
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Panel Samping / Nomor Soal -->
    <div>
        <div class="card" style="position: sticky; top: 80px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                <h3 style="font-size: 1rem; font-weight: 700;">Daftar Soal</h3>
                <span id="save-status" style="font-size: 0.75rem; color: var(--success); font-weight: 600;">Semua tersimpan</span>
            </div>

            <!-- Petunjuk Warna -->
            <div style="display: flex; gap: 0.5rem; font-size: 0.75rem; margin-bottom: 0.75rem; flex-wrap: wrap;">
                <span style="display:flex; align-items:center; gap:4px;"><span style="width:12px; height:12px; background:var(--primary); border-radius:2px;"></span> Dijawab</span>
                <span style="display:flex; align-items:center; gap:4px;"><span style="width:12px; height:12px; background:var(--warning); border-radius:2px;"></span> Ragu</span>
                <span style="display:flex; align-items:center; gap:4px;"><span style="width:12px; height:12px; border:1px solid var(--gray-300); background:#fff; border-radius:2px;"></span> Kosong</span>
            </div>

            <div class="nav-grid">
                <?php foreach ($questions as $index => $q): 
                    $classes = [];
                    if ($index === 0) $classes[] = 'active';
                    if (!empty($q['selected_option'])) $classes[] = 'answered';
                    if ($q['is_doubt']) $classes[] = 'doubt';
                ?>
                    <button type="button" 
                            id="nav-btn-<?= $index ?>" 
                            class="q-number-btn <?= implode(' ', $classes) ?>" 
                            onclick="showQuestion(<?= $index ?>)">
                        <?= $index + 1 ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div style="margin-top: 1.5rem; border-top: 1px solid var(--gray-200); padding-top: 1rem;">
                <button type="button" class="btn btn-danger" style="width: 100%; font-size: 0.9rem;" onclick="finishExam()">
                    Kumpulkan Ujian Sekarang
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentQuestionIndex = 0;
const totalQuestions = <?= $total_q ?>;
let remainingSeconds = <?= $remainingSeconds ?>;

// Question Navigation
function showQuestion(index) {
    if (index < 0 || index >= totalQuestions) return;
    
    // Hide current
    document.getElementById('q-card-' + currentQuestionIndex).style.display = 'none';
    document.getElementById('nav-btn-' + currentQuestionIndex).classList.remove('active');

    // Show target
    currentQuestionIndex = index;
    document.getElementById('q-card-' + currentQuestionIndex).style.display = 'block';
    document.getElementById('nav-btn-' + currentQuestionIndex).classList.add('active');
}

// Select Answer & Auto-save via AJAX
function selectAnswer(answerId, optionKey, index) {
    const card = document.getElementById('q-card-' + index);
    const options = card.querySelectorAll('.option-item');
    options.forEach(opt => opt.classList.remove('selected'));

    event.currentTarget.classList.add('selected');

    // Update Nav grid state
    const navBtn = document.getElementById('nav-btn-' + index);
    if (!navBtn.classList.contains('doubt')) {
        navBtn.classList.add('answered');
    }

    // Save to server
    saveAnswer(answerId, optionKey, null);
}

// Toggle Doubt (Ragu-ragu)
function toggleDoubt(answerId, index) {
    const navBtn = document.getElementById('nav-btn-' + index);
    const doubtBtn = document.getElementById('doubt-btn-' + index);
    const isCurrentlyDoubt = navBtn.classList.contains('doubt');
    const newDoubtState = isCurrentlyDoubt ? 0 : 1;

    if (newDoubtState === 1) {
        navBtn.classList.add('doubt');
        doubtBtn.textContent = '✓ Hilangkan Ragu';
    } else {
        navBtn.classList.remove('doubt');
        doubtBtn.textContent = '⚠️ Ragu-Ragu';
    }

    saveAnswer(answerId, null, newDoubtState);
}

// AJAX helper to save state
function saveAnswer(answerId, option, isDoubt) {
    const statusText = document.getElementById('save-status');
    statusText.textContent = 'Menyimpan...';
    statusText.style.color = '#f59e0b';

    const formData = new FormData();
    formData.append('answer_id', answerId);
    if (option !== null) formData.append('selected_option', option);
    if (isDoubt !== null) formData.append('is_doubt', isDoubt);

    fetch('save_answer.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            statusText.textContent = 'Tersimpan otomatis';
            statusText.style.color = '#10b981';
        } else {
            statusText.textContent = 'Gagal menyimpan!';
            statusText.style.color = '#ef4444';
        }
    })
    .catch(() => {
        statusText.textContent = 'Koneksi bermasalah!';
        statusText.style.color = '#ef4444';
    });
}

// Exam Timer Countdown
function updateTimer() {
    if (remainingSeconds <= 0) {
        alert('Waktu ujian telah habis! Sistem akan mengumpulkan ujian secara otomatis.');
        window.location.href = 'exam.php?action=finish&attempt_id=<?= $attempt_id ?>';
        return;
    }

    const hours = Math.floor(remainingSeconds / 3600);
    const minutes = Math.floor((remainingSeconds % 3600) / 60);
    const seconds = remainingSeconds % 60;

    const timerStr = 
        String(hours).padStart(2, '0') + ':' + 
        String(minutes).padStart(2, '0') + ':' + 
        String(seconds).padStart(2, '0');

    document.getElementById('exam-timer').textContent = timerStr;
    remainingSeconds--;
}

setInterval(updateTimer, 1000);
updateTimer();

function finishExam() {
    if (confirm('Apakah Anda yakin ingin mengakhiri dan mengumpulkan jawaban ujian ini?')) {
        window.location.href = 'exam.php?action=finish&attempt_id=<?= $attempt_id ?>';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
