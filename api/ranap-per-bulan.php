<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');

$sql = "
  SELECT
    MONTHNAME(a.tgl_masuk) AS bulan,
    COUNT(a.no_rawat)      AS jumlah_pasien,
    MONTH(a.tgl_masuk)     AS bln_num
  FROM kamar_inap a
  WHERE YEAR(a.tgl_masuk) = '$tahun'
    AND a.stts_pulang != 'Pindah Kamar'
  GROUP BY MONTH(a.tgl_masuk)
  ORDER BY bln_num
";

$rows = q($sql);

$bulan = [];
$jumlah = [];
foreach ($rows as $r) {
  $bulan[]   = $r['bulan'];
  $jumlah[]  = (int)$r['jumlah_pasien'];
}

echo json_encode([
  'status'         => '200',
  'bulan'          => $bulan,
  'jumlah_pasien'  => $jumlah,
  'data_pasien'    => $rows,
]);
