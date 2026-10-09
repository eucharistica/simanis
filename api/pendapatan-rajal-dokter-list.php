<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

function validDate(string $value, string $fallback): string {
  $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
  return $date && $date->format('Y-m-d') === $value ? $value : $fallback;
}

try {
  $tgl1 = validDate(substr((string)($_GET['tgl1'] ?? ''), 0, 10), date('Y-m-01'));
  $tgl2 = validDate(substr((string)($_GET['tgl2'] ?? ''), 0, 10), date('Y-m-d'));
  $kdPoli = trim((string)($_GET['kd_poli'] ?? ''));

  $sql = "SELECT DISTINCT d.kd_dokter, d.nm_dokter
          FROM reg_periksa rp
          INNER JOIN dokter d ON d.kd_dokter = rp.kd_dokter
          WHERE rp.status_lanjut = 'Ralan'
            AND rp.tgl_registrasi BETWEEN :tgl1 AND :tgl2";
  $params = [':tgl1'=>$tgl1, ':tgl2'=>$tgl2];
  if ($kdPoli !== '') {
    $sql .= ' AND rp.kd_poli = :kd_poli';
    $params[':kd_poli'] = $kdPoli;
  }
  $sql .= ' ORDER BY d.nm_dokter';

  $stmt = pdo()->prepare($sql);
  $stmt->execute($params);
  echo json_encode(['data'=>$stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  error_log('pendapatan-rajal-dokter-list: ' . $e->getMessage());
  echo json_encode(['data'=>[], 'message'=>'Gagal mengambil daftar dokter.'], JSON_UNESCAPED_UNICODE);
}
