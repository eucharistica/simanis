<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

$pk1 = $_GET['pk1'] ?? date('Y-m-d');
$pk2 = $_GET['pk2'] ?? date('Y-m-d');

$pk1 = substr($pk1, 0, 10);
$pk2 = substr($pk2, 0, 10);

$data = q("
  SELECT COUNT(*) as jml,
         b.nm_bangsal,
         b.kd_bangsal
  FROM kamar_inap ki
  INNER JOIN kamar   k ON k.kd_kamar   = ki.kd_kamar
  INNER JOIN bangsal b ON b.kd_bangsal = k.kd_bangsal
  WHERE ki.tgl_keluar BETWEEN '$pk1' AND '$pk2'
    AND ki.tgl_keluar <> '0000-00-00'
  GROUP BY b.kd_bangsal
  ORDER BY jml DESC
");

$labels = [];
$values = [];
$kd_bangsal = [];

foreach ($data as $r) {
  $labels[]    = $r['nm_bangsal'];
  $values[]    = (int)$r['jml'];
  $kd_bangsal[] = $r['kd_bangsal'];
}

header('Content-Type: application/json');
echo json_encode([
  'labels'      => $labels,
  'data'        => $values,
  'kd_bangsal'  => $kd_bangsal,
]);
