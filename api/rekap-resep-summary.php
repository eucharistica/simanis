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
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
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

$medicineWhere = "
  AND dpo.status = '$statusDpo'
  AND TRIM(COALESCE(db.kode_golongan, '')) NOT IN ('', '-', 'G01')
  AND TRIM(COALESCE(db.kode_kategori, '')) NOT IN ('', '-', 'K06')";

$sqlNonRacik = "
SELECT
    COUNT(DISTINCT ro.no_resep) AS jumlah_resep,
    COALESCE(SUM(dpo.jml), 0) AS jumlah_obat_keluar,
    COALESCE(SUM(dpo.jml * dpo.biaya_obat), 0) AS total_pengeluaran
FROM resep_obat ro
INNER JOIN detail_pemberian_obat dpo
    ON dpo.no_rawat = ro.no_rawat
    AND dpo.tgl_perawatan = ro.tgl_perawatan
    AND dpo.jam = ro.jam
$rawatJoin
INNER JOIN databarang db
    ON db.kode_brng = dpo.kode_brng
WHERE $dateWhere
$medicineWhere
  AND NOT EXISTS (
      SELECT 1
      FROM detail_obat_racikan dor
      WHERE dor.no_rawat = dpo.no_rawat
        AND dor.tgl_perawatan = dpo.tgl_perawatan
        AND dor.jam = dpo.jam
        AND dor.kode_brng = dpo.kode_brng
  )
";

$sqlRacik = "
SELECT
    COUNT(DISTINCT CONCAT(
        orac.no_rawat, '|', orac.tgl_perawatan, '|', orac.jam, '|', orac.no_racik
    )) AS jumlah_resep,
    COALESCE(SUM(CEIL(dpo.jml)), 0) AS jumlah_obat_keluar,
    COALESCE(SUM(CEIL(dpo.jml) * dpo.biaya_obat), 0) AS total_pengeluaran
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
$medicineWhere
";

$empty = [
    'jumlah_resep' => 0,
    'jumlah_obat_keluar' => 0,
    'total_pengeluaran' => 0,
];

$nonRacik = fetch_assoc(query($sqlNonRacik)) ?: $empty;
$racik = fetch_assoc(query($sqlRacik)) ?: $empty;

echo json_encode([
    'jenis_rawat' => $jenisRawat,
    'non_racik' => [
        'jumlah_resep' => (int) $nonRacik['jumlah_resep'],
        'jumlah_obat_keluar' => (float) $nonRacik['jumlah_obat_keluar'],
        'total_pengeluaran' => (float) $nonRacik['total_pengeluaran'],
    ],
    'racik' => [
        'jumlah_resep' => (int) $racik['jumlah_resep'],
        'jumlah_obat_keluar' => (float) $racik['jumlah_obat_keluar'],
        'total_pengeluaran' => (float) $racik['total_pengeluaran'],
    ],
], JSON_UNESCAPED_UNICODE);
