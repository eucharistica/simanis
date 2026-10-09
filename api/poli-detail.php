<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

$kd_poli = $_GET['kd_poli'] ?? '';
$tgl1    = $_GET['tgl1'] ?? date('Y-m-d');
$tgl2    = $_GET['tgl2'] ?? date('Y-m-d');

// sanitasi sederhana
$kd_poli = substr($kd_poli, 0, 10);
$tgl1    = substr($tgl1, 0, 10);
$tgl2    = substr($tgl2, 0, 10);

if ($kd_poli === '') {
    echo json_encode(['rows' => []]);
    exit;
}

$sql = "
    SELECT
        reg_periksa.no_reg,
        reg_periksa.no_rkm_medis,
        pasien.nm_pasien,
        reg_periksa.tgl_registrasi,
        dokter.nm_dokter,
        penjab.png_jawab
    FROM reg_periksa
    LEFT JOIN pasien ON pasien.no_rkm_medis = reg_periksa.no_rkm_medis
    LEFT JOIN dokter ON dokter.kd_dokter = reg_periksa.kd_dokter
    LEFT JOIN penjab ON penjab.kd_pj = reg_periksa.kd_pj
    WHERE reg_periksa.kd_poli = '$kd_poli'
      AND reg_periksa.status_lanjut = 'Ralan'
      AND reg_periksa.stts != 'Batal'
      AND reg_periksa.tgl_registrasi BETWEEN '$tgl1' AND '$tgl2'
    ORDER BY reg_periksa.tgl_registrasi DESC, reg_periksa.no_reg DESC
";

$rows = [];
$res  = q($sql);
foreach ($res as $r) {
    $rows[] = $r;
}

echo json_encode(['rows' => $rows]);
