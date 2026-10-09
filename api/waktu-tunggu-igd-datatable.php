<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

function post($k, $default=null){ return $_POST[$k] ?? $default; }
function asDate($s): string {
  $s = substr((string)$s, 0, 10);
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) ? $s : date('Y-m-d');
}
function toArray($v): array {
  if (is_array($v)) return $v;
  if ($v === null || $v === '') return [];
  return [$v];
}

$draw   = (int)post('draw', 1);
$start  = max(0, (int)post('start', 0));
$length = (int)post('length', 25000);
if ($length <= 0) $length = 25000;

$colMap = [
  0  => "a.tgl_registrasi",
  1  => "a.jam_reg",
  2  => "pasien.no_rkm_medis",
  3  => "pasien.nm_pasien",
  4  => "pasien.jk",
  5  => "a.tgl_registrasi",
  6  => "dokter.nm_dokter",
  7  => "penjab.png_jawab",
  8  => "a.stts",
  9  => "dt.tgl_kunjungan",
  10 => "pak.tanggal",
  11 => "am.tanggal",
  12 => "interval_reg_triase",
  13 => "interval_triase_keperawatan",
  14 => "interval_keperawatan_medis",
  15 => "diagnosa_pasien",
];

$orderColIdx = (int)($_POST['order'][0]['column'] ?? 0);
$orderDir    = strtolower($_POST['order'][0]['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
$orderBy     = $colMap[$orderColIdx] ?? "a.tgl_registrasi";

$search = trim((string)($_POST['search']['value'] ?? ''));

$tgl1 = asDate(post('startDate', date('Y-m-d')));
$tgl2 = asDate(post('endDate', date('Y-m-d')));

$stts      = array_filter(array_map('trim', toArray(post('status', []))));
$caraBayar = array_filter(array_map('trim', toArray(post('caraBayar', []))));

$fromJoin = "
FROM reg_periksa a
INNER JOIN penjab ON penjab.kd_pj = a.kd_pj
INNER JOIN pasien ON pasien.no_rkm_medis = a.no_rkm_medis
INNER JOIN poliklinik ON poliklinik.kd_poli = a.kd_poli
INNER JOIN dokter ON dokter.kd_dokter = a.kd_dokter
LEFT JOIN data_triase_igd dt ON dt.no_rawat = a.no_rawat
LEFT JOIN penilaian_awal_keperawatan_igd pak ON pak.no_rawat = a.no_rawat
LEFT JOIN asesmen_medis_igd am ON am.no_rawat = a.no_rawat
LEFT JOIN diagnosa_pasien d ON d.no_rawat = a.no_rawat AND d.prioritas = '1' AND d.kd_penyakit NOT LIKE 'Z%'
LEFT JOIN penyakit p ON p.kd_penyakit = d.kd_penyakit
";

$where = [];
$where[] = "a.status_lanjut = 'Ralan'";
$where[] = "a.tgl_registrasi BETWEEN '$tgl1' AND '$tgl2'";
$where[] = "a.stts != 'Batal'";
$where[] = "(a.kd_poli = 'IGDK' OR poliklinik.nm_poli LIKE '%IGD%')";

if ($stts) {
  $in = "'" . implode("','", array_map('escape', $stts)) . "'";
  $where[] = "a.stts IN ($in)";
}
if ($caraBayar) {
  $in = "'" . implode("','", array_map('escape', $caraBayar)) . "'";
  $where[] = "a.kd_pj IN ($in)";
}

$whereBaseSql = "WHERE " . implode(" AND ", $where);

$whereWithSearch = $where;
if ($search !== '') {
  $s = escape($search);
  $whereWithSearch[] =
    "("
    . "pasien.no_rkm_medis LIKE '%$s%' OR "
    . "pasien.nm_pasien LIKE '%$s%' OR "
    . "dokter.nm_dokter LIKE '%$s%' OR "
    . "penjab.png_jawab LIKE '%$s%' OR "
    . "p.nm_penyakit LIKE '%$s%' OR "
    . "d.kd_penyakit LIKE '%$s%' OR "
    . "poliklinik.nm_poli LIKE '%$s%'"
    . ")";
}
$whereSearchSql = "WHERE " . implode(" AND ", $whereWithSearch);

$recordsTotal = (int)(fetch_assoc(query("
  SELECT COUNT(*) as jml FROM (
    SELECT a.no_rawat
    $fromJoin
    $whereBaseSql
    GROUP BY a.no_rawat
  ) x
"))['jml'] ?? 0);

$recordsFiltered = (int)(fetch_assoc(query("
  SELECT COUNT(*) as jml FROM (
    SELECT a.no_rawat
    $fromJoin
    $whereSearchSql
    GROUP BY a.no_rawat
  ) x
"))['jml'] ?? 0);

$sql = "
SELECT
  a.tgl_registrasi,
  a.jam_reg,
  pasien.no_rkm_medis,
  pasien.nm_pasien,
  pasien.jk,
  CASE
    WHEN TIMESTAMPDIFF(MONTH, pasien.tgl_lahir, a.tgl_registrasi) < 1
      THEN CONCAT(TIMESTAMPDIFF(DAY, pasien.tgl_lahir, a.tgl_registrasi), ' Hr')
    WHEN TIMESTAMPDIFF(YEAR, pasien.tgl_lahir, a.tgl_registrasi) < 1
      THEN CONCAT(TIMESTAMPDIFF(MONTH, pasien.tgl_lahir, a.tgl_registrasi), ' Bl')
    ELSE CONCAT(TIMESTAMPDIFF(YEAR, pasien.tgl_lahir, a.tgl_registrasi), ' Th')
  END AS umur_status,
  dokter.nm_dokter,
  penjab.png_jawab AS cara_bayar,
  a.stts AS status_pasien,

  IFNULL(DATE_FORMAT(dt.tgl_kunjungan, '%H:%i:%s'), '-') AS jam_triase,
  IFNULL(DATE_FORMAT(pak.tanggal, '%H:%i:%s'), '-') AS jam_awal_keperawatan,
  IFNULL(DATE_FORMAT(am.jam, '%H:%i:%s'), '-') AS jam_asesmen_medis,

  CASE
      WHEN COALESCE(pak.tanggal, dt.tgl_kunjungan) IS NOT NULL
      THEN TIMESTAMPDIFF(
        MINUTE,
        CONCAT(a.tgl_registrasi, ' ', a.jam_reg),
        COALESCE(pak.tanggal, dt.tgl_kunjungan)
      )
      ELSE NULL
  END AS interval_reg_perawatan,
  IFNULL(CONCAT(d.kd_penyakit,' - ',p.nm_penyakit), '-') AS diagnosa_pasien
$fromJoin
$whereSearchSql
GROUP BY a.no_rawat
ORDER BY $orderBy $orderDir
LIMIT $start, $length
";

$rows = q($sql);

foreach ($rows as &$r) {
  foreach (['interval_reg_triase','interval_triase_keperawatan','interval_keperawatan_medis'] as $k) {
    if (!isset($r[$k]) || $r[$k] === null || $r[$k] === '') {
      $r[$k] = '-';
    }
  }
}
unset($r);

echo json_encode([
  'draw' => $draw,
  'recordsTotal' => $recordsTotal,
  'recordsFiltered' => $recordsFiltered,
  'data' => $rows,
]);
