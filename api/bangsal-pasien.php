<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

$kd_bangsal = $_GET['kd_bangsal'] ?? '';
$kd_bangsal = escape($kd_bangsal);

// “Ranap aktif” versi rule: stts_pulang='-' dan jam_keluar='00:00:00'
$rows = q("
  SELECT 
    kamar_inap.kd_kamar,
    pasien.nm_pasien,
    pasien.no_rkm_medis,
    kamar_inap.tgl_masuk,
    kamar_inap.jam_masuk,
    kamar_inap.tgl_keluar,
    kamar_inap.jam_keluar
  FROM kamar_inap
  INNER JOIN kamar ON kamar.kd_kamar = kamar_inap.kd_kamar
  INNER JOIN bangsal ON bangsal.kd_bangsal = kamar.kd_bangsal
  INNER JOIN reg_periksa ON reg_periksa.no_rawat = kamar_inap.no_rawat
  INNER JOIN pasien ON pasien.no_rkm_medis = reg_periksa.no_rkm_medis
  WHERE bangsal.kd_bangsal = '$kd_bangsal'
    AND kamar_inap.stts_pulang = '-'
    AND kamar_inap.jam_keluar = '00:00:00'
  ORDER BY kamar_inap.tgl_masuk DESC, kamar_inap.jam_masuk DESC
  LIMIT 300
");

header('Content-Type: application/json');
echo json_encode([
  'rows' => $rows
]);
