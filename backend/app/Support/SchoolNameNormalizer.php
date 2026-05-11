<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Menjadikan variasi penulisan nama sekolah/instansi konvergen ke satu kunci
 * kanonik agar clustering batch & fuzzy-match autocomplete tahan typo.
 *
 * Contoh keluaran sama untuk: "SMP Negeri 1 Cilandak", "SMPN 1 Cilandak",
 * "SMP N 1 Cilandak", "SMPN1 CILANDAK"  → "smpn 1 cilandak".
 */
class SchoolNameNormalizer
{
    /**
     * Diurutkan dari frasa terpanjang ke terpendek agar tidak terjadi
     * substitusi parsial (mis. "smp negeri" diproses dulu sebelum "negeri").
     *
     * @var array<string, string>
     */
    private const SYNONYMS = [
        'sekolah menengah pertama negeri' => 'smpn',
        'sekolah menengah pertama swasta' => 'smps',
        'sekolah menengah atas negeri' => 'sman',
        'sekolah menengah atas swasta' => 'smas',
        'sekolah menengah kejuruan negeri' => 'smkn',
        'sekolah menengah kejuruan swasta' => 'smks',
        'sekolah dasar negeri' => 'sdn',
        'sekolah dasar swasta' => 'sds',
        'madrasah ibtidaiyah negeri' => 'min',
        'madrasah ibtidaiyah swasta' => 'mis',
        'madrasah tsanawiyah negeri' => 'mtsn',
        'madrasah tsanawiyah swasta' => 'mts',
        'madrasah aliyah negeri' => 'man',
        'madrasah aliyah swasta' => 'ma',
        'pondok pesantren' => 'pondok',
        'sekolah menengah pertama' => 'smp',
        'sekolah menengah atas' => 'sma',
        'sekolah menengah kejuruan' => 'smk',
        'sekolah dasar' => 'sd',
        'madrasah tsanawiyah' => 'mts',
        'madrasah ibtidaiyah' => 'mi',
        'madrasah aliyah' => 'ma',
        'smp negeri' => 'smpn',
        'smp swasta' => 'smps',
        'sma negeri' => 'sman',
        'sma swasta' => 'smas',
        'smk negeri' => 'smkn',
        'smk swasta' => 'smks',
        'sd negeri' => 'sdn',
        'sd swasta' => 'sds',
        'mts negeri' => 'mtsn',
        'mi negeri' => 'min',
        'ma negeri' => 'man',
        'smp n' => 'smpn',
        'sma n' => 'sman',
        'smk n' => 'smkn',
        'sd n' => 'sdn',
        'mts n' => 'mtsn',
        'mi n' => 'min',
        'ma n' => 'man',
    ];

    /**
     * @return string|null  Versi kanonik (lowercase, ringkas), atau null kalau input kosong.
     */
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $value = trim($raw);
        if ($value === '') {
            return null;
        }

        $value = Str::ascii($value);
        $value = Str::lower($value);

        // Buang tanda baca selain alfanumerik & spasi.
        $value = preg_replace('/[^a-z0-9\s]+/u', ' ', $value) ?? $value;

        // Collapse whitespace ganda jadi tunggal.
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $padded = ' ' . $value . ' ';
        foreach (self::SYNONYMS as $needle => $replacement) {
            $padded = str_replace(' ' . $needle . ' ', ' ' . $replacement . ' ', $padded);
        }
        $value = trim($padded);

        // Sapuan kedua agar "SMP Negeri  1" -> "smpn 1" tetap rapi
        // bila masih ada sisa spasi ganda akibat substitusi sinonim.
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return $value === '' ? null : $value;
    }

    /**
     * Similarity 0..1 antara dua string apapun. Kedua input dinormalisasi dulu
     * supaya skor merefleksikan kesamaan setelah singkatan diharmonisasi.
     */
    public static function similarity(?string $a, ?string $b): float
    {
        $na = self::normalize($a);
        $nb = self::normalize($b);

        if ($na === null || $nb === null || $na === '' || $nb === '') {
            return 0.0;
        }

        if ($na === $nb) {
            return 1.0;
        }

        // similar_text() di PHP mengembalikan persentase 0..100.
        similar_text($na, $nb, $percent);

        return round($percent / 100, 4);
    }
}
