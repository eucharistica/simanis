<?php
declare(strict_types=1);

function antibiotikFilters(array $input): array {
    $dates = [];
    foreach (['tgl1','tgl2'] as $key) {
        $value = $input[$key] ?? '';
        if (is_string($value) && preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $value, $m)) $value = $m[3].'-'.$m[1].'-'.$m[2];
        if (!is_string($value)) throw new InvalidArgumentException('Tanggal tidak valid.');
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) throw new InvalidArgumentException('Tanggal mulai dan akhir wajib valid.');
        $dates[$key] = $value;
    }
    if ($dates['tgl1'] > $dates['tgl2']) throw new InvalidArgumentException('Tanggal mulai tidak boleh melewati tanggal akhir.');
    $jenis = $input['jenis'] ?? [];
    if (!is_array($jenis)) $jenis = [];
    if (!$jenis) $jenis = ['J120','J160','J161'];
    foreach ($jenis as $code) {
        if (!is_string($code) || !in_array($code, ['J120','J160','J161'], true)) throw new InvalidArgumentException('Jenis antibiotik tidak valid.');
    }
    $obat = $input['obat'] ?? [];
    if (!is_array($obat)) $obat = [$obat];
    $obat = array_values(array_filter(array_map('trim', $obat)));
    return $dates + ['jenis'=>array_values(array_unique($jenis)), 'obat'=>array_values(array_unique($obat))];
}

function antibiotikLos(string $masuk, ?string $keluar, string $today): ?int {
    $end = $keluar ?? $today;
    if ($end < $masuk) return null;
    return max(1, (int)(new DateTimeImmutable($masuk))->diff(new DateTimeImmutable($end))->days);
}

function antibiotikRows(PDO $db, array $filters): array {
    // Aggregate rooms BEFORE joining medication, preventing transfer-related multiplication.
    // All Ranap medication for the selected admission is included, not only dates in the filter.
    $in = implode(',', array_fill(0, count($filters['jenis']), '?'));
    $sql = "SELECT rp.no_rawat, rp.no_rkm_medis, p.nm_pasien,
        stay.tgl_masuk, stay.tgl_keluar, stay.masih_dirawat,
        b.kode_brng, b.nama_brng, b.kdjns, j.nama AS jenis, MAX(ap.aturan) AS aturan_pakai,
        COALESCE(s.satuan,b.kode_sat) AS satuan, o.tgl_perawatan, o.jam, SUM(o.jml) AS jumlah
      FROM (
        SELECT no_rawat, MIN(NULLIF(tgl_masuk,'0000-00-00')) AS tgl_masuk,
          MAX(NULLIF(tgl_keluar,'0000-00-00')) AS tgl_keluar,
          MAX(CASE WHEN tgl_keluar IS NULL OR tgl_keluar='0000-00-00' THEN 1 ELSE 0 END) AS masih_dirawat
        FROM kamar_inap GROUP BY no_rawat
      ) stay
      INNER JOIN reg_periksa rp ON rp.no_rawat=stay.no_rawat
      INNER JOIN pasien p ON p.no_rkm_medis=rp.no_rkm_medis
      INNER JOIN detail_pemberian_obat o ON o.no_rawat=stay.no_rawat AND o.status='Ranap'
      LEFT JOIN aturan_pakai ap ON ap.no_rawat=o.no_rawat AND ap.kode_brng=o.kode_brng AND ap.tgl_perawatan=o.tgl_perawatan AND ap.jam=o.jam
      INNER JOIN databarang b ON b.kode_brng=o.kode_brng
      INNER JOIN jenis j ON j.kdjns=b.kdjns
      LEFT JOIN kodesatuan s ON s.kode_sat=b.kode_sat
      WHERE stay.tgl_masuk BETWEEN ? AND ? AND b.kdjns IN ($in)" . (empty($filters['obat']) ? '' : " AND b.kode_brng IN (".implode(',',array_fill(0,count($filters['obat']),'?')).")") . "
      GROUP BY rp.no_rawat,rp.no_rkm_medis,p.nm_pasien,stay.tgl_masuk,stay.tgl_keluar,stay.masih_dirawat,
        b.kode_brng,b.nama_brng,b.kdjns,j.nama,s.satuan,b.kode_sat,o.tgl_perawatan,o.jam
      ORDER BY stay.tgl_masuk DESC,rp.no_rawat,b.nama_brng,b.kode_brng";
    $stmt = $db->prepare($sql);
    $stmt->execute(array_merge([$filters['tgl1'],$filters['tgl2']],$filters['jenis'],$filters['obat']));
    $patients = [];
    $today = date('Y-m-d');
    while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $key = $item['no_rawat'];
        if (!isset($patients[$key])) {
            $out = (int)$item['masih_dirawat'] === 1 ? null : $item['tgl_keluar'];
            $patients[$key] = [
                'no_rawat'=>$key,'no_rkm_medis'=>$item['no_rkm_medis'],'nm_pasien'=>$item['nm_pasien'],
                'tgl_masuk'=>$item['tgl_masuk'],'tgl_keluar'=>$out,
                'status_rawat'=>$out === null ? 'Masih dirawat' : 'Pulang',
                'los'=>antibiotikLos($item['tgl_masuk'],$out,$today),
                'obat'=>[],'total_jml'=>0.0
            ];
        }
        $qty = (float)$item['jumlah'];
        $patients[$key]['obat'][] = ['kode'=>$item['kode_brng'],'nama'=>$item['nama_brng'],
            'jenis'=>$item['jenis'],'kdjns'=>$item['kdjns'],'aturan_pakai'=>$item['aturan_pakai'],'tanggal_pemberian'=>$item['tgl_perawatan'].' '.$item['jam'],'satuan'=>$item['satuan'],'jumlah'=>$qty];
        $patients[$key]['total_jml'] += $qty;
    }
    $rows=array_values($patients); $totalLos=0; $totalQty=0;
    foreach($rows as $r){$totalLos+=(int)$r['los'];$totalQty+=(float)$r['total_jml'];}
    return ['rows'=>$rows,'summary'=>['total_los'=>$totalLos,'total_jumlah'=>$totalQty]];
}
