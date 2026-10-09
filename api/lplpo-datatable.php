<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

function post($k, $d=null){ return $_POST[$k] ?? $d; }
function asDate($s): string {
  $s = substr((string)$s, 0, 10);
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) ? $s : date('Y-m-d');
}

$tgl1  = asDate(post('tgl1', date('Y-m-01')));
$tgl2  = asDate(post('tgl2', date('Y-m-d')));
$jenis = strtolower(trim((string)post('jenis', 'obat')));

if ($jenis === 'nonobat') {
  $filterJenis = "(
    kategori_barang.kode IN ('-','K06')
    OR golongan_barang.kode IN ('-','G01')
  )";
} else {
  $filterJenis = "(
    kategori_barang.kode NOT IN ('-','K06')
    AND golongan_barang.kode NOT IN ('-','G01')
  )";
}

$aktifkanbatch = "no";
$hppfarmasi    = "dasar";

if ($aktifkanbatch === 'yes') {
  $stokSub = "
    SELECT
      gudangbarang.kode_brng,
      SUM(gudangbarang.stok) AS stok
    FROM gudangbarang
    WHERE gudangbarang.no_batch <> ''
      AND gudangbarang.no_faktur <> ''
    GROUP BY gudangbarang.kode_brng
  ";
} else {
  $stokSub = "
    SELECT
      gudangbarang.kode_brng,
      SUM(gudangbarang.stok) AS stok
    FROM gudangbarang
    WHERE gudangbarang.no_batch = ''
      AND gudangbarang.no_faktur = ''
    GROUP BY gudangbarang.kode_brng
  ";
}

$sql = "
SELECT
  dpo.kode_brng,
  databarang.nama_brng,
  kodesatuan.satuan,
  ROUND(COALESCE(MAX(gb.stok),0),2) AS stok,

  ROUND(SUM(CASE
    WHEN dpo.status='Ralan' AND (rp.kd_poli IS NULL OR rp.kd_poli <> 'IGDK')
    THEN dpo.jml ELSE 0 END),2) AS rajal_qty,
  ROUND(SUM(CASE
    WHEN dpo.status='Ralan' AND (rp.kd_poli IS NULL OR rp.kd_poli <> 'IGDK')
    THEN (dpo.total - (dpo.embalase + dpo.tuslah)) ELSE 0 END),2) AS rajal_rp,

  ROUND(SUM(CASE
    WHEN dpo.status='Ralan' AND rp.kd_poli='IGDK'
    THEN dpo.jml ELSE 0 END),2) AS igd_qty,
  ROUND(SUM(CASE
    WHEN dpo.status='Ralan' AND rp.kd_poli='IGDK'
    THEN (dpo.total - (dpo.embalase + dpo.tuslah)) ELSE 0 END),2) AS igd_rp,

  ROUND(SUM(CASE
    WHEN dpo.status='Ranap'
    THEN dpo.jml ELSE 0 END),2) AS ranap_qty,
  ROUND(SUM(CASE
    WHEN dpo.status='Ranap'
    THEN (dpo.total - (dpo.embalase + dpo.tuslah)) ELSE 0 END),2) AS ranap_rp,

  ROUND(SUM(dpo.jml),2) AS total_qty,
  ROUND(SUM(dpo.total - (dpo.embalase + dpo.tuslah)),2) AS total_rp
FROM detail_pemberian_obat dpo
INNER JOIN databarang ON databarang.kode_brng = dpo.kode_brng
INNER JOIN kodesatuan ON databarang.kode_sat = kodesatuan.kode_sat
INNER JOIN kategori_barang ON databarang.kode_kategori = kategori_barang.kode
INNER JOIN golongan_barang ON databarang.kode_golongan = golongan_barang.kode
LEFT JOIN reg_periksa rp ON rp.no_rawat = dpo.no_rawat
LEFT JOIN (
  $stokSub
) gb ON gb.kode_brng = dpo.kode_brng
WHERE dpo.tgl_perawatan >= '$tgl1'
  AND dpo.tgl_perawatan < DATE_ADD('$tgl2', INTERVAL 1 DAY)
  AND $filterJenis
GROUP BY dpo.kode_brng, databarang.nama_brng, kodesatuan.satuan
HAVING
  stok > 0
  OR rajal_qty > 0 OR igd_qty > 0 OR ranap_qty > 0
  OR rajal_rp > 0 OR igd_rp > 0 OR ranap_rp > 0
  OR total_qty > 0 OR total_rp > 0
ORDER BY databarang.nama_brng ASC
";

$data = q($sql);

foreach ($data as &$r) {
  $r['stok']      = number_format((float)$r['stok'], 2, '.', '');
  $r['rajal_qty'] = number_format((float)$r['rajal_qty'], 2, '.', '');
  $r['rajal_rp']  = number_format((float)$r['rajal_rp'], 2, '.', '');
  $r['igd_qty']   = number_format((float)$r['igd_qty'], 2, '.', '');
  $r['igd_rp']    = number_format((float)$r['igd_rp'], 2, '.', '');
  $r['ranap_qty'] = number_format((float)$r['ranap_qty'], 2, '.', '');
  $r['ranap_rp']  = number_format((float)$r['ranap_rp'], 2, '.', '');
  $r['total_qty'] = number_format((float)$r['total_qty'], 2, '.', '');
  $r['total_rp']  = number_format((float)$r['total_rp'], 2, '.', '');
}
unset($r);

echo json_encode([
  'data' => $data
], JSON_UNESCAPED_UNICODE);