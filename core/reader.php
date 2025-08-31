<?php
if (!defined('ABSPATH')) exit;

function jwi_read_rows(string $file_path, string $ext): array {
    $rows = [];

    if ($ext === 'csv') {
        $fh = fopen($file_path, 'r');
        if (!$fh) return [];
        $headers = fgetcsv($fh);
        if (!$headers) return [];
        while (($r = fgetcsv($fh)) !== false) {
            $r = array_pad($r, count($headers), '');
            $rows[] = array_combine($headers, $r);
        }
        fclose($fh);

    } elseif ($ext === 'xlsx' && class_exists('SimpleXLSX')) {
        $xlsx = SimpleXLSX::parse($file_path);
        if (!$xlsx) return [];
        $sheet = $xlsx->rows();
        if (empty($sheet) || empty($sheet[0])) return [];
        $headers = $sheet[0];
        for ($i = 1; $i < count($sheet); $i++) {
            $row = array_pad($sheet[$i], count($headers), '');
            $rows[] = array_combine($headers, $row);
        }
    }

    return $rows;
}

function jwi_apply_renames(array $rows, array $renames): array {
    $map = [];
    foreach ($renames as $old => $new) $map[$old] = $new;

    $out = [];
    foreach ($rows as $row) {
        $new = [];
        foreach ($row as $k => $v) {
            $nk = $map[$k] ?? $k;
            $new[$nk] = $v;
        }
        $out[] = $new;
    }
    return $out;
}
