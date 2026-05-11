<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Fuzzy School Name Match Threshold
    |--------------------------------------------------------------------------
    |
    | Ambang similarity (0..1) untuk menentukan apakah sebuah kandidat dari
    | katalog `school_suggestions` / `universities` cukup mirip dengan teks
    | yang sedang diketik user. Nilai 0.65 cukup konservatif: typo ringan
    | (≤ 35 % beda karakter) lolos, sementara nama yang tidak relevan
    | tersaring.
    |
    */
    'school_match_threshold' => (float) env('BOOKING_SCHOOL_MATCH_THRESHOLD', 0.65),

];
