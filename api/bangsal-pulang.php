<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

$kd  = escape($_GET['kd_bangsal'] ?? '');
$pk1 = escape($_GET['pk1'] ?? date('Y-m-d'));
$pk2 = escape($_GET['pk2'] ?? date('Y-m-d'));

$rows = q("
  SELECT ki.kd_kamar, p.nm_pasien, p.no_rkm_medis, ki.tgl_masuk, ki.jam_masuk, ki.tgl_keluar, ki.jam_keluar
  FROM kamar_inap ki
  INNER JOIN kamar k ON k.kd_kamar=ki.kd_kamar
  INNER JOIN bangsal b ON b.kd_bangsal=k.kd_bangsal
  INNER JOIN reg_periksa rp ON rp.no_rawat=ki.no_rawat
  INNER JOIN pasien p ON p.no_rkm_medis=rp.no_rkm_medis
  WHERE b.kd_bangsal='$kd'
    AND ki.tgl_keluar BETWEEN '$pk1' AND '$pk2'
    AND ki.tgl_keluar<>'0000-00-00'
  ORDER BY ki.tgl_keluar DESC, ki.jam_keluar DESC
  LIMIT 300
");

header('Content-Type: application/json');
echo json_encode(['rows'=>$rows]);
