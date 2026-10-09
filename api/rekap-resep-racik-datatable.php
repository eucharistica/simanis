<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

function postValue(string $key, $default = null)
{
    return $_POST[$key] ?? $default;
}

function validDate($value): string
{
    $value = substr((string) $value, 0, 10);

    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)
        ? $value
        : date('Y-m-d');
}

function invalidFilter(string $message): void
{
    http_response_code(400);
    echo json_encode(['data' => [], 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

$tgl1 = validDate(postValue('tgl1', date('Y-m-d')));
$tgl2 = validDate(postValue('tgl2', date('Y-m-d')));
$jenisRawat = strtolower(trim((string) postValue('jenis_rawat', 'ralan')));

if ($tgl1 > $tgl2) {
    [$tgl1, $tgl2] = [$tgl2, $tgl1];
}

if (!in_array($jenisRawat, ['ralan', 'ranap'], true)) {
    invalidFilter('Jenis rawat tidak valid.');
}

$statusDpo = $jenisRawat === 'ralan' ? 'Ralan' : 'Ranap';
$rawatJoin = '';
$dateWhere = "dpo.tgl_perawatan >= '$tgl1' AND dpo.tgl_perawatan <= '$tgl2'";

if ($jenisRawat === 'ralan') {
    $rawatJoin = 'INNER JOIN reg_periksa rp ON rp.no_rawat = dpo.no_rawat';
    $dateWhere = "rp.tgl_registrasi >= '$tgl1' AND rp.tgl_registrasi <= '$tgl2'";
}

$sql = "
SELECT
    dpo.kode_brng,
    db.nama_brng,
    SUM(CEIL(dpo.jml)) AS jumlah,
    ROUND(SUM(CEIL(dpo.jml) * dpo.biaya_obat), 2) AS total
FROM obat_racikan orac
INNER JOIN detail_obat_racikan dor
    ON dor.no_rawat = orac.no_rawat
    AND dor.tgl_perawatan = orac.tgl_perawatan
    AND dor.jam = orac.jam
    AND dor.no_racik = orac.no_racik
INNER JOIN detail_pemberian_obat dpo
    ON dpo.no_rawat = dor.no_rawat
    AND dpo.tgl_perawatan = dor.tgl_perawatan
    AND dpo.jam = dor.jam
    AND dpo.kode_brng = dor.kode_brng
$rawatJoin
INNER JOIN databarang db
    ON db.kode_brng = dpo.kode_brng
WHERE $dateWhere
  AND dpo.status = '$statusDpo'
  AND TRIM(COALESCE(db.kode_golongan, '')) NOT IN ('', '-', 'G01')
  AND TRIM(COALESCE(db.kode_kategori, '')) NOT IN ('', '-', 'K06')
GROUP BY dpo.kode_brng, db.nama_brng
ORDER BY SUM(CEIL(dpo.jml)) DESC, db.nama_brng ASC
";

$data = q($sql);

foreach ($data as &$row) {
    $row['jumlah'] = number_format((float) $row['jumlah'], 0, '.', ',');
    $row['total'] = number_format((float) $row['total'], 0, '.', ',');
}
unset($row);

echo json_encode(['data' => $data], JSON_UNESCAPED_UNICODE);
