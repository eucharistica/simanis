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
  $allowedStatus = ['semua','piutang_belum','piutang_lunas','sudah_non_piutang','belum_closing'];
  if (!in_array($status, $allowedStatus, true)) $status = 'semua';

  $draw = max(1, (int)($_POST['draw'] ?? 1));
  $start = max(0, (int)($_POST['start'] ?? 0));
  $length = (int)($_POST['length'] ?? 25);
  if ($length < 1 || $length > 500) $length = 25;
  $exportAll = (string)($_POST['export_all'] ?? '') === '1';
  $search = $exportAll ? '' : trim((string)($_POST['search']['value'] ?? ''));

  $suffix = $mode === 'ralan' ? 'jl' : 'inap';
  $tableDokter = "rawat_{$suffix}_dr";
  $tableParamedis = "rawat_{$suffix}_pr";
  $tableGabungan = "rawat_{$suffix}_drpr";
  $jenis = $mode === 'ralan' ? 'jns_perawatan' : 'jns_perawatan_inap';

  if ($mode === 'ralan') {
    $dateColumn = 'rp.tgl_registrasi';
    $roomJoin = '';
    $actionDateDokter = 'rp.tgl_registrasi';
    $actionDateParamedis = 'rp.tgl_registrasi';
    $actionDateGabungan = 'rp.tgl_registrasi';
  } else {
    $dateColumn = 'ki.tgl_masuk';
    $roomKey = "CONCAT(COALESCE(NULLIF(ki.tgl_keluar,'0000-00-00'),'9999-12-31'),' ',COALESCE(ki.jam_keluar,'00:00:00'),'|',ki.tgl_masuk,' ',ki.jam_masuk,'|',ki.kd_kamar)";
    $roomJoin = "INNER JOIN kamar_inap ki ON ki.no_rawat=rp.no_rawat
      INNER JOIN (
        SELECT no_rawat,
          MAX(CONCAT(COALESCE(NULLIF(tgl_keluar,'0000-00-00'),'9999-12-31'),' ',COALESCE(jam_keluar,'00:00:00'),'|',tgl_masuk,' ',jam_masuk,'|',kd_kamar)) AS latest_key
        FROM kamar_inap GROUP BY no_rawat
      ) lk ON lk.no_rawat=ki.no_rawat AND lk.latest_key=$roomKey";
    $actionDateDokter = 'ad.tgl_perawatan';
    $actionDateParamedis = 'ap.tgl_perawatan';
    $actionDateGabungan = 'ag.tgl_perawatan';
  }

  $from = "FROM reg_periksa rp
    INNER JOIN pasien p ON p.no_rkm_medis=rp.no_rkm_medis
    INNER JOIN dokter d ON d.kd_dokter=rp.kd_dokter
    INNER JOIN penjab pj ON pj.kd_pj=rp.kd_pj
    $roomJoin";

  $hasAction = "(
    EXISTS (SELECT 1 FROM $tableDokter ed WHERE ed.no_rawat=rp.no_rawat AND ed.kd_dokter=rp.kd_dokter AND ed.tarif_tindakandr>0)
    OR EXISTS (SELECT 1 FROM $tableParamedis ep WHERE ep.no_rawat=rp.no_rawat AND ep.tarif_tindakanpr>0)
    OR EXISTS (SELECT 1 FROM $tableGabungan eg WHERE eg.no_rawat=rp.no_rawat AND eg.kd_dokter=rp.kd_dokter AND (COALESCE(eg.tarif_tindakandr,0)+COALESCE(eg.tarif_tindakanpr,0))>0)
  )";

  $where = ["$dateColumn BETWEEN :tgl1 AND :tgl2", $hasAction];
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

  $baseWhere = 'WHERE '.implode(' AND ', $where);
  $searchSql = '';
  if ($search !== '') {
    $searchSql = " AND (
      rp.no_rawat LIKE :s_rawat OR rp.no_rkm_medis LIKE :s_rm OR d.nm_dokter LIKE :s_dokter OR p.nm_pasien LIKE :s_pasien
      OR EXISTS (SELECT 1 FROM $tableDokter sd INNER JOIN $jenis sjd ON sjd.kd_jenis_prw=sd.kd_jenis_prw WHERE sd.no_rawat=rp.no_rawat AND sjd.nm_perawatan LIKE :s_td)
      OR EXISTS (SELECT 1 FROM $tableParamedis sp INNER JOIN $jenis sjp ON sjp.kd_jenis_prw=sp.kd_jenis_prw WHERE sp.no_rawat=rp.no_rawat AND sjp.nm_perawatan LIKE :s_tp)
      OR EXISTS (SELECT 1 FROM $tableGabungan sg INNER JOIN $jenis sjg ON sjg.kd_jenis_prw=sg.kd_jenis_prw WHERE sg.no_rawat=rp.no_rawat AND sjg.nm_perawatan LIKE :s_tg)
    )";
    foreach ([':s_rawat',':s_rm',':s_dokter',':s_pasien',':s_td',':s_tp',':s_tg'] as $key) $params[$key]='%'.$search.'%';
  }

  $group = 'GROUP BY rp.no_rawat, rp.no_rkm_medis, rp.kd_dokter, d.nm_dokter, p.nm_pasien, '.$dateColumn.($mode==='ralan'?', rp.stts':'');
  $countParams = array_filter($params, fn($key)=>!str_starts_with($key, ':s_'), ARRAY_FILTER_USE_KEY);
  $count = pdo()->prepare("SELECT COUNT(*) FROM (SELECT rp.no_rawat $from $baseWhere $group) counted");
  $count->execute($countParams);
  $recordsTotal = (int)$count->fetchColumn();

  if ($searchSql === '') {
    $recordsFiltered = $recordsTotal;
  } else {
    $filtered = pdo()->prepare("SELECT COUNT(*) FROM (SELECT rp.no_rawat $from $baseWhere $searchSql $group) counted");
    $filtered->execute($params);
    $recordsFiltered = (int)$filtered->fetchColumn();
  }

  $actionDokter = "(SELECT GROUP_CONCAT(CONCAT(DATE_FORMAT($actionDateDokter,'%Y-%m-%d'),'::',jd.nm_perawatan,' (',ad.kd_jenis_prw,')') ORDER BY $actionDateDokter,ad.jam_rawat,jd.nm_perawatan SEPARATOR '||')
    FROM $tableDokter ad INNER JOIN $jenis jd ON jd.kd_jenis_prw=ad.kd_jenis_prw
    WHERE ad.no_rawat=rp.no_rawat AND ad.kd_dokter=rp.kd_dokter AND ad.tarif_tindakandr>0)";
  $actionParamedis = "(SELECT GROUP_CONCAT(CONCAT(DATE_FORMAT($actionDateParamedis,'%Y-%m-%d'),'::',jp2.nm_perawatan,' (',ap.kd_jenis_prw,')') ORDER BY $actionDateParamedis,ap.jam_rawat,jp2.nm_perawatan SEPARATOR '||')
    FROM $tableParamedis ap INNER JOIN $jenis jp2 ON jp2.kd_jenis_prw=ap.kd_jenis_prw
    WHERE ap.no_rawat=rp.no_rawat AND ap.tarif_tindakanpr>0)";
  $actionGabungan = "(SELECT GROUP_CONCAT(CONCAT(DATE_FORMAT($actionDateGabungan,'%Y-%m-%d'),'::',jg.nm_perawatan,' (',ag.kd_jenis_prw,')') ORDER BY $actionDateGabungan,ag.jam_rawat,jg.nm_perawatan SEPARATOR '||')
    FROM $tableGabungan ag INNER JOIN $jenis jg ON jg.kd_jenis_prw=ag.kd_jenis_prw
    WHERE ag.no_rawat=rp.no_rawat AND ag.kd_dokter=rp.kd_dokter AND (COALESCE(ag.tarif_tindakandr,0)+COALESCE(ag.tarif_tindakanpr,0))>0)";

  $statusSelect = $mode==='ralan' ? ', rp.stts AS status' : '';
  $orderIndex = (int)($_POST['order'][0]['column'] ?? -1);
  $orderDir = strtolower((string)($_POST['order'][0]['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
  $orderColumns = [0=>'rp.no_rawat',1=>'rp.no_rkm_medis',2=>'d.nm_dokter',3=>'p.nm_pasien'];
  $orderBy = $orderColumns[$orderIndex] ?? $dateColumn;
  $limitSql = $exportAll ? '' : ' LIMIT :limit OFFSET :offset';

  $sql = "SELECT rp.no_rawat, rp.no_rkm_medis, d.nm_dokter, p.nm_pasien, $dateColumn AS tanggal_filter,
      $actionDokter AS tindakan_dokter,
      $actionParamedis AS tindakan_paramedis,
      $actionGabungan AS tindakan_gabungan
      $statusSelect
    $from $baseWhere $searchSql $group
    ORDER BY $orderBy $orderDir, rp.no_rawat ASC$limitSql";

  pdo()->exec('SET SESSION group_concat_max_len=1048576');
  $stmt = pdo()->prepare($sql);
  foreach ($params as $key=>$value) $stmt->bindValue($key,$value,PDO::PARAM_STR);
  if (!$exportAll) {
    $stmt->bindValue(':limit',$length,PDO::PARAM_INT);
    $stmt->bindValue(':offset',$start,PDO::PARAM_INT);
  }
  $stmt->execute();

  echo json_encode(['draw'=>$draw,'recordsTotal'=>$recordsTotal,'recordsFiltered'=>$recordsFiltered,'data'=>$stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $e) {
  http_response_code(422);
  echo json_encode(['draw'=>(int)($_POST['draw']??1),'recordsTotal'=>0,'recordsFiltered'=>0,'data'=>[],'message'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  error_log('tindakan-harian-dokter-data: '.$e->getMessage());
  echo json_encode(['draw'=>(int)($_POST['draw']??1),'recordsTotal'=>0,'recordsFiltered'=>0,'data'=>[],'message'=>'Gagal mengambil data tindakan dokter.'],JSON_UNESCAPED_UNICODE);
}
