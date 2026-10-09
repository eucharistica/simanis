<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

function post($k,$d=null){ return $_POST[$k] ?? $d; }
function asDate($s): string {
  $s = substr((string)$s, 0, 10);
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) ? $s : date('Y-m-d');
}

$type = post('type','rajal');
$draw = (int)post('draw', 1);
$start = max(0, (int)post('start', 0));
$length = (int)post('length', 500);
if ($length <= 0) $length = 500;
if ($length > 50) $length = 500;

$tgl1 = asDate(post('tgl1', date('Y-m-d')));
$tgl2 = asDate(post('tgl2', date('Y-m-d')));
$kd_poli = array_filter(array_map('trim', (array)post('kd_poli', [])));
$kd_bangsal = array_filter(array_map('trim', (array)post('kd_bangsal', [])));

$exclude_poli = ['U0015','U0016','U0035','U0021','U0012'];
$exclude_list = "'" . implode("','", $exclude_poli) . "'";

$status_lanjut = $type === 'ranap' ? "'Ranap'" : "'Ralan'";

// FROM JOIN berdasarkan type
$fromJoin = $type === 'ranap' ? "
  FROM reg_periksa a
  INNER JOIN kamar_inap ki ON ki.no_rawat = a.no_rawat
  INNER JOIN kamar k ON k.kd_kamar = ki.kd_kamar
  INNER JOIN bangsal b ON b.kd_bangsal = k.kd_bangsal
  INNER JOIN diagnosa_pasien dp ON a.no_rawat = dp.no_rawat
  INNER JOIN penyakit p ON p.kd_penyakit = dp.kd_penyakit
" : "
  FROM reg_periksa a
  INNER JOIN diagnosa_pasien dp ON a.no_rawat = dp.no_rawat
  INNER JOIN penyakit p ON p.kd_penyakit = dp.kd_penyakit
";

// WHERE conditions
$where = [];
$where[] = "a.tgl_registrasi BETWEEN '$tgl1' AND '$tgl2'";
$where[] = "a.kd_poli NOT IN ($exclude_list)";
$where[] = "a.status_lanjut = $status_lanjut";
$where[] = "dp.kd_penyakit NOT LIKE 'Z%'";

if ($type === 'rajal' && $kd_poli) {
  $in = "'" . implode("','", array_map('escape', $kd_poli)) . "'";
  $where[] = "a.kd_poli IN ($in)";
}
if ($type === 'ranap' && $kd_bangsal) {
  $in = "'" . implode("','", array_map('escape', $kd_bangsal)) . "'";
  $where[] = "b.kd_bangsal IN ($in)";
}

$whereSql = "WHERE " . implode(" AND ", $where);

// COUNT TOTAL UNIQUE ICD10
$total_all = (int)(fetch_assoc(query("
  SELECT COUNT(DISTINCT dp.kd_penyakit) as jml
  $fromJoin
  $whereSql
"))['jml'] ?? 0);

// MAIN QUERY
$sql = "
SELECT 
  COUNT(*) as total,
  dp.kd_penyakit as icd10,
  p.nm_penyakit as penyakit
$fromJoin
$whereSql
GROUP BY dp.kd_penyakit, p.nm_penyakit
ORDER BY total DESC
LIMIT $start, $length
";

$data = q($sql);

echo json_encode([
  'draw' => $draw,
  'recordsTotal' => $total_all,
  'recordsFiltered' => $total_all,
  'data' => $data,
]);
?>
