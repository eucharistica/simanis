<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

try {
  $noRawat = trim((string)($_GET['no_rawat'] ?? ''));
  if ($noRawat === '' || strlen($noRawat) > 17) throw new InvalidArgumentException('Nomor rawat tidak valid.');

  $sql = "SELECT pr.tgl_perawatan, pr.jam_rawat, pr.keluhan, pr.pemeriksaan, pr.penilaian, pr.rtl
    FROM pemeriksaan_ralan pr
    INNER JOIN reg_periksa rp ON rp.no_rawat=pr.no_rawat AND rp.kd_dokter=pr.nik
    WHERE pr.no_rawat=:no_rawat
    ORDER BY pr.tgl_perawatan DESC, pr.jam_rawat DESC";
  $stmt = pdo()->prepare($sql);
  $stmt->execute([':no_rawat'=>$noRawat]);
  echo json_encode(['ok'=>true, 'data'=>$stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $e) {
  http_response_code(422);
  echo json_encode(['ok'=>false, 'message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  error_log('tindakan-harian-dokter-soap: '.$e->getMessage());
  echo json_encode(['ok'=>false, 'message'=>'Gagal mengambil pemeriksaan pasien.'], JSON_UNESCAPED_UNICODE);
}
