<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$today = date('Y-m-d');

$sql = "
  SELECT
    MONTHNAME(a.tgl_registrasi) AS bulan,
    COUNT(a.no_rawat)           AS jumlah_pasien,
    MONTH(a.tgl_registrasi)     AS bln_num
  FROM reg_periksa a
  WHERE YEAR(a.tgl_registrasi) = '$tahun'
    AND a.stts != 'Batal'
    AND a.status_lanjut = 'Ralan'
    AND a.tgl_registrasi <= '$today'
  GROUP BY MONTH(a.tgl_registrasi)
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
