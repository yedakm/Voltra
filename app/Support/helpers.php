<?php

/**
 * Global view helpers for the Voltra ERP frontend.
 * Ported 1:1 from the design prototype's src/data.jsx helper functions.
 */

if (! function_exists('voltra_month_names')) {
    function voltra_month_names(): array
    {
        return ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    }
}

if (! function_exists('voltra_month_short')) {
    function voltra_month_short(): array
    {
        return ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
            'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    }
}

if (! function_exists('fmtIDR')) {
    function fmtIDR($n): string
    {
        if ($n === null || ! is_numeric($n)) {
            return '—';
        }

        return 'Rp ' . number_format(round((float) $n), 0, ',', '.');
    }
}

if (! function_exists('fmtNum')) {
    function fmtNum($n): string
    {
        if ($n === null || ! is_numeric($n)) {
            return '—';
        }

        return number_format((float) $n, 0, ',', '.');
    }
}

if (! function_exists('fmtDate')) {
    function fmtDate($d): string
    {
        if (! $d) {
            return '—';
        }
        $ts = strtotime((string) $d);
        if ($ts === false) {
            return '—';
        }
        $m = voltra_month_short();

        return date('d', $ts) . ' ' . $m[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
    }
}

if (! function_exists('fmtDateShort')) {
    function fmtDateShort($d): string
    {
        if (! $d) {
            return '—';
        }
        $ts = strtotime((string) $d);
        if ($ts === false) {
            return '—';
        }
        $m = voltra_month_short();

        return date('d', $ts) . ' ' . $m[(int) date('n', $ts) - 1];
    }
}

if (! function_exists('fmtDateTime')) {
    function fmtDateTime($d): string
    {
        if (! $d) {
            return '—';
        }
        $ts = strtotime((string) $d);
        if ($ts === false) {
            return '—';
        }
        $m = voltra_month_short();

        return date('d', $ts) . ' ' . $m[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts)
            . ' ' . date('H.i', $ts);
    }
}

if (! function_exists('lbl')) {
    function lbl($k): string
    {
        return \App\Support\VoltraData::statusLabels()[$k] ?? (string) $k;
    }
}
