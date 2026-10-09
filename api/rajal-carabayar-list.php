<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

$rows = q("SELECT kd_pj, png_jawab FROM penjab WHERE status='1' and kd_pj not in ('K01','K02','K03') ORDER BY png_jawab ASC");

$out = [];
foreach ($rows as $r) {
  $out[] = ['value' => $r['kd_pj'], 'label' => $r['png_jawab']];
}
echo json_encode($out);
