<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

function post($k, $default=null){ return $_POST[$k] ?? $default; }
function asDate($s): string {
  $s = substr((string)$s, 0, 10);
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) ? $s : date('Y-m-d');
}

$draw   = (int)post('draw', 1);
$start  = max(0, (int)post('start', 0));
$length = (int)post('length', 10);
if ($length <= 0) $length = 10;

$tgl1 = asDate(post('startDate', date('Y-m-d')));
$tgl2 = asDate(post('endDate', date('Y-m-d')));

$subSql = "
SELECT DISTINCT
  rp.no_rawat,
  pasien.jk,
  CASE
    WHEN rp.status_lanjut = 'Ralan' THEN 'Rawat Jalan'
    WHEN rp.status_lanjut = 'Ranap' THEN 'Rawat Inap'
    ELSE rp.status_lanjut
  END AS jenis_layanan,

  CASE
    WHEN LEFT(dp.kd_penyakit, 1) = 'J'
         AND CAST(SUBSTRING(REPLACE(dp.kd_penyakit, '.', ''), 2, 2) AS UNSIGNED) BETWEEN 12 AND 18
      THEN 'Pneumonia'
    WHEN LEFT(dp.kd_penyakit, 1) = 'J'
         AND (
           CAST(SUBSTRING(REPLACE(dp.kd_penyakit, '.', ''), 2, 2) AS UNSIGNED) BETWEEN 0 AND 6
           OR CAST(SUBSTRING(REPLACE(dp.kd_penyakit, '.', ''), 2, 2) AS UNSIGNED) BETWEEN 9 AND 11
           OR CAST(SUBSTRING(REPLACE(dp.kd_penyakit, '.', ''), 2, 2) AS UNSIGNED) BETWEEN 20 AND 22
         )
      THEN 'Batuk Bukan Pneumonia'
    ELSE NULL
  END AS kategori_ispa,

  CASE
    WHEN rp.sttsumur = 'Hr' THEN '<1'
    WHEN rp.sttsumur = 'Bl' THEN '<1'
    WHEN rp.sttsumur = 'Th' AND rp.umurdaftar < 1 THEN '<1'
    WHEN rp.sttsumur = 'Th' AND rp.umurdaftar >= 1 AND rp.umurdaftar < 5 THEN '1-4'
    WHEN rp.sttsumur = 'Th' AND rp.umurdaftar BETWEEN 5 AND 9 THEN '5-9'
    WHEN rp.sttsumur = 'Th' AND rp.umurdaftar BETWEEN 10 AND 18 THEN '10-18'
    WHEN rp.sttsumur = 'Th' AND rp.umurdaftar BETWEEN 19 AND 59 THEN '19-59'
    WHEN rp.sttsumur = 'Th' AND rp.umurdaftar >= 60 THEN '60+'
    ELSE NULL
  END AS kelompok_umur,

  CASE
    WHEN EXISTS (
      SELECT 1
      FROM detail_pemberian_obat dpo
      INNER JOIN databarang db ON db.kode_brng = dpo.kode_brng
      WHERE dpo.no_rawat = rp.no_rawat
        AND db.kdjns = 'J120'
    ) THEN 1
    ELSE 0
  END AS antibiotik
FROM reg_periksa rp
INNER JOIN pasien ON pasien.no_rkm_medis = rp.no_rkm_medis
INNER JOIN diagnosa_pasien dp ON dp.no_rawat = rp.no_rawat
WHERE rp.tgl_registrasi >= '$tgl1'
  AND rp.tgl_registrasi < DATE_ADD('$tgl2', INTERVAL 1 DAY)
  AND rp.stts != 'Batal'
  AND rp.status_lanjut IN ('Ralan', 'Ranap')
  AND LEFT(dp.kd_penyakit, 1) = 'J'
";

$countSql = "
SELECT COUNT(*) AS jml
FROM (
  SELECT jenis_layanan, kategori_ispa
  FROM ($subSql) x
  WHERE kategori_ispa IS NOT NULL
  GROUP BY jenis_layanan, kategori_ispa
) z
";

$recordsTotal = (int)(fetch_assoc(query($countSql))['jml'] ?? 0);
$recordsFiltered = $recordsTotal;

$sql = "
SELECT
  jenis_layanan,
  kategori_ispa,

  COUNT(DISTINCT CASE WHEN kelompok_umur = '<1'  AND jk = 'L' THEN no_rawat END) AS u1_l,
  COUNT(DISTINCT CASE WHEN kelompok_umur = '<1'  AND jk = 'P' THEN no_rawat END) AS u1_p,
  COUNT(DISTINCT CASE WHEN kelompok_umur = '<1' THEN no_rawat END) AS u1_sub,
  COUNT(DISTINCT CASE WHEN kelompok_umur = '<1'  AND antibiotik = 1 THEN no_rawat END) AS u1_antibiotik,

  COUNT(DISTINCT CASE WHEN kelompok_umur = '1-4'  AND jk = 'L' THEN no_rawat END) AS u14_l,
  COUNT(DISTINCT CASE WHEN kelompok_umur = '1-4'  AND jk = 'P' THEN no_rawat END) AS u14_p,
  COUNT(DISTINCT CASE WHEN kelompok_umur = '1-4' THEN no_rawat END) AS u14_sub,
  COUNT(DISTINCT CASE WHEN kelompok_umur = '1-4'  AND antibiotik = 1 THEN no_rawat END) AS u14_antibiotik,

  COUNT(DISTINCT CASE WHEN kelompok_umur = '5-9'  AND jk = 'L' THEN no_rawat END) AS u5_l,
  COUNT(DISTINCT CASE WHEN kelompok_umur = '5-9'  AND jk = 'P' THEN no_rawat END) AS u5_p,
  COUNT(DISTINCT CASE WHEN kelompok_umur = '5-9' THEN no_rawat END) AS u5_sub,
  COUNT(DISTINCT CASE WHEN kelompok_umur = '5-9'  AND antibiotik = 1 THEN no_rawat END) AS u5_antibiotik,

  COUNT(DISTINCT CASE WHEN kelompok_umur = '10-18'  AND jk = 'L' THEN no_rawat END) AS u10_l,
  COUNT(DISTINCT CASE WHEN kelompok_umur = '10-18'  AND jk = 'P' THEN no_rawat END) AS u10_p,
  COUNT(DISTINCT CASE WHEN kelompok_umur = '10-18' THEN no_rawat END) AS u10_sub,
  COUNT(DISTINCT CASE WHEN kelompok_umur = '10-18'  AND antibiotik = 1 THEN no_rawat END) AS u10_antibiotik,

  COUNT(DISTINCT CASE WHEN kelompok_umur = '19-59'  AND jk = 'L' THEN no_rawat END) AS u19_l,
  COUNT(DISTINCT CASE WHEN kelompok_umur = '19-59'  AND jk = 'P' THEN no_rawat END) AS u19_p,
  COUNT(DISTINCT CASE WHEN kelompok_umur = '19-59' THEN no_rawat END) AS u19_sub,
  COUNT(DISTINCT CASE WHEN kelompok_umur = '19-59'  AND antibiotik = 1 THEN no_rawat END) AS u19_antibiotik,

  COUNT(DISTINCT CASE WHEN kelompok_umur = '60+'  AND jk = 'L' THEN no_rawat END) AS u60_l,
  COUNT(DISTINCT CASE WHEN kelompok_umur = '60+'  AND jk = 'P' THEN no_rawat END) AS u60_p,
  COUNT(DISTINCT CASE WHEN kelompok_umur = '60+' THEN no_rawat END) AS u60_sub,
  COUNT(DISTINCT CASE WHEN kelompok_umur = '60+'  AND antibiotik = 1 THEN no_rawat END) AS u60_antibiotik,

  COUNT(DISTINCT no_rawat) AS grand_total
FROM ($subSql) a
WHERE kategori_ispa IS NOT NULL
GROUP BY jenis_layanan, kategori_ispa
ORDER BY
  FIELD(jenis_layanan, 'Rawat Jalan', 'Rawat Inap'),
  FIELD(kategori_ispa, 'Pneumonia', 'Batuk Bukan Pneumonia')
LIMIT $start, $length
";

$rows = q($sql);

echo json_encode([
  'draw' => $draw,
  'recordsTotal' => $recordsTotal,
  'recordsFiltered' => $recordsFiltered,
  'data' => $rows,
]);