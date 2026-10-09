<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

// Exclude poli – copy dari dashboard
$exclude_poli = ["U0015", "U0016", "U0035", "U0033", "U0041", "U0036"];
$exclude_list = "'" . implode("','", $exclude_poli) . "'";

$tgl1 = $_GET['tgl1'] ?? date('Y-m-d');
$tgl2 = $_GET['tgl2'] ?? date('Y-m-d');

// sanitasi simple (format yyyy-mm-dd)
$tgl1 = substr($tgl1, 0, 10);
$tgl2 = substr($tgl2, 0, 10);

$data = q("
  SELECT COUNT(*) as jml,
         IFNULL(poliklinik.nm_poli,'Poliklinik Lain') as nm_poli,
         reg_periksa.kd_poli
  FROM reg_periksa
  LEFT JOIN poliklinik ON poliklinik.kd_poli = reg_periksa.kd_poli
  WHERE reg_periksa.kd_poli NOT IN ($exclude_list)
    AND status_lanjut='Ralan'
    AND stts!='Batal'
    AND tgl_registrasi BETWEEN '$tgl1' AND '$tgl2'
  GROUP BY reg_periksa.kd_poli
  ORDER BY jml DESC
");

$labels = [];
$values = [];
$kd_poli = [];

foreach ($data as $r) {
  $labels[] = $r['nm_poli'];
  $values[] = (int)$r['jml'];
  $kd_poli[] = $r['kd_poli'];
}

header('Content-Type: application/json');
echo json_encode([
  'labels'   => $labels,
  'data'     => $values,
  'kd_poli'  => $kd_poli,
]);
