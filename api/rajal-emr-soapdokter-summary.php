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

$exclude_poli = ["U0015","U0016","U0035","U0033","U0041","IGDK","U0036"];
$exclude_list = "'" . implode("','", $exclude_poli) . "'";

$tgl1 = asDate(post('startDate', date('Y-m-d')));
$tgl2 = asDate(post('endDate', date('Y-m-d')));

$poli      = array_filter(array_map('trim', toArray(post('poli', []))));
$stts      = array_filter(array_map('trim', toArray(post('status', []))));
$caraBayar = array_filter(array_map('trim', toArray(post('caraBayar', []))));

$where = [];
$where[] = "a.status_lanjut='Ralan'";
$where[] = "a.kd_poli NOT IN ($exclude_list)";
$where[] = "a.tgl_registrasi BETWEEN '$tgl1' AND '$tgl2'";
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
  a.kd_dokter,
  dokter.nm_dokter,
  poliklinik.nm_poli,
  COUNT(DISTINCT a.no_rawat) AS total_pasien,

  COUNT(DISTINCT CASE WHEN (
      (a.kd_poli IN ('U0045','U0021') AND EXISTS (
        SELECT 1
        FROM pemeriksaan_ralan_rehab prr
        WHERE prr.no_rawat = a.no_rawat
          AND prr.nik = a.kd_dokter
          AND prr.jam_rawat IS NOT NULL AND prr.jam_rawat <> ''
      ))
      OR
      (a.kd_poli NOT IN ('U0045','U0021') AND EXISTS (
        SELECT 1
        FROM pemeriksaan_ralan pr
        WHERE pr.no_rawat = a.no_rawat
          AND pr.nik = a.kd_dokter
          AND pr.jam_rawat IS NOT NULL AND pr.jam_rawat <> ''
      ))
  ) THEN a.no_rawat END) AS soap_diisi,

  ROUND(
    (
      COUNT(DISTINCT CASE WHEN (
          (a.kd_poli IN ('U0045','U0021') AND EXISTS (
            SELECT 1
            FROM pemeriksaan_ralan_rehab prr
            WHERE prr.no_rawat = a.no_rawat
              AND prr.nik = a.kd_dokter
              AND prr.jam_rawat IS NOT NULL AND prr.jam_rawat <> ''
          ))
          OR
          (a.kd_poli NOT IN ('U0045','U0021') AND EXISTS (
            SELECT 1
            FROM pemeriksaan_ralan pr
            WHERE pr.no_rawat = a.no_rawat
              AND pr.nik = a.kd_dokter
              AND pr.jam_rawat IS NOT NULL AND pr.jam_rawat <> ''
          ))
      ) THEN a.no_rawat END)
      / NULLIF(COUNT(DISTINCT a.no_rawat),0)
    ) * 100
  ) AS persentase

FROM reg_periksa a
INNER JOIN dokter ON dokter.kd_dokter=a.kd_dokter
INNER JOIN poliklinik ON poliklinik.kd_poli=a.kd_poli
$whereSql
GROUP BY a.kd_dokter, a.kd_poli
ORDER BY persentase DESC, total_pasien DESC
";

echo json_encode([
  'data' => q($sql),
]);
