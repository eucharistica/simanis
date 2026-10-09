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
  INNER JOIN prosedur_pasien pp ON a.no_rawat = pp.no_rawat
  INNER JOIN icd9 i ON i.kode = pp.kode
" : "
  FROM reg_periksa a
  INNER JOIN prosedur_pasien pp ON a.no_rawat = pp.no_rawat
  INNER JOIN icd9 i ON i.kode = pp.kode
";

// WHERE conditions
$where = [];
$where[] = "a.tgl_registrasi BETWEEN '$tgl1' AND '$tgl2'";
$where[] = "a.kd_poli NOT IN ($exclude_list)";
$where[] = "a.status_lanjut = $status_lanjut";

if ($type === 'rajal' && $kd_poli) {
  $in = "'" . implode("','", array_map('escape', $kd_poli)) . "'";
  $where[] = "a.kd_poli IN ($in)";
}
if ($type === 'ranap' && $kd_bangsal) {
  $in = "'" . implode("','", array_map('escape', $kd_bangsal)) . "'";
  $where[] = "b.kd_bangsal IN ($in)";
}

$whereSql = "WHERE " . implode(" AND ", $where);

// COUNT TOTAL UNIQUE ICD9
$total_all = (int)(fetch_assoc(query("
  SELECT COUNT(DISTINCT pp.kode) as jml
  $fromJoin
  $whereSql
"))['jml'] ?? 0);

// MAIN QUERY
$sql = "
SELECT 
  COUNT(*) as total,
  pp.kode as icd9,
  i.deskripsi_pendek as prosedur
$fromJoin
$whereSql
GROUP BY pp.kode, i.deskripsi_pendek
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
