<?php
declare(strict_types=1);
require_once '../config.php';

$kd_poli = $_GET['kd_poli'] ?? '';
$tgl1 = $_GET['tgl1'] ?? date('Y-m-d');
$tgl2 = $_GET['tgl2'] ?? date('Y-m-d');

// Optimized query + LIMIT 25 dokter terbanyak
$data = q("SELECT COUNT(*) as jml, IFNULL(dokter.nm_dokter, 'Dokter') as nm_dokter 
           FROM reg_periksa 
           LEFT JOIN dokter ON dokter.kd_dokter = reg_periksa.kd_dokter 
           WHERE reg_periksa.kd_poli = '$kd_poli' 
           AND status_lanjut = 'Ralan' AND stts != 'Batal' 
           AND tgl_registrasi BETWEEN '$tgl1' AND '$tgl2'
           GROUP BY reg_periksa.kd_dokter 
           ORDER BY jml DESC LIMIT 25");

$labels = []; 
$values = [];
foreach ($data as $row) {
    $labels[] = $row['nm_dokter'];
    $values[] = (int)$row['jml'];
}

header('Content-Type: application/json');
echo json_encode([
    'labels' => $labels,
    'datasets' => [[
        'label' => 'Kunjungan',
        'data' => $values,
        'backgroundColor' => 'rgba(13,110,253,0.8)',
        'borderColor' => '#0d6efd',
        'borderWidth' => 1
    ]]
]);
?>
