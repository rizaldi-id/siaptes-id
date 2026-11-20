#!/usr/bin/env python3
"""
Script untuk mengekstrak & menggabungkan soal terbaru dari Bank Data Simulator V2.89
(bank data terbaru/Bank_Data_Simulator_DalamNegeri_V2_89/01_MASTER_QUESTION_BANK_7335/MASTER_ITEMS_7335.json)
ke dalam database/all_questions.json dengan deduplikasi ketat dan penyaringan soal visual/non-MCQ.
"""
import json
import re
from collections import Counter
from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent.parent
V289_JSON_PATH = (
    BASE_DIR
    / "bank data terbaru"
    / "Bank_Data_Simulator_DalamNegeri_V2_89"
    / "01_MASTER_QUESTION_BANK_7335"
    / "MASTER_ITEMS_7335.json"
)
FALLBACK_HTML_PATH = BASE_DIR / "Bank Soal" / "Simulator_Psikotes_DalamNegeri_V2_50_RC1_Standalone.html"
JSON_PATH = BASE_DIR / "database" / "all_questions.json"

LETTERS = ["A", "B", "C", "D", "E"]
NON_CAT_MODULES = {"colorblind", "bei", "case", "inbasket", "lgd", "roleplay", "enneagram", "eq", "papi", "riasec"}
VISUAL_PHRASES = (
    "gambar di atas",
    "tabel di atas",
    "diagram di atas",
    "grafik di atas",
    "ilustrasi di atas",
    "perhatikan gambar",
    "lihat gambar",
    "bentuk asli di atas",
    "jaring-jaring kubus di atas",
    "tumpukan kubus di atas",
    "pola titik berwarna",
)


def normalize_text(s: str) -> str:
    return re.sub(r"\s+", " ", str(s or "").strip()).lower()


def map_category(module: str, submodule: str, counters: dict) -> int:
    if module == "twk":
        return 1  # TWK (CPNS)
    if module == "tkp":
        return 3  # TKP (CPNS)
    if module == "akhlak":
        return 5  # AKHLAK (BUMN)
    if module == "english":
        return 6  # ENG_BUMN (BUMN)
    if module == "spatial":
        return 9  # PSIKO_SPASIAL (Psikotes)
    if module in ("learning-agility", "sjt"):
        return 10  # PSIKO_SJT (Psikotes)
    if module in ("critical", "attention"):
        return 8  # PSIKO_LOGIKA (Psikotes)

    if module == "general-ability":
        if submodule == "bahasa-indonesia":
            return 1  # TWK (CPNS - Bahasa Indonesia)
        if submodule == "bahasa-inggris":
            return 6  # ENG_BUMN (BUMN)
        if submodule == "deret-bilangan":
            return 2  # TIU (CPNS)
        return 7  # KEDINASAN_TPA (Kedinasan)

    if module == "verbal":
        if submodule in ("analogi", "sinonim", "antonim", "sinonim-frasa", "pemahaman-bacaan", "logika-kalimat"):
            return 2  # TIU (CPNS)
        if submodule in ("kalimat-teratur", "kelompok-kata", "peribahasa"):
            return 4  # TKD_BUMN (BUMN)
        if submodule in ("acak-kata", "pembentukan-kata"):
            return 7  # KEDINASAN_TPA (Kedinasan)
        if submodule == "ejaan-kata":
            counters["ejaan"] += 1
            return 4 if counters["ejaan"] % 2 == 0 else 7

    if module == "numerical":
        if submodule in ("deret-angka", "perbandingan-kuantitatif", "soal-cerita", "persentase-rasio"):
            return 2  # TIU (CPNS)
        if submodule in ("hitung-cepat", "tabel-grafik"):
            return 4  # TKD_BUMN (BUMN)
        if submodule in ("logaritma", "matematika-berpola", "soal-teknikal"):
            return 7  # KEDINASAN_TPA (Kedinasan)
        if submodule == "aritmetika":
            counters["aritmetika"] += 1
            return 4 if counters["aritmetika"] % 2 == 0 else 7

    if module == "logic":
        if submodule in ("silogisme", "deret-angka", "deret-huruf"):
            return 2  # TIU (CPNS)
        if submodule in ("penalaran-jadwal", "penalaran-logis-campuran", "logika-diagram-venn"):
            return 4  # TKD_BUMN (BUMN)
        return 8  # PSIKO_LOGIKA (Psikotes)

    return 8


def load_source_items() -> tuple[list, str]:
    if V289_JSON_PATH.exists():
        with open(V289_JSON_PATH, "r", encoding="utf-8") as f:
            raw = json.load(f)
        items = raw["items"] if isinstance(raw, dict) and "items" in raw else raw
        return items, str(V289_JSON_PATH)

    if FALLBACK_HTML_PATH.exists():
        with open(FALLBACK_HTML_PATH, "r", encoding="utf-8", errors="ignore") as f:
            html_text = f.read()
        m = re.search(r"const STARTER_DB=(\{.*?\});", html_text, re.S) or re.search(r"const STARTER_DB=(\{.*?\})\n", html_text, re.S)
        if not m:
            raise RuntimeError("Gagal menemukan objek STARTER_DB di HTML")
        starter_db = json.loads(m.group(1))
        return starter_db.get("items", []), str(FALLBACK_HTML_PATH)

    raise FileNotFoundError("Tidak menemukan sumber bank soal V2.89 maupun V2.50.")


