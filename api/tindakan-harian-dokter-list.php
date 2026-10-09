<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

try {
  $dokter = pdo()->query("SELECT DISTINCT d.kd_dokter AS value, d.nm_dokter AS label
    FROM jadwal j
    INNER JOIN dokter d ON d.kd_dokter=j.kd_dokter
    WHERE d.status='1'
    ORDER BY d.nm_dokter")->fetchAll();
  $caraBayar = pdo()->query("SELECT kd_pj AS value, png_jawab AS label
    FROM penjab
    WHERE status='1' AND kd_pj IN ('A01','BPJ')
    ORDER BY FIELD(kd_pj,'A01','BPJ')")->fetchAll();
  echo json_encode(['ok'=>true, 'dokter'=>$dokter, 'cara_bayar'=>$caraBayar], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  error_log('tindakan-harian-dokter-list: '.$e->getMessage());
  echo json_encode(['ok'=>false, 'message'=>'Gagal mengambil pilihan filter.'], JSON_UNESCAPED_UNICODE);
}
