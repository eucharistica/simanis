<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

function validDate(string $value): ?string {
  $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
  return $date && $date->format('Y-m-d') === $value ? $value : null;
}

try {
  $tgl1 = validDate(substr((string)($_POST['tgl1'] ?? ''), 0, 10));
  $tgl2 = validDate(substr((string)($_POST['tgl2'] ?? ''), 0, 10));
  $kdPoli = trim((string)($_POST['kd_poli'] ?? ''));
  $kdDokter = trim((string)($_POST['kd_dokter'] ?? ''));

  if (!$tgl1 || !$tgl2 || $tgl1 > $tgl2) {
    http_response_code(422);
    throw new InvalidArgumentException('Rentang tanggal tidak valid.');
  }

  $where = [
    "rp.status_lanjut = 'Ralan'",
    'rp.tgl_registrasi BETWEEN :tgl1 AND :tgl2',
    'NOT EXISTS (SELECT 1 FROM piutang_pasien pp WHERE pp.no_rawat = rp.no_rawat)',
    "b.status IN ('Laborat','Radiologi','Obat','Ralan Dokter','Ralan Dokter Paramedis','Ralan Paramedis','Tambahan','Registrasi','Operasi')"
  ];
  $params = [':tgl1' => $tgl1, ':tgl2' => $tgl2];

  if ($kdPoli !== '') {
    $where[] = 'rp.kd_poli = :kd_poli';
    $params[':kd_poli'] = $kdPoli;
  }
  if ($kdDokter !== '') {
    $where[] = 'rp.kd_dokter = :kd_dokter';
    $params[':kd_dokter'] = $kdDokter;
  }

  $sql = "SELECT DATE_FORMAT(rp.tgl_registrasi, '%Y-%m') AS periode,
                 COALESCE(SUM(b.totalbiaya), 0) AS total
          FROM reg_periksa rp
          INNER JOIN billing b ON b.no_rawat = rp.no_rawat
          WHERE " . implode(' AND ', $where) . "
          GROUP BY DATE_FORMAT(rp.tgl_registrasi, '%Y-%m')
          ORDER BY periode";
  $stmt = pdo()->prepare($sql);
  $stmt->execute($params);
  $monthlyResults = [];
  foreach ($stmt->fetchAll() as $row) {
    $monthlyResults[$row['periode']] = (float)$row['total'];
  }

  $bulanIndonesia = [
    1=>'Januari', 2=>'Februari', 3=>'Maret', 4=>'April', 5=>'Mei', 6=>'Juni',
    7=>'Juli', 8=>'Agustus', 9=>'September', 10=>'Oktober', 11=>'November', 12=>'Desember'
  ];
  $data = [];
  $total = 0.0;
  $cursor = new DateTimeImmutable(substr($tgl1, 0, 7) . '-01');
  $lastMonth = new DateTimeImmutable(substr($tgl2, 0, 7) . '-01');
  while ($cursor <= $lastMonth) {
    $key = $cursor->format('Y-m');
    $amount = $monthlyResults[$key] ?? 0.0;
    $total += $amount;
    $data[] = [
      'periode' => $key,
      'bulan' => $bulanIndonesia[(int)$cursor->format('n')] . ' ' . $cursor->format('Y'),
      'total' => $amount
    ];
    $cursor = $cursor->modify('+1 month');
  }

  $nmPoli = null;
  if ($kdPoli !== '') {
    $stmtPoli = pdo()->prepare('SELECT nm_poli FROM poliklinik WHERE kd_poli = :kode LIMIT 1');
    $stmtPoli->execute([':kode' => $kdPoli]);
    $nmPoli = $stmtPoli->fetchColumn() ?: null;
  }

  $nmDokter = null;
  if ($kdDokter !== '') {
    $stmtDokter = pdo()->prepare('SELECT nm_dokter FROM dokter WHERE kd_dokter = :kode LIMIT 1');
    $stmtDokter->execute([':kode' => $kdDokter]);
    $nmDokter = $stmtDokter->fetchColumn() ?: null;
  }

  echo json_encode([
    'ok' => true,
    'total' => $total,
    'data' => $data,
    'filters' => ['tgl1'=>$tgl1, 'tgl2'=>$tgl2, 'nm_poli'=>$nmPoli, 'nm_dokter'=>$nmDokter]
  ], JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
} catch (InvalidArgumentException $e) {
  echo json_encode(['ok'=>false, 'message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  error_log('pendapatan-rajal-total: ' . $e->getMessage());
  echo json_encode(['ok'=>false, 'message'=>'Gagal mengambil data pendapatan.'], JSON_UNESCAPED_UNICODE);
}
