<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

function ymValue($value, string $fallback): string {
    $value = preg_replace('/[^0-9-]/', '', (string)$value);
    return preg_match('/^\d{4}-\d{2}$/', $value) ? $value : $fallback;
}

$awal = ymValue($_POST['bulan_awal'] ?? '', date('Y-m'));
$akhir = ymValue($_POST['bulan_akhir'] ?? '', date('Y-m'));
if ($awal > $akhir) [$awal, $akhir] = [$akhir, $awal];
$tglAwal = $awal . '-01';
$tglAkhir = (new DateTimeImmutable($akhir . '-01'))->modify('+1 month')->format('Y-m-d');
$monthCount = ((int)substr($akhir, 0, 4) - (int)substr($awal, 0, 4)) * 12 + (int)substr($akhir, 5, 2) - (int)substr($awal, 5, 2) + 1;
$isBhp = (string)($_POST['jenis'] ?? '') === 'bhp';
$categoryWhere = $isBhp
    ? "(db.kode_kategori NOT IN ('K01','K02','K04','K05') OR TRIM(COALESCE(db.kode_golongan,'')) IN ('','-','G01'))"
    : "db.kode_kategori IN ('K01','K02','K04','K05') AND TRIM(COALESCE(db.kode_golongan,'')) NOT IN ('','-','G01')";

try {
    $sql = "
      SELECT x.kode_brng, x.nama_brng,
             FLOOR(COALESCE(MAX(g.stok), 0)) AS stok,
             ROUND(FLOOR(COALESCE(MAX(g.stok), 0)) * COALESCE(MAX(db_dasar.dasar), 0), 2) AS persediaan,
             ROUND(SUM(x.jumlah), 2) AS jumlah_keluar,
             FLOOR(SUM(x.jumlah) / $monthCount) AS rata2_pengeluaran
      FROM (
        SELECT dpo.kode_brng, db.nama_brng, DATE_FORMAT(rp.tgl_registrasi, '%Y-%m') AS bulan,
               SUM(dpo.jml) AS jumlah
        FROM resep_obat ro
        INNER JOIN detail_pemberian_obat dpo ON dpo.no_rawat=ro.no_rawat AND dpo.tgl_perawatan=ro.tgl_perawatan AND dpo.jam=ro.jam
        INNER JOIN reg_periksa rp ON rp.no_rawat=dpo.no_rawat
        INNER JOIN databarang db ON db.kode_brng=dpo.kode_brng
        WHERE dpo.status='Ralan' AND rp.tgl_registrasi >= :tgl_awal AND rp.tgl_registrasi < :tgl_akhir
          AND $categoryWhere
          AND NOT EXISTS (SELECT 1 FROM detail_obat_racikan dor WHERE dor.no_rawat=dpo.no_rawat AND dor.tgl_perawatan=dpo.tgl_perawatan AND dor.jam=dpo.jam AND dor.kode_brng=dpo.kode_brng)
        GROUP BY dpo.kode_brng, db.nama_brng, bulan
        UNION ALL
        SELECT dpo.kode_brng, db.nama_brng, DATE_FORMAT(rp.tgl_registrasi, '%Y-%m') AS bulan,
               SUM(CEIL(dpo.jml)) AS jumlah
        FROM obat_racikan orac
        INNER JOIN detail_obat_racikan dor ON dor.no_rawat=orac.no_rawat AND dor.tgl_perawatan=orac.tgl_perawatan AND dor.jam=orac.jam AND dor.no_racik=orac.no_racik
        INNER JOIN detail_pemberian_obat dpo ON dpo.no_rawat=dor.no_rawat AND dpo.tgl_perawatan=dor.tgl_perawatan AND dpo.jam=dor.jam AND dpo.kode_brng=dor.kode_brng
        INNER JOIN reg_periksa rp ON rp.no_rawat=dpo.no_rawat
        INNER JOIN databarang db ON db.kode_brng=dpo.kode_brng
        WHERE dpo.status='Ralan' AND rp.tgl_registrasi >= :tgl_awal_ralan_racik AND rp.tgl_registrasi < :tgl_akhir_ralan_racik
          AND $categoryWhere
        GROUP BY dpo.kode_brng, db.nama_brng, bulan
        UNION ALL
        SELECT dpo.kode_brng, db.nama_brng, DATE_FORMAT(dpo.tgl_perawatan, '%Y-%m') AS bulan,
               SUM(CEIL(dpo.jml)) AS jumlah
        FROM obat_racikan orac
        INNER JOIN detail_obat_racikan dor ON dor.no_rawat=orac.no_rawat AND dor.tgl_perawatan=orac.tgl_perawatan AND dor.jam=orac.jam AND dor.no_racik=orac.no_racik
        INNER JOIN detail_pemberian_obat dpo ON dpo.no_rawat=dor.no_rawat AND dpo.tgl_perawatan=dor.tgl_perawatan AND dpo.jam=dor.jam AND dpo.kode_brng=dor.kode_brng
        INNER JOIN databarang db ON db.kode_brng=dpo.kode_brng
        WHERE dpo.status='Ranap' AND dpo.tgl_perawatan >= :tgl_awal_ranap AND dpo.tgl_perawatan < :tgl_akhir_ranap
          AND $categoryWhere
        GROUP BY dpo.kode_brng, db.nama_brng, bulan
        UNION ALL
        SELECT dpo.kode_brng, db.nama_brng, DATE_FORMAT(dpo.tgl_perawatan, '%Y-%m') AS bulan,
               SUM(dpo.jml) AS jumlah
        FROM resep_obat ro
        INNER JOIN detail_pemberian_obat dpo ON dpo.no_rawat=ro.no_rawat AND dpo.tgl_perawatan=ro.tgl_perawatan AND dpo.jam=ro.jam
        INNER JOIN databarang db ON db.kode_brng=dpo.kode_brng
        WHERE dpo.status='Ranap' AND dpo.tgl_perawatan >= :tgl_awal_ranap_non AND dpo.tgl_perawatan < :tgl_akhir_ranap_non
          AND $categoryWhere
          AND NOT EXISTS (SELECT 1 FROM detail_obat_racikan dor WHERE dor.no_rawat=dpo.no_rawat AND dor.tgl_perawatan=dpo.tgl_perawatan AND dor.jam=dpo.jam AND dor.kode_brng=dpo.kode_brng)
        GROUP BY dpo.kode_brng, db.nama_brng, bulan
      ) x
      LEFT JOIN databarang db_dasar ON db_dasar.kode_brng=x.kode_brng
      LEFT JOIN (SELECT kode_brng, SUM(stok) AS stok FROM gudangbarang WHERE kd_bangsal IN ('B0015','B0016') GROUP BY kode_brng) g ON g.kode_brng=x.kode_brng
      GROUP BY x.kode_brng, x.nama_brng ORDER BY x.nama_brng";
    $stmt = pdo()->prepare($sql);
    $params = ['tgl_awal'=>$tglAwal,'tgl_akhir'=>$tglAkhir,'tgl_awal_ralan_racik'=>$tglAwal,'tgl_akhir_ralan_racik'=>$tglAkhir,'tgl_awal_ranap'=>$tglAwal,'tgl_akhir_ranap'=>$tglAkhir,'tgl_awal_ranap_non'=>$tglAwal,'tgl_akhir_ranap_non'=>$tglAkhir];
    $stmt->execute($params);
    echo json_encode(['ok'=>true,'data'=>$stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'message'=>'Gagal memuat rekap rata-rata pengeluaran obat.']);
}
