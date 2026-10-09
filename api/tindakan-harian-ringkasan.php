<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

function validDate(string $value): ?string {
  $value = substr($value, 0, 10);
  $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
  return $date && $date->format('Y-m-d') === $value ? $value : null;
}

try {
  $mode = ($_POST['mode'] ?? 'ralan') === 'ranap' ? 'ranap' : 'ralan';
  $tgl1 = validDate((string)($_POST['tgl1'] ?? ''));
  $tgl2 = validDate((string)($_POST['tgl2'] ?? ''));
  if (!$tgl1 || !$tgl2 || $tgl1 > $tgl2) throw new InvalidArgumentException('Rentang tanggal tidak valid.');

  $kdPj = trim((string)($_POST['kd_pj'] ?? ''));
  $kdDokter = trim((string)($_POST['kd_dokter'] ?? ''));
  $status = (string)($_POST['status'] ?? 'semua');
  if (!in_array($status, ['semua','piutang_belum','piutang_lunas','sudah_non_piutang','belum_closing'], true)) $status='semua';

  $suffix = $mode === 'ralan' ? 'jl' : 'inap';
  $tableDokter = "rawat_{$suffix}_dr";
  $tableParamedis = "rawat_{$suffix}_pr";
  $tableGabungan = "rawat_{$suffix}_drpr";
  $jenis = $mode === 'ralan' ? 'jns_perawatan' : 'jns_perawatan_inap';

  if ($mode === 'ralan') {
    $dateColumn = 'rp.tgl_registrasi';
    $roomJoin = '';
  } else {
    $dateColumn = 'ki.tgl_masuk';
    $roomKey = "CONCAT(COALESCE(NULLIF(ki.tgl_keluar,'0000-00-00'),'9999-12-31'),' ',COALESCE(ki.jam_keluar,'00:00:00'),'|',ki.tgl_masuk,' ',ki.jam_masuk,'|',ki.kd_kamar)";
    $roomJoin = "INNER JOIN kamar_inap ki ON ki.no_rawat=rp.no_rawat
      INNER JOIN (
        SELECT no_rawat,
          MAX(CONCAT(COALESCE(NULLIF(tgl_keluar,'0000-00-00'),'9999-12-31'),' ',COALESCE(jam_keluar,'00:00:00'),'|',tgl_masuk,' ',jam_masuk,'|',kd_kamar)) AS latest_key
        FROM kamar_inap GROUP BY no_rawat
      ) lk ON lk.no_rawat=ki.no_rawat AND lk.latest_key=$roomKey";
  }

  $where = ["$dateColumn BETWEEN :tgl1 AND :tgl2", "(a.sumber='pr' OR a.kd_dokter=rp.kd_dokter)"];
  $params = [':tgl1'=>$tgl1, ':tgl2'=>$tgl2];
  if ($kdPj !== '') { $where[]='rp.kd_pj=:kd_pj'; $params[':kd_pj']=$kdPj; }
  if ($kdDokter !== '') { $where[]='rp.kd_dokter=:kd_dokter'; $params[':kd_dokter']=$kdDokter; }

  switch ($status) {
    case 'piutang_belum':
      $where[]="rp.status_bayar='Sudah Bayar'";
      $where[]="EXISTS (SELECT 1 FROM piutang_pasien pp WHERE pp.no_rawat=rp.no_rawat AND pp.status='Belum Lunas')";
      break;
    case 'piutang_lunas':
      $where[]="rp.status_bayar='Sudah Bayar'";
      $where[]="EXISTS (SELECT 1 FROM piutang_pasien pp WHERE pp.no_rawat=rp.no_rawat AND pp.status='Lunas')";
      break;
    case 'sudah_non_piutang':
      $where[]="rp.status_bayar='Sudah Bayar'";
      $where[]='NOT EXISTS (SELECT 1 FROM piutang_pasien pp WHERE pp.no_rawat=rp.no_rawat)';
      break;
    case 'belum_closing':
      $where[]="rp.status_bayar='Belum Bayar'";
      break;
  }

  // Tindakan gabungan muncul dua kali (peran dokter dan paramedis), tetapi event_id
  // tetap sama sehingga jumlah kejadian unik tidak terhitung ganda.
  $actions = "
    SELECT no_rawat,kd_jenis_prw,kd_dokter,'dokter' AS peran,'dr' AS sumber,
      CONCAT('dr|',no_rawat,'|',kd_jenis_prw,'|',kd_dokter,'|',tgl_perawatan,'|',jam_rawat) AS event_id
    FROM $tableDokter WHERE tarif_tindakandr>0
    UNION ALL
    SELECT no_rawat,kd_jenis_prw,NULL AS kd_dokter,'paramedis' AS peran,'pr' AS sumber,
      CONCAT('pr|',no_rawat,'|',kd_jenis_prw,'|',nip,'|',tgl_perawatan,'|',jam_rawat) AS event_id
    FROM $tableParamedis WHERE tarif_tindakanpr>0
    UNION ALL
    SELECT no_rawat,kd_jenis_prw,kd_dokter,'dokter' AS peran,'drpr' AS sumber,
      CONCAT('drpr|',no_rawat,'|',kd_jenis_prw,'|',kd_dokter,'|',nip,'|',tgl_perawatan,'|',jam_rawat) AS event_id
    FROM $tableGabungan WHERE (COALESCE(tarif_tindakandr,0)+COALESCE(tarif_tindakanpr,0))>0
    UNION ALL
    SELECT no_rawat,kd_jenis_prw,kd_dokter,'paramedis' AS peran,'drpr' AS sumber,
      CONCAT('drpr|',no_rawat,'|',kd_jenis_prw,'|',kd_dokter,'|',nip,'|',tgl_perawatan,'|',jam_rawat) AS event_id
    FROM $tableGabungan WHERE (COALESCE(tarif_tindakandr,0)+COALESCE(tarif_tindakanpr,0))>0
  ";

  $sql = "SELECT rp.kd_dokter,d.nm_dokter,a.kd_jenis_prw,jp.nm_perawatan,
      SUM(a.peran='dokter') AS jumlah_dokter,
      SUM(a.peran='paramedis') AS jumlah_paramedis,
      COUNT(DISTINCT a.event_id) AS jumlah_unik
    FROM reg_periksa rp
    INNER JOIN pasien p ON p.no_rkm_medis=rp.no_rkm_medis
    INNER JOIN dokter d ON d.kd_dokter=rp.kd_dokter
    INNER JOIN penjab pj ON pj.kd_pj=rp.kd_pj
    $roomJoin
    INNER JOIN ($actions) a ON a.no_rawat=rp.no_rawat
    INNER JOIN $jenis jp ON jp.kd_jenis_prw=a.kd_jenis_prw
    WHERE ".implode(' AND ', $where)."
    GROUP BY rp.kd_dokter,d.nm_dokter,a.kd_jenis_prw,jp.nm_perawatan
    ORDER BY d.nm_dokter,jp.nm_perawatan";

  $stmt = pdo()->prepare($sql);
  $stmt->execute($params);
  echo json_encode(['ok'=>true,'data'=>$stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $e) {
  http_response_code(422);
  echo json_encode(['ok'=>false,'data'=>[],'message'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  error_log('tindakan-harian-ringkasan: '.$e->getMessage());
  echo json_encode(['ok'=>false,'data'=>[],'message'=>'Gagal mengambil ringkasan tindakan.'],JSON_UNESCAPED_UNICODE);
}
