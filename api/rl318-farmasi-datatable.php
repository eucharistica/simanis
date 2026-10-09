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
  kategori,
  SUM(CASE WHEN layanan = 'Rawat Jalan' THEN jumlah ELSE 0 END) AS rawat_jalan,
  SUM(CASE WHEN layanan = 'IGD' THEN jumlah ELSE 0 END) AS igd,
  SUM(CASE WHEN layanan = 'Rawat Inap' THEN jumlah ELSE 0 END) AS rawat_inap
FROM (
  SELECT
    CASE
      WHEN rp.status_lanjut = 'Ralan' AND rp.kd_poli <> 'IGDK' THEN 'Rawat Jalan'
      WHEN rp.status_lanjut = 'Ralan' AND rp.kd_poli = 'IGDK' THEN 'IGD'
      WHEN rp.status_lanjut = 'Ranap' THEN 'Rawat Inap'
      ELSE NULL
    END AS layanan,

    CASE
      WHEN db.kode_golongan IN ('G02','G04') AND db.kode_kategori IN ('K04')
        THEN 'Obat Generik Formularium Nasional'
      WHEN db.kode_golongan IN ('G02','G04') AND db.kode_kategori IN ('K05')
        THEN 'Obat Generik Non Formularium Nasional'
      WHEN db.kode_golongan IN ('G03','G05') AND db.kode_kategori IN ('K04')
        THEN 'Obat Non Generik Formularium Nasional'
      WHEN db.kode_golongan IN ('G03','G05') AND db.kode_kategori IN ('K05')
        THEN 'Obat Non Generik Non Formularium Nasional'
      ELSE 'Tidak Ada Data'
    END AS kategori,

    COUNT(*) AS jumlah
  FROM detail_pemberian_obat dpo
  INNER JOIN reg_periksa rp ON rp.no_rawat = dpo.no_rawat
  INNER JOIN databarang db ON db.kode_brng = dpo.kode_brng
  WHERE rp.tgl_registrasi >= '$tgl1'
    AND rp.tgl_registrasi < DATE_ADD('$tgl2', INTERVAL 1 DAY)
    AND rp.stts != 'Batal'
  GROUP BY layanan, kategori
) x
GROUP BY kategori
ORDER BY
  FIELD(
    kategori,
    'Obat Generik Formularium Nasional',
    'Obat Generik Non Formularium Nasional',
    'Obat Non Generik Formularium Nasional',
    'Obat Non Generik Non Formularium Nasional',
    'Tidak Ada Data'
  )
";

$raw = q($sql);

$master = [
  'Obat Generik Formularium Nasional',
  'Obat Generik Non Formularium Nasional',
  'Obat Non Generik Formularium Nasional',
  'Obat Non Generik Non Formularium Nasional',
  'Tidak Ada Data'
];

$map = [];
foreach ($raw as $r) {
  $map[$r['kategori']] = $r;
}

$data = [];
$no = 1;
foreach ($master as $nama) {
  $row = $map[$nama] ?? [
    'kategori' => $nama,
    'rawat_jalan' => 0,
    'igd' => 0,
    'rawat_inap' => 0,
  ];

  $data[] = [
      'no' => $nama === 'Tidak Ada Data' ? 0 : $no++,
      'golongan_obat' => $nama,
      'rawat_jalan' => (int)$row['rawat_jalan'],
      'igd' => (int)$row['igd'],
      'rawat_inap' => (int)$row['rawat_inap'],
    ];
}

echo json_encode([
  'data' => $data
], JSON_UNESCAPED_UNICODE);