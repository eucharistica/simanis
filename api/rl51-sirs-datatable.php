<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

function post($k, $default=null){ return $_POST[$k] ?? $default; }
function asDate($s): string {
  $s = substr((string)$s, 0, 10);
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) ? $s : date('Y-m-d');
}

$tgl1 = asDate(post('startDate', date('Y-m-01')));
$tgl2 = asDate(post('endDate', date('Y-m-d')));

$sql = "
SELECT
  x.kd_penyakit,
  p.nm_penyakit,

  SUM(CASE WHEN x.umur_group = '<1jam'     AND x.jk='L' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_lt1jam_l,
  SUM(CASE WHEN x.umur_group = '<1jam'     AND x.jk='P' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_lt1jam_p,

  SUM(CASE WHEN x.umur_group = '1_23jam'   AND x.jk='L' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_1_23jam_l,
  SUM(CASE WHEN x.umur_group = '1_23jam'   AND x.jk='P' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_1_23jam_p,

  SUM(CASE WHEN x.umur_group = '1_7hr'     AND x.jk='L' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_1_7hr_l,
  SUM(CASE WHEN x.umur_group = '1_7hr'     AND x.jk='P' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_1_7hr_p,

  SUM(CASE WHEN x.umur_group = '8_28hr'    AND x.jk='L' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_8_28hr_l,
  SUM(CASE WHEN x.umur_group = '8_28hr'    AND x.jk='P' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_8_28hr_p,

  SUM(CASE WHEN x.umur_group = '29hr_3bln' AND x.jk='L' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_29hr_3bln_l,
  SUM(CASE WHEN x.umur_group = '29hr_3bln' AND x.jk='P' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_29hr_3bln_p,

  SUM(CASE WHEN x.umur_group = '3_6bln'    AND x.jk='L' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_3_6bln_l,
  SUM(CASE WHEN x.umur_group = '3_6bln'    AND x.jk='P' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_3_6bln_p,

  SUM(CASE WHEN x.umur_group = '6_11bln'   AND x.jk='L' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_6_11bln_l,
  SUM(CASE WHEN x.umur_group = '6_11bln'   AND x.jk='P' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_6_11bln_p,

  SUM(CASE WHEN x.umur_group = '1_4th'     AND x.jk='L' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_1_4th_l,
  SUM(CASE WHEN x.umur_group = '1_4th'     AND x.jk='P' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_1_4th_p,

  SUM(CASE WHEN x.umur_group = '5_9th'     AND x.jk='L' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_5_9th_l,
  SUM(CASE WHEN x.umur_group = '5_9th'     AND x.jk='P' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_5_9th_p,

  SUM(CASE WHEN x.umur_group = '10_14th'   AND x.jk='L' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_10_14th_l,
  SUM(CASE WHEN x.umur_group = '10_14th'   AND x.jk='P' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_10_14th_p,

  SUM(CASE WHEN x.umur_group = '15_19th'   AND x.jk='L' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_15_19th_l,
  SUM(CASE WHEN x.umur_group = '15_19th'   AND x.jk='P' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_15_19th_p,

  SUM(CASE WHEN x.umur_group = '20_24th'   AND x.jk='L' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_20_24th_l,
  SUM(CASE WHEN x.umur_group = '20_24th'   AND x.jk='P' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_20_24th_p,

  SUM(CASE WHEN x.umur_group = '25_29th'   AND x.jk='L' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_25_29th_l,
  SUM(CASE WHEN x.umur_group = '25_29th'   AND x.jk='P' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_25_29th_p,

  SUM(CASE WHEN x.umur_group = '30_34th'   AND x.jk='L' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_30_34th_l,
  SUM(CASE WHEN x.umur_group = '30_34th'   AND x.jk='P' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_30_34th_p,

  SUM(CASE WHEN x.umur_group = '35_39th'   AND x.jk='L' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_35_39th_l,
  SUM(CASE WHEN x.umur_group = '35_39th'   AND x.jk='P' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_35_39th_p,

  SUM(CASE WHEN x.umur_group = '40_44th'   AND x.jk='L' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_40_44th_l,
  SUM(CASE WHEN x.umur_group = '40_44th'   AND x.jk='P' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_40_44th_p,

  SUM(CASE WHEN x.umur_group = '45_49th'   AND x.jk='L' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_45_49th_l,
  SUM(CASE WHEN x.umur_group = '45_49th'   AND x.jk='P' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_45_49th_p,

  SUM(CASE WHEN x.umur_group = '50_54th'   AND x.jk='L' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_50_54th_l,
  SUM(CASE WHEN x.umur_group = '50_54th'   AND x.jk='P' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_50_54th_p,

  SUM(CASE WHEN x.umur_group = '55_59th'   AND x.jk='L' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_55_59th_l,
  SUM(CASE WHEN x.umur_group = '55_59th'   AND x.jk='P' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_55_59th_p,

  SUM(CASE WHEN x.umur_group = '60_64th'   AND x.jk='L' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_60_64th_l,
  SUM(CASE WHEN x.umur_group = '60_64th'   AND x.jk='P' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_60_64th_p,

  SUM(CASE WHEN x.umur_group = '65_69th'   AND x.jk='L' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_65_69th_l,
  SUM(CASE WHEN x.umur_group = '65_69th'   AND x.jk='P' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_65_69th_p,

  SUM(CASE WHEN x.umur_group = '70_74th'   AND x.jk='L' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_70_74th_l,
  SUM(CASE WHEN x.umur_group = '70_74th'   AND x.jk='P' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_70_74th_p,

  SUM(CASE WHEN x.umur_group = '75_79th'   AND x.jk='L' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_75_79th_l,
  SUM(CASE WHEN x.umur_group = '75_79th'   AND x.jk='P' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_75_79th_p,

  SUM(CASE WHEN x.umur_group = '80_84th'   AND x.jk='L' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_80_84th_l,
  SUM(CASE WHEN x.umur_group = '80_84th'   AND x.jk='P' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_80_84th_p,

  SUM(CASE WHEN x.umur_group = 'gt85th'    AND x.jk='L' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_gt85th_l,
  SUM(CASE WHEN x.umur_group = 'gt85th'    AND x.jk='P' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS u_gt85th_p,

  SUM(CASE WHEN x.jk='L' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS kasus_baru_l,
  SUM(CASE WHEN x.jk='P' AND x.status_poli='Baru' THEN 1 ELSE 0 END) AS kasus_baru_p,
  SUM(CASE WHEN x.status_poli='Baru' THEN 1 ELSE 0 END) AS kasus_baru_total,

  SUM(CASE WHEN x.jk='L' THEN 1 ELSE 0 END) AS kunjungan_l,
  SUM(CASE WHEN x.jk='P' THEN 1 ELSE 0 END) AS kunjungan_p,
  COUNT(*) AS kunjungan_total

FROM (
  SELECT
    rp.no_rawat,
    rp.no_rkm_medis,
    rp.tgl_registrasi,
    rp.status_poli,
    ps.jk,
    dp.kd_penyakit,
    CASE
      WHEN TIMESTAMPDIFF(HOUR, ps.tgl_lahir, rp.tgl_registrasi) < 1 THEN '<1jam'
      WHEN TIMESTAMPDIFF(HOUR, ps.tgl_lahir, rp.tgl_registrasi) BETWEEN 1 AND 23 THEN '1_23jam'
      WHEN TIMESTAMPDIFF(DAY, ps.tgl_lahir, rp.tgl_registrasi) BETWEEN 1 AND 7 THEN '1_7hr'
      WHEN TIMESTAMPDIFF(DAY, ps.tgl_lahir, rp.tgl_registrasi) BETWEEN 8 AND 28 THEN '8_28hr'
      WHEN TIMESTAMPDIFF(MONTH, ps.tgl_lahir, rp.tgl_registrasi) >= 1 AND TIMESTAMPDIFF(MONTH, ps.tgl_lahir, rp.tgl_registrasi) < 3 THEN '29hr_3bln'
      WHEN TIMESTAMPDIFF(MONTH, ps.tgl_lahir, rp.tgl_registrasi) >= 3 AND TIMESTAMPDIFF(MONTH, ps.tgl_lahir, rp.tgl_registrasi) < 6 THEN '3_6bln'
      WHEN TIMESTAMPDIFF(MONTH, ps.tgl_lahir, rp.tgl_registrasi) >= 6 AND TIMESTAMPDIFF(MONTH, ps.tgl_lahir, rp.tgl_registrasi) < 12 THEN '6_11bln'
      WHEN TIMESTAMPDIFF(YEAR, ps.tgl_lahir, rp.tgl_registrasi) BETWEEN 1 AND 4 THEN '1_4th'
      WHEN TIMESTAMPDIFF(YEAR, ps.tgl_lahir, rp.tgl_registrasi) BETWEEN 5 AND 9 THEN '5_9th'
      WHEN TIMESTAMPDIFF(YEAR, ps.tgl_lahir, rp.tgl_registrasi) BETWEEN 10 AND 14 THEN '10_14th'
      WHEN TIMESTAMPDIFF(YEAR, ps.tgl_lahir, rp.tgl_registrasi) BETWEEN 15 AND 19 THEN '15_19th'
      WHEN TIMESTAMPDIFF(YEAR, ps.tgl_lahir, rp.tgl_registrasi) BETWEEN 20 AND 24 THEN '20_24th'
      WHEN TIMESTAMPDIFF(YEAR, ps.tgl_lahir, rp.tgl_registrasi) BETWEEN 25 AND 29 THEN '25_29th'
      WHEN TIMESTAMPDIFF(YEAR, ps.tgl_lahir, rp.tgl_registrasi) BETWEEN 30 AND 34 THEN '30_34th'
      WHEN TIMESTAMPDIFF(YEAR, ps.tgl_lahir, rp.tgl_registrasi) BETWEEN 35 AND 39 THEN '35_39th'
      WHEN TIMESTAMPDIFF(YEAR, ps.tgl_lahir, rp.tgl_registrasi) BETWEEN 40 AND 44 THEN '40_44th'
      WHEN TIMESTAMPDIFF(YEAR, ps.tgl_lahir, rp.tgl_registrasi) BETWEEN 45 AND 49 THEN '45_49th'
      WHEN TIMESTAMPDIFF(YEAR, ps.tgl_lahir, rp.tgl_registrasi) BETWEEN 50 AND 54 THEN '50_54th'
      WHEN TIMESTAMPDIFF(YEAR, ps.tgl_lahir, rp.tgl_registrasi) BETWEEN 55 AND 59 THEN '55_59th'
      WHEN TIMESTAMPDIFF(YEAR, ps.tgl_lahir, rp.tgl_registrasi) BETWEEN 60 AND 64 THEN '60_64th'
      WHEN TIMESTAMPDIFF(YEAR, ps.tgl_lahir, rp.tgl_registrasi) BETWEEN 65 AND 69 THEN '65_69th'
      WHEN TIMESTAMPDIFF(YEAR, ps.tgl_lahir, rp.tgl_registrasi) BETWEEN 70 AND 74 THEN '70_74th'
      WHEN TIMESTAMPDIFF(YEAR, ps.tgl_lahir, rp.tgl_registrasi) BETWEEN 75 AND 79 THEN '75_79th'
      WHEN TIMESTAMPDIFF(YEAR, ps.tgl_lahir, rp.tgl_registrasi) BETWEEN 80 AND 84 THEN '80_84th'
      WHEN TIMESTAMPDIFF(YEAR, ps.tgl_lahir, rp.tgl_registrasi) >= 85 THEN 'gt85th'
      ELSE NULL
    END AS umur_group
  FROM reg_periksa rp
  INNER JOIN pasien ps ON ps.no_rkm_medis = rp.no_rkm_medis
  INNER JOIN diagnosa_pasien dp ON dp.no_rawat = rp.no_rawat
  WHERE rp.tgl_registrasi >= '$tgl1'
    AND rp.tgl_registrasi < DATE_ADD('$tgl2', INTERVAL 1 DAY)
    AND rp.stts != 'Batal'
    AND rp.status_lanjut = 'Ralan'
    AND dp.prioritas IN ('0','1')
    AND dp.kd_penyakit IS NOT NULL
    AND TRIM(dp.kd_penyakit) <> ''
    AND NOT (
        LEFT(UPPER(REPLACE(TRIM(dp.kd_penyakit), '.', '')), 3) IN ('O80', 'O82')
        OR LEFT(UPPER(REPLACE(TRIM(dp.kd_penyakit), '.', '')), 1) IN ('R', 'V', 'W', 'X', 'Y', 'Z')
    )
) x
LEFT JOIN penyakit p ON p.kd_penyakit = x.kd_penyakit
GROUP BY x.kd_penyakit, p.nm_penyakit
ORDER BY kunjungan_total DESC, kasus_baru_total DESC, x.kd_penyakit ASC
LIMIT 15
";

$rows = q($sql);

foreach ($rows as $i => &$r) {
  $r['no'] = $i + 1;
  foreach ($r as $k => $v) {
    if ($k !== 'no' && $k !== 'kd_penyakit' && $k !== 'nm_penyakit') {
      $r[$k] = (int)$v;
    }
  }
}
unset($r);

echo json_encode([
  'data' => $rows
], JSON_UNESCAPED_UNICODE);