#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import json

# Load existing 140 questions
with open('database/existing_140.json', 'r', encoding='utf-8') as f:
    existing = json.load(f)

# Categories definition
categories = [
    {
        'id': 1, 'code': 'TWK', 'name': 'Tes Wawasan Kebangsaan (TWK)',
        'category_group': 'cpns', 'passing_grade': 65,
        'description': 'Menguji pilar kebangsaan: Pancasila, UUD 1945, NKRI, Bhinneka Tunggal Ika, integritas, dan bela negara.'
    },
    {
        'id': 2, 'code': 'TIU', 'name': 'Tes Intelegensia Umum (TIU)',
        'category_group': 'cpns', 'passing_grade': 80,
        'description': 'Menguji kemampuan verbal (analogi, silogisme, analitis), numerik (berhitung, deret angka, soal cerita), dan figural.'
    },
    {
        'id': 3, 'code': 'TKP', 'name': 'Tes Karakteristik Pribadi (TKP)',
        'category_group': 'cpns', 'passing_grade': 166,
        'description': 'Menguji pelayanan publik, jejaring kerja, sosial budaya, TIK, profesionalisme, dan anti-radikalisme (skala bobot 1–5).'
    },
    {
        'id': 4, 'code': 'TKD_BUMN', 'name': 'Tes Kemampuan Dasar BUMN (TKD)',
        'category_group': 'bumn', 'passing_grade': 58,
        'description': 'Menguji kemampuan penalaran logis, verbal analitis, numerik bisnis, dan pemecahan masalah standar Rekrutmen Bersama BUMN.'
    },
    {
        'id': 5, 'code': 'AKHLAK', 'name': 'Core Values AKHLAK BUMN',
        'category_group': 'bumn', 'passing_grade': 65,
        'description': 'Menguji pemahaman dan implementasi nilai inti BUMN: Amanah, Kompeten, Harmonis, Loyal, Adaptif, dan Kolaboratif.'
    },
    {
        'id': 6, 'code': 'ENG_BUMN', 'name': 'Bahasa Inggris BUMN',
        'category_group': 'bumn', 'passing_grade': 60,
        'description': 'Menguji kemampuan Structure & Written Expression, Error Recognition, Vocabulary, dan Reading Comprehension dunia kerja BUMN.'
    },
    {
        'id': 7, 'code': 'KEDINASAN_TPA', 'name': 'TPA & Akademik Kedinasan',
        'category_group': 'kedinasan', 'passing_grade': 70,
        'description': 'Menguji Matematika Terapan, Aljabar Fungsi, Geometri Ruang, Analisis Data, dan Logika Khusus SPMB STAN, STIS, IPDN.'
    },
    {
        'id': 8, 'code': 'PSIKO_LOGIKA', 'name': 'Psikotes Logika & Penalaran Kerja',
        'category_group': 'psikotes', 'passing_grade': 65,
        'description': 'Menguji pola logika deduktif-induktif, penalaran analitis posisi/urutan, dan deret aritmetika untuk asesmen rekrutmen kerja.'
    },
    {
        'id': 9, 'code': 'PSIKO_SPASIAL', 'name': 'Psikotes Spasial & Hubungan Gambar',
        'category_group': 'psikotes', 'passing_grade': 65,
        'description': 'Menguji ketajaman visual, rotasi 3D, pencerminan, matriks gambar figural, dan jaring-jaring bangun ruang.'
    },
    {
        'id': 10, 'code': 'PSIKO_SJT', 'name': 'Situational Judgement Test (SJT)',
        'category_group': 'psikotes', 'passing_grade': 70,
        'description': 'Simulasi pengambilan keputusan manajerial, etika kerja, integritas problem solving, dan perilaku kerja di lingkungan profesional.'
    }
]

packages = [
    {
        'id': 1,
        'title': 'Tryout Akbar SKD CPNS 2024/2025 (Standar BKN)',
        'category_group': 'cpns',
        'description': 'Simulasi resmi CAT BKN lengkap 110 butir soal: TWK (30 soal), TIU (35 soal), TKP (45 soal) dengan passing grade resmi PermenPAN-RB.',
        'duration_minutes': 100,
        'is_premium': 1,
        'price': 49000
    },
    {
        'id': 2,
        'title': 'Tryout Rekrutmen Bersama BUMN (TKD + AKHLAK + English)',
        'category_group': 'bumn',
        'description': 'Simulasi seleksi terpadu BUMN FHCI: Tes Kemampuan Dasar (TKD), Core Values AKHLAK BUMN, dan Bahasa Inggris Dunia Kerja.',
        'duration_minutes': 90,
        'is_premium': 1,
        'price': 49000
    },
    {
        'id': 3,
        'title': 'Tryout Masuk Sekolah Kedinasan (STAN, IPDN, STIS, Poltekip)',
        'category_group': 'kedinasan',
        'description': 'Simulasi seleksi masuk sekolah kedinasan: SKD terpadu + Tes Potensi Akademik (TPA Matematika Terapan, Aljabar & Logika Analitis).',
        'duration_minutes': 90,
        'is_premium': 1,
        'price': 49000
    },
    {
        'id': 4,
        'title': 'Simulasi Asesmen Psikotes Kerja & Rekrutmen Perusahaan',
        'category_group': 'psikotes',
        'description': 'Simulasi psikotes rekrutmen perbankan, BUMN, swasta & startup: Penalaran Logika, Spasial Figural, dan Situational Judgement Test (SJT).',
        'duration_minutes': 60,
        'is_premium': 1,
        'price': 49000
    },
    {
        'id': 5,
        'title': 'Mini Tryout All-in-One Multi-Tes (Trial Percobaan Gratis)',
        'category_group': 'all',
        'description': 'Paket coba gratis 30 butir soal lintas kategori (CPNS, BUMN, Kedinasan, dan Psikotes Kerja) untuk mencoba sistem dan menguji kesiapan awal tanpa biaya.',
        'duration_minutes': 30,
        'is_premium': 0,
        'price': 0
    }
]

print("Setup definitions completed.")
