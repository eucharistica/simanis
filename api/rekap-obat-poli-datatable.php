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

if ($tgl1 > $tgl2) {
    [$tgl1, $tgl2] = [$tgl2, $tgl1];
}

$jenisRawat = strtolower(trim((string) postValue('jenis_rawat', 'ralan')));
$kdPoli = trim((string) postValue('kd_poli', ''));
$requestedCategory = strtoupper(trim((string) postValue('kode_kategori', '')));
$golonganObat = strtoupper(trim((string) postValue('golongan_obat', '')));

$allowedJenisRawat = ['ralan', 'ranap'];
$allowedCategories = ['K01', 'K02', 'K04', 'K05'];
$allowedGolongan = ['', 'KRONIS', 'NONKRONIS'];

if (!in_array($jenisRawat, $allowedJenisRawat, true)) {
    invalidFilter('Jenis rawat tidak valid.');
}

if ($requestedCategory !== '' && !in_array($requestedCategory, $allowedCategories, true)) {
    invalidFilter('Kategori obat tidak valid.');
}

if (!in_array($golonganObat, $allowedGolongan, true)) {
    invalidFilter('Golongan obat tidak valid.');
}

$statusDpo = $jenisRawat === 'ralan' ? 'Ralan' : 'Ranap';
$commonWhere = [
    "dpo.status = '$statusDpo'",
    "db.kode_kategori IN ('K01','K02','K04','K05')",
    "TRIM(COALESCE(db.kode_golongan,'')) NOT IN ('','-','G01')",
];

if ($jenisRawat === 'ralan' && $kdPoli !== '') {
    $commonWhere[] = "rp.kd_poli = '" . escape($kdPoli) . "'";
}

if ($requestedCategory !== '') {
    $commonWhere[] = "db.kode_kategori = '" . escape($requestedCategory) . "'";
}

if ($golonganObat === 'KRONIS') {
    $commonWhere[] = "db.kode_golongan IN ('G04','G05')";
} elseif ($golonganObat === 'NONKRONIS') {
    $commonWhere[] = "db.kode_golongan IN ('G02','G03')";
}

$commonWhereSql = implode("\n      AND ", $commonWhere);

if ($jenisRawat === 'ralan') {
    // Ralan dihitung sebagai satu episode berdasarkan tanggal pasien berobat.
    $unitSelect = 'rp.kd_poli, p.nm_poli,';
    $unitJoins = "
    INNER JOIN reg_periksa rp
        ON rp.no_rawat = dpo.no_rawat
    INNER JOIN poliklinik p
        ON p.kd_poli = rp.kd_poli";
    $dateWhere = "rp.tgl_registrasi >= '$tgl1' AND rp.tgl_registrasi <= '$tgl2'";
    $innerGroup = 'rp.kd_poli, p.nm_poli, dpo.kode_brng, db.nama_brng';
    $outerSelect = 'resep.kd_poli, resep.nm_poli,';
    $outerGroup = 'resep.kd_poli, resep.nm_poli, resep.kode_brng, resep.nama_brng';
    $orderBy = 'resep.nm_poli ASC, resep.nama_brng ASC';
} else {
    // Ranap dihitung berdasarkan tanggal aktual pemberian obat.
    $unitSelect = "'' AS kd_poli, 'Rawat Inap' AS nm_poli,";
    $unitJoins = '';
    $dateWhere = "dpo.tgl_perawatan >= '$tgl1' AND dpo.tgl_perawatan <= '$tgl2'";
    $innerGroup = 'dpo.kode_brng, db.nama_brng';
    $outerSelect = '';
    $outerGroup = 'resep.kode_brng, resep.nama_brng';
    $orderBy = 'resep.nama_brng ASC';
}

/*
 * Non-racik dan racik mengikuti sumber Rekap Resep. Query luar menyatukan
 * keduanya per poli+obat untuk Ralan, atau per obat saja untuk Ranap.
 */
$sql = "
SELECT
    $outerSelect
    resep.kode_brng,
    resep.nama_brng,
    ROUND(SUM(resep.jumlah), 2) AS jumlah,
    ROUND(SUM(resep.total), 2) AS total
FROM (
    SELECT
        $unitSelect
        dpo.kode_brng,
        db.nama_brng,
        SUM(dpo.jml) AS jumlah,
        SUM(dpo.jml * dpo.biaya_obat) AS total
    FROM resep_obat ro
    INNER JOIN detail_pemberian_obat dpo
        ON dpo.no_rawat = ro.no_rawat
        AND dpo.tgl_perawatan = ro.tgl_perawatan
        AND dpo.jam = ro.jam
    $unitJoins
    INNER JOIN databarang db
        ON db.kode_brng = dpo.kode_brng
    WHERE $dateWhere
      AND $commonWhereSql
      AND NOT EXISTS (
          SELECT 1
          FROM detail_obat_racikan dor
          WHERE dor.no_rawat = dpo.no_rawat
            AND dor.tgl_perawatan = dpo.tgl_perawatan
            AND dor.jam = dpo.jam
            AND dor.kode_brng = dpo.kode_brng
      )
    GROUP BY $innerGroup

    UNION ALL

    SELECT
        $unitSelect
        dpo.kode_brng,
        db.nama_brng,
        SUM(CEIL(dpo.jml)) AS jumlah,
        SUM(CEIL(dpo.jml) * dpo.biaya_obat) AS total
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
    $unitJoins
    INNER JOIN databarang db
        ON db.kode_brng = dpo.kode_brng
    WHERE $dateWhere
      AND $commonWhereSql
    GROUP BY $innerGroup
) resep
GROUP BY $outerGroup
ORDER BY $orderBy
";

$data = q($sql);

foreach ($data as &$row) {
    $row['jumlah'] = number_format((float) $row['jumlah'], 0, '.', ',');
    $row['total'] = number_format((float) $row['total'], 0, '.', ',');
}
unset($row);

echo json_encode(['data' => $data], JSON_UNESCAPED_UNICODE);
