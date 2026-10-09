<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

$kd = escape($_GET['kd_bangsal'] ?? '');

$rows = q("
  SELECT ki.kd_kamar, p.nm_pasien, p.no_rkm_medis, ki.tgl_masuk, ki.jam_masuk
  FROM kamar_inap ki
  INNER JOIN kamar k ON k.kd_kamar=ki.kd_kamar
  INNER JOIN bangsal b ON b.kd_bangsal=k.kd_bangsal
  INNER JOIN reg_periksa rp ON rp.no_rawat=ki.no_rawat
  INNER JOIN pasien p ON p.no_rkm_medis=rp.no_rkm_medis
  WHERE b.kd_bangsal='$kd'
    AND ki.stts_pulang='-'
    AND ki.tgl_keluar='0000-00-00'
  ORDER BY ki.tgl_masuk DESC, ki.jam_masuk DESC
  LIMIT 300
");

header('Content-Type: application/json');
echo json_encode(['rows'=>$rows]);
