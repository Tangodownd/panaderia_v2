<?php

namespace App\Support;

use Carbon\Carbon;

class DateRanges
{
    public static function parse(string $text): array
    {
        $t = mb_strtolower(trim($text));
        $now = Carbon::now();

        if (preg_match('/\bhoy\b/', $t)) {
            return [$now->copy()->startOfDay(), $now->copy()->endOfDay()];
        }
        if (preg_match('/\bayer\b/', $t)) {
            $y = $now->copy()->subDay();
            return [$y->startOfDay(), $y->endOfDay()];
        }
        if (preg_match('/\best(a|e)\s+semana\b/', $t)) {
            return [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()];
        }
        if (preg_match('/\bsemana\s+pasada\b/', $t)) {
            $w = $now->copy()->subWeek();
            return [$w->startOfWeek(), $w->endOfWeek()];
        }
        if (preg_match('/\best(e|a)\s+mes\b/', $t)) {
            return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
        }
        if (preg_match('/\bmes\s+pasado\b/', $t)) {
            $m = $now->copy()->subMonth();
            return [$m->startOfMonth(), $m->endOfMonth()];
        }
        // Rango explícito: 2025-09-01 al 2025-09-07
        if (preg_match('/(\d{4}-\d{2}-\d{2})\s*(?:al|-|a)\s*(\d{4}-\d{2}-\d{2})/', $t, $m)) {
            $f = Carbon::parse($m[1])->startOfDay();
            $to= Carbon::parse($m[2])->endOfDay();
            return [$f,$to];
        }
        // Por defecto 30 días
        return [$now->copy()->subDays(30)->startOfDay(), $now->copy()->endOfDay()];
    }
}
