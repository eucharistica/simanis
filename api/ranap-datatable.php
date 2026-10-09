<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

function post($k,$d=null){ return $_POST[$k] ?? $d; }
function toArray($v): array {
  if (is_array($v)) return $v;
  if ($v === null || $v === '') return [];
  return [$v];
}
function asDate($s): string {
  $s = substr((string)$s, 0, 10);
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) ? $s : date('Y-m-d');
}

$mode = post('mode','aktif'); // aktif | pulang

$draw   = (int)post('draw', 1);
$start  = max(0, (int)post('start', 0));
$length = (int)post('length', 25);
if ($length <= 0) $length = 25;

$orderColIdx = (int)($_POST['order'][0]['column'] ?? 3);
$orderDir    = strtolower($_POST['order'][0]['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

// mapping kolom (sesuai columns JS nanti)
$colMap = [
  0 => "pasien.no_rkm_medis",
  1 => "pasien.nm_pasien",
  2 => "pasien.jk",
  3 => "umur_status",
  4 => "stts_daftar",
  5 => "bangsal.nm_bangsal",
  6 => "kamar_inap.kd_kamar",
  7 => "kamar_inap.tgl_masuk",
  8 => "kamar_inap.jam_masuk",
  9 => "diagnosa_pasien.kd_penyakit",
  10 => "penyakit.nm_penyakit",
  11 => "kamar_inap.tgl_keluar",
  12 => "kamar_inap.jam_keluar",
  13 => "kamar_inap.stts_pulang",
  14 => "penjab.png_jawab",
  15 => "dokter.nm_dokter",
];
$orderBy = $colMap[$orderColIdx] ?? "kamar_inap.tgl_masuk";

$search = trim((string)($_POST['search']['value'] ?? ''));

// filters
$kd_pj     = array_filter(array_map('trim', toArray(post('kd_pj', []))));
$kd_bangsal= array_filter(array_map('trim', toArray(post('kd_bangsal', []))));
$kd_dokter = array_filter(array_map('trim', toArray(post('kd_dokter', []))));

$dateMode = post('dateMode','keluar'); // masuk|keluar
$tgl1 = asDate(post('tgl1', date('Y-m-d')));
$tgl2 = asDate(post('tgl2', date('Y-m-d')));

$fromJoin = "
FROM kamar_inap
INNER JOIN kamar ON kamar.kd_kamar = kamar_inap.kd_kamar
INNER JOIN bangsal ON bangsal.kd_bangsal = kamar.kd_bangsal
INNER JOIN reg_periksa ON reg_periksa.no_rawat = kamar_inap.no_rawat
INNER JOIN pasien ON pasien.no_rkm_medis = reg_periksa.no_rkm_medis
INNER JOIN penjab ON penjab.kd_pj = reg_periksa.kd_pj
INNER JOIN dokter ON dokter.kd_dokter = reg_periksa.kd_dokter
LEFT JOIN diagnosa_pasien ON diagnosa_pasien.no_rawat = reg_periksa.no_rawat AND diagnosa_pasien.status = 'Ranap' AND prioritas = '1'
LEFT JOIN penyakit on penyakit.kd_penyakit = diagnosa_pasien.kd_penyakit
";

$where = [];

// mode aktif/pulang
if ($mode === 'aktif') {
  $where[] = "kamar_inap.tgl_keluar='0000-00-00'";
} else {
//   $where[] = "kamar_inap.tgl_keluar<>'0000-00-00'";
  $colDate = ($dateMode === 'masuk') ? "kamar_inap.tgl_masuk" : "kamar_inap.tgl_keluar";
  $where[] = "$colDate BETWEEN '$tgl1' AND '$tgl2'";
  $where[] = "kamar_inap.stts_pulang != 'Pindah Kamar'";
}

if ($kd_pj) {
  $in = "'" . implode("','", array_map('escape', $kd_pj)) . "'";
  $where[] = "reg_periksa.kd_pj IN ($in)";
}
if ($kd_bangsal) {
  $in = "'" . implode("','", array_map('escape', $kd_bangsal)) . "'";
  $where[] = "bangsal.kd_bangsal IN ($in)";
}
if ($mode !== 'aktif' && $kd_dokter) {
  $in = "'" . implode("','", array_map('escape', $kd_dokter)) . "'";
  $where[] = "reg_periksa.kd_dokter IN ($in)";
}

if ($search !== '') {
  $s = escape($search);
  $where[] = "("
    . "pasien.no_rkm_medis LIKE '%$s%' OR "
    . "pasien.nm_pasien LIKE '%$s%' OR "
    . "stts_daftar LIKE '%$s%' OR "
    . "bangsal.nm_bangsal LIKE '%$s%' OR "
    . "kamar_inap.kd_kamar LIKE '%$s%' OR "
    . "dokter.nm_dokter LIKE '%$s%' OR "
    . "penjab.png_jawab LIKE '%$s%'"
    . ")";
}

$whereSql = "WHERE " . implode(" AND ", $where);

// count total & filtered (di sini disamakan biar aman)
$recordsTotal = (int)(fetch_assoc(query("
  SELECT COUNT(*) as jml FROM (
    SELECT kamar_inap.no_rawat
    $fromJoin
    $whereSql
    GROUP BY kamar_inap.no_rawat, kamar_inap.tgl_masuk, kamar_inap.jam_masuk
  ) x
"))['jml'] ?? 0);

$recordsFiltered = $recordsTotal;

$sql = "
SELECT
  pasien.no_rkm_medis,
  pasien.nm_pasien,
  pasien.jk,
  CASE
    WHEN TIMESTAMPDIFF(MONTH, pasien.tgl_lahir, reg_periksa.tgl_registrasi) < 1
      THEN CONCAT(TIMESTAMPDIFF(DAY, pasien.tgl_lahir, reg_periksa.tgl_registrasi), ' Hr')
    WHEN TIMESTAMPDIFF(YEAR, pasien.tgl_lahir, reg_periksa.tgl_registrasi) < 1
      THEN CONCAT(TIMESTAMPDIFF(MONTH, pasien.tgl_lahir, reg_periksa.tgl_registrasi), ' Bl')
    ELSE CONCAT(TIMESTAMPDIFF(YEAR, pasien.tgl_lahir, reg_periksa.tgl_registrasi), ' Th')
  END AS umur_status,
  CASE WHEN reg_periksa.stts_daftar='Lama' THEN 'Pasien Lama'
       WHEN reg_periksa.stts_daftar='Baru' THEN 'Pasien Baru'
       ELSE reg_periksa.stts_daftar
  END AS stts_daftar,       
  bangsal.nm_bangsal,
  kamar_inap.kd_kamar,
  kamar_inap.tgl_masuk,
  kamar_inap.jam_masuk,
  diagnosa_pasien.kd_penyakit,
  penyakit.nm_penyakit,
  kamar_inap.tgl_keluar,
  kamar_inap.jam_keluar,
  kamar_inap.stts_pulang,
  penjab.png_jawab AS cara_bayar,
  dokter.nm_dokter
$fromJoin
$whereSql
GROUP BY kamar_inap.no_rawat, kamar_inap.tgl_masuk, kamar_inap.jam_masuk
ORDER BY $orderBy $orderDir
";

$data = q($sql);

echo json_encode([
  'draw' => $draw,
  'recordsTotal' => $recordsTotal,
  'recordsFiltered' => $recordsFiltered,
  'data' => $data,
]);