def main():
    with open(JSON_PATH, "r", encoding="utf-8") as f:
        existing_db = json.load(f)

    # Sinkronisasi metadata Paket 1 -> "Tryout CPNS" & passing grade BKN di all_questions.json
    for pkg in existing_db.get("packages", []):
        if pkg and pkg[0] == 1:
            pkg[1] = "Tryout CPNS"
            pkg[2] = "cpns"
            pkg[3] = "Simulasi resmi CAT BKN lengkap 110 butir soal acak dari bank soal: TWK (30 soal, PG 65), TIU (35 soal, PG 80), dan TKP (45 soal, PG 166) dalam 100 menit sesuai standar BKN."
            pkg[4] = 100

    for cat in existing_db.get("categories", []):
        if cat and cat[1] == "TWK":
            cat[5] = 65
        elif cat and cat[1] == "TIU":
            cat[5] = 80
        elif cat and cat[1] == "TKP":
            cat[5] = 166

    existing_questions = existing_db.get("questions", [])
    seen_texts = {normalize_text(q["question_text"]) for q in existing_questions}
    print(f"Soal yang sudah ada di all_questions.json: {len(existing_questions)}")

    items, src_label = load_source_items()
    print(f"Sumber bank soal: {src_label}")
    print(f"Total item mentah: {len(items)}")

    counters = {"ejaan": 0, "aritmetika": 0}
    added_by_cat = Counter()
    skipped = Counter()
    new_questions = []

    for x in items:
        mod = str(x.get("module") or "").strip().lower()
        sub = str(x.get("submodule") or "").strip().lower()

        if mod in NON_CAT_MODULES:
            skipped[f"non_cat_{mod}"] += 1
            continue

        # Lewati semua item yang bergantung pada gambar/HTML visual
        if x.get("image") or x.get("options_html"):
            skipped["has_image_or_options_html"] += 1
            continue

        prompt_html = str(x.get("prompt_html") or "")
        if prompt_html:
            # Untuk modul verbal, prompt_html hanya berisi <b><i> highlight sementara prompt plain text lengkap
            if mod != "verbal" or any(tag in prompt_html.lower() for tag in ("<svg", "<img", "<table", "<div", "<canvas")):
                skipped["has_prompt_html_visual"] += 1
                continue

        prompt = str(x.get("prompt") or "").strip()
        if not prompt or len(prompt) < 6:
            skipped["empty_prompt"] += 1
            continue

        low_prompt = prompt.lower()
        if any(phrase in low_prompt for phrase in VISUAL_PHRASES):
            skipped["visual_reference"] += 1
            continue

        opts = x.get("options") or []
        ans = x.get("answer")
        scores = x.get("scores")
        is_weighted = isinstance(scores, list) and len(scores) >= 4 and sum(int(s) for s in scores[:5]) > 0

        if not isinstance(opts, list) or len(opts) < 4:
            skipped["invalid_options"] += 1
            continue
        if not is_weighted and (not isinstance(ans, int) or not (0 <= ans < len(opts))):
            skipped["invalid_answer"] += 1
            continue

        opt_a = str(opts[0]).strip()
        opt_b = str(opts[1]).strip()
        opt_c = str(opts[2]).strip()
        opt_d = str(opts[3]).strip()
        opt_e = str(opts[4]).strip() if len(opts) > 4 else ""
        if not opt_a or not opt_b or not opt_c or not opt_d:
            skipped["empty_option_text"] += 1
            continue

        norm = normalize_text(prompt)
        if norm in seen_texts:
            skipped["duplicate"] += 1
            continue
        seen_texts.add(norm)

        cat_id = map_category(mod, sub, counters)

        if is_weighted:
            correct_opt = None
            sa = int(scores[0])
            sb = int(scores[1])
            sc = int(scores[2])
            sd = int(scores[3])
            se = int(scores[4]) if len(scores) > 4 else 0
        else:
            correct_opt = LETTERS[ans] if ans < 5 else "A"
            sa = sb = sc = sd = se = 0

        explanation = str(x.get("explanation") or "").strip()
        if not explanation:
            if correct_opt:
                explanation = f"Jawaban yang tepat adalah opsi {correct_opt}."
            else:
                explanation = "Pilih opsi yang paling mencerminkan sikap profesional, integritas, dan solusi terbaik."

        new_questions.append({
            "category_id": cat_id,
            "package_id": None,  # Masuk ke pool Bank Soal Umum (langsung teracak di Tryout & Latihan Mandiri)
            "question_text": prompt,
            "option_a": opt_a,
            "option_b": opt_b,
            "option_c": opt_c,
            "option_d": opt_d,
            "option_e": opt_e,
            "correct_option": correct_opt,
            "score_a": sa,
            "score_b": sb,
            "score_c": sc,
            "score_d": sd,
            "score_e": se,
            "explanation": explanation,
        })
        added_by_cat[cat_id] += 1

    existing_db["questions"] = existing_questions + new_questions

    with open(JSON_PATH, "w", encoding="utf-8") as f:
        json.dump(existing_db, f, ensure_ascii=False, indent=2)

    print(f"\nBerhasil menambahkan {len(new_questions)} soal baru!")
    print(f"Total soal di all_questions.json sekarang: {len(existing_db['questions'])}")

    cat_names = {c[0]: f"{c[1]} ({c[3]})" for c in existing_db["categories"]}
    total_by_cat = Counter(q["category_id"] for q in existing_db["questions"])

    print("\nDistribusi tambahan & total akhir per kategori:")
    for cid in sorted(cat_names.keys()):
        print(f"  ID {cid:2d} [{cat_names[cid]:30s}]: +{added_by_cat[cid]:4d} -> total {total_by_cat[cid]:4d}")
    print("\nAlasan dilewati (skipped):", dict(skipped))


if __name__ == "__main__":
    main()
