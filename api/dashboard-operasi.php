<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

function getq($k, $default=null) {
  return $_GET[$k] ?? $default;
}

function asDate($s): string {
  $s = substr((string)$s, 0, 10);
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) ? $s : date('Y-m-d');
}

$start = asDate(getq('start', date('Y-m-d')));
$end   = asDate(getq('end', date('Y-m-d')));

$sql = "
SELECT
  COUNT(*) AS jumlah,
  dokter.nm_dokter
FROM operasi
INNER JOIN dokter ON dokter.kd_dokter = operasi.operator1
WHERE tgl_operasi >= ? AND tgl_operasi < DATE_ADD(?, INTERVAL 1 DAY)
GROUP BY dokter.nm_dokter
ORDER BY jumlah DESC, dokter.nm_dokter ASC
";

$stmt = pdo()->prepare($sql);
$stmt->execute([$start, $end]);
$rows = $stmt->fetchAll();

echo json_encode([
  'success' => true,
  'start'   => $start,
  'end'     => $end,
  'data'    => $rows,
], JSON_UNESCAPED_UNICODE);
