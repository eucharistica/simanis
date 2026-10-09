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

$exclude_poli = ["U0015", "U0016", "U0035", "U0033", "U0041", "U0036"];
$exclude_list = "'" . implode("','", $exclude_poli) . "'";

$tgl1 = asDate(post('startDate', date('Y-m-d')));
$tgl2 = asDate(post('endDate', date('Y-m-d')));

$poli      = array_filter(array_map('trim', toArray(post('poli', []))));
$stts      = array_filter(array_map('trim', toArray(post('status', []))));
$caraBayar = array_filter(array_map('trim', toArray(post('caraBayar', []))));

$fromJoin = "
FROM reg_periksa a
INNER JOIN penjab ON penjab.kd_pj=a.kd_pj
INNER JOIN pasien ON pasien.no_rkm_medis=a.no_rkm_medis
INNER JOIN poliklinik ON poliklinik.kd_poli=a.kd_poli
INNER JOIN dokter ON dokter.kd_dokter=a.kd_dokter
LEFT JOIN pemeriksaan_ralan b ON b.no_rawat=a.no_rawat AND a.kd_dokter=b.nik
LEFT JOIN pemeriksaan_ralan_rehab br ON br.no_rawat=a.no_rawat AND a.kd_dokter=br.nik
LEFT JOIN pemeriksaan_ralan c ON c.no_rawat=a.no_rawat AND a.kd_dokter<>c.nik
LEFT JOIN resep_obat ON resep_obat.no_rawat=a.no_rawat
LEFT JOIN diagnosa_pasien d ON d.no_rawat=a.no_rawat AND d.kd_penyakit NOT LIKE 'Z%'
LEFT JOIN penyakit p ON p.kd_penyakit=d.kd_penyakit
";

$where = [];
$where[] = "a.status_lanjut='Ralan'";
$where[] = "a.kd_poli NOT IN ($exclude_list)";
$where[] = "a.tgl_registrasi >= '$tgl1'";
$where[] = "a.tgl_registrasi < DATE_ADD('$tgl2', INTERVAL 1 DAY)";
$where[] = "a.stts!='Batal'";

if ($poli) {
  $in = "'" . implode("','", array_map('escape', $poli)) . "'";
  $where[] = "a.kd_poli IN ($in)";
}
if ($stts) {
  $in = "'" . implode("','", array_map('escape', $stts)) . "'";
  $where[] = "a.stts IN ($in)";
}
if ($caraBayar) {
  $in = "'" . implode("','", array_map('escape', $caraBayar)) . "'";
  $where[] = "a.kd_pj IN ($in)";
}

$whereSql = "WHERE " . implode(" AND ", $where);

$sql = "
SELECT
  a.tgl_registrasi,
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
  poliklinik.nm_poli,
  dokter.nm_dokter,
  penjab.png_jawab as cara_bayar,
  CASE
    WHEN a.status_poli='Lama' THEN 'Pasien Lama'
    WHEN a.status_poli='Baru' THEN 'Pasien Baru'
    ELSE a.status_poli
  END AS status_poli,
  a.stts AS status_pasien,
  IFNULL(c.jam_rawat, '-') AS soap_perawat,
  IFNULL(resep_obat.jam_peresepan, '-') AS resep_dokter,
  IFNULL(CASE WHEN a.kd_poli IN ('U0045','U0021') THEN br.jam_rawat ELSE b.jam_rawat END, '-') AS soap_dokter,
  IFNULL(resep_obat.jam, '-') AS validasi_resep,
  IFNULL(GROUP_CONCAT(DISTINCT CONCAT(d.kd_penyakit,' - ',p.nm_penyakit) SEPARATOR '; '), '-') AS diagnosa_pasien,
  a.status_bayar
$fromJoin
$whereSql
GROUP BY a.no_rawat
ORDER BY a.tgl_registrasi DESC, a.no_rawat DESC
";

$rows = q($sql);

echo json_encode([
  'data' => $rows
], JSON_UNESCAPED_UNICODE);