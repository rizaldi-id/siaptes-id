#!/usr/bin/env python3
"""
Script untuk mengekstrak ribuan soal dari Bank Soal standalone HTML,
memetakannya ke kategori CPNS, BUMN, Kedinasan, dan Psikotes,
lalu menggabungkannya ke database/all_questions.json.
"""
import json
import re
from collections import Counter
from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent.parent
HTML_PATH = BASE_DIR / "Bank Soal" / "Simulator_Psikotes_DalamNegeri_V2_50_RC1_Standalone.html"
JSON_PATH = BASE_DIR / "database" / "all_questions.json"

LETTERS = ["A", "B", "C", "D", "E"]


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
    if module in ("learning-agility", "sjt"):
        return 10  # PSIKO_SJT (Psikotes)
    if module in ("critical", "attention"):
        return 8  # PSIKO_LOGIKA (Psikotes)

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
        if submodule == "hitung-cepat":
            return 4  # TKD_BUMN (BUMN)
        if submodule in ("logaritma", "matematika-berpola", "soal-teknikal"):
            return 7  # KEDINASAN_TPA (Kedinasan)
        if submodule == "aritmetika":
            counters["aritmetika"] += 1
            return 4 if counters["aritmetika"] % 2 == 0 else 7

    if module == "logic":
        if submodule == "silogisme":
            return 2  # TIU (CPNS)
        if submodule in ("penalaran-jadwal", "penalaran-logis-campuran"):
            return 4  # TKD_BUMN (BUMN)
        return 8  # PSIKO_LOGIKA (Psikotes)

    return 8


def main():
    if not HTML_PATH.exists():
        raise FileNotFoundError(f"File tidak ditemukan: {HTML_PATH}")

    with open(JSON_PATH, "r", encoding="utf-8") as f:
        existing_db = json.load(f)

    existing_questions = existing_db.get("questions", [])
    seen_texts = {normalize_text(q["question_text"]) for q in existing_questions}
    print(f"Soal yang sudah ada di all_questions.json: {len(existing_questions)}")

    with open(HTML_PATH, "r", encoding="utf-8", errors="ignore") as f:
        html_text = f.read()

    m = re.search(r"const STARTER_DB=(\{.*?\});", html_text, re.S) or re.search(r"const STARTER_DB=(\{.*?\})\n", html_text, re.S)
    if not m:
        raise RuntimeError("Gagal menemukan objek STARTER_DB di HTML")

    starter_db = json.loads(m.group(1))
    items = starter_db.get("items", [])
    print(f"Total item mentah di Bank Soal HTML: {len(items)}")

    counters = {"ejaan": 0, "aritmetika": 0}
    added_by_cat = Counter()
    skipped = Counter()
    new_questions = []

    for x in items:
        mod = x.get("module", "")
        sub = x.get("submodule", "")

        # Lewati soal visual yang membutuhkan SVG/gambar
        if mod in ("spatial", "colorblind"):
            skipped["visual_module"] += 1
            continue
        if x.get("prompt_html") or x.get("options_html"):
            skipped["has_html_svg"] += 1
            continue

        prompt = str(x.get("prompt") or "").strip()
        if not prompt or len(prompt) < 6:
            skipped["empty_prompt"] += 1
            continue

        low_prompt = prompt.lower()
        if any(phrase in low_prompt for phrase in ("gambar di atas", "tabel di atas", "diagram di atas", "jaring-jaring kubus di atas", "tumpukan kubus di atas")):
            skipped["visual_reference"] += 1
            continue

        opts = x.get("options") or []
        ans = x.get("answer")
        if not isinstance(opts, list) or len(opts) < 4 or not isinstance(ans, int) or not (0 <= ans < len(opts)):
            skipped["invalid_options_or_answer"] += 1
            continue

        norm = normalize_text(prompt)
        if norm in seen_texts:
            skipped["duplicate"] += 1
            continue
        seen_texts.add(norm)

        cat_id = map_category(mod, sub, counters)

        opt_a = str(opts[0]).strip()
        opt_b = str(opts[1]).strip()
        opt_c = str(opts[2]).strip()
        opt_d = str(opts[3]).strip()
        opt_e = str(opts[4]).strip() if len(opts) > 4 else ""

        scores = x.get("scores")
        is_weighted = isinstance(scores, list) and len(scores) >= 4

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
            "package_id": None,  # Masuk ke Bank Soal Mandiri (tidak mengubah kuota paket tryout)
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
    print("\nDistribusi tambahan soal per category_id:")
    cat_names = {c[0]: f"{c[1]} ({c[3]})" for c in existing_db["categories"]}
    for cid, cnt in sorted(added_by_cat.items()):
        print(f"  ID {cid:2d} [{cat_names.get(cid, '')}]: +{cnt}")
    print("\nAlasan dilewati (skipped):", dict(skipped))


if __name__ == "__main__":
    main()
