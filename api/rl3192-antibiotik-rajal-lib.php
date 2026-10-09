<?php
declare(strict_types=1);

const RL3192_POLI = ['U0024','U0034','U0025','U0027'];
const RL3192_ANTIBIOTIK = ['J120','J160','J161'];

function rl3192Date(string $value): ?string {
    $value = substr($value, 0, 10);
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $value : null;
}

function rl3192Filters(array $input): array {
    $start = rl3192Date((string)($input['startDate'] ?? ''));
    $end = rl3192Date((string)($input['endDate'] ?? ''));
    if (!$start || !$end || $start > $end) {
        throw new InvalidArgumentException('Rentang tanggal tidak valid.');
    }
    return ['startDate'=>$start, 'endDate'=>$end];
}

function rl3192Rows(PDO $db, array $filters): array {
    $poliPlaceholders = implode(',', array_fill(0, count(RL3192_POLI), '?'));
    $drugPlaceholders = implode(',', array_fill(0, count(RL3192_ANTIBIOTIK), '?'));
    $sql = "SELECT poli.kd_poli, poli.nm_poli,
        COALESCE(ab.jumlah_antibiotik,0) AS jumlah_antibiotik,
        COALESCE(sample.jumlah_sampel,0) AS jumlah_sampel
      FROM poliklinik poli
      LEFT JOIN (
        SELECT rp.kd_poli, COUNT(DISTINCT dpo.no_rawat) AS jumlah_antibiotik
        FROM detail_pemberian_obat dpo
        INNER JOIN databarang db ON db.kode_brng=dpo.kode_brng
        INNER JOIN reg_periksa rp ON rp.no_rawat=dpo.no_rawat
        WHERE dpo.status='Ralan'
          AND dpo.tgl_perawatan BETWEEN ? AND ?
          AND db.kdjns IN ($drugPlaceholders)
          AND rp.kd_poli IN ($poliPlaceholders)
          AND rp.status_lanjut='Ralan'
          AND rp.stts<>'Batal'
        GROUP BY rp.kd_poli
      ) ab ON ab.kd_poli=poli.kd_poli
      LEFT JOIN (
        SELECT rp.kd_poli, COUNT(DISTINCT rp.no_rawat) AS jumlah_sampel
        FROM reg_periksa rp
        WHERE rp.tgl_registrasi BETWEEN ? AND ?
          AND rp.kd_poli IN ($poliPlaceholders)
          AND rp.status_lanjut='Ralan'
          AND rp.stts<>'Batal'
        GROUP BY rp.kd_poli
      ) sample ON sample.kd_poli=poli.kd_poli
      WHERE poli.kd_poli IN ($poliPlaceholders)
      ORDER BY FIELD(poli.kd_poli,'U0024','U0034','U0025','U0027')";
    $params = array_merge(
        [$filters['startDate'],$filters['endDate']],
        RL3192_ANTIBIOTIK,
        RL3192_POLI,
        [$filters['startDate'],$filters['endDate']],
        RL3192_POLI,
        RL3192_POLI
    );
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $index=>$row) {
        $antibiotik = (int)$row['jumlah_antibiotik'];
        $sampel = (int)$row['jumlah_sampel'];
        $rows[] = [
            'no'=>$index+1,
            'kd_poli'=>$row['kd_poli'],
            'nm_poli'=>$row['nm_poli'],
            'jumlah_antibiotik'=>$antibiotik,
            'jumlah_sampel'=>$sampel,
            'persen'=>$sampel > 0 ? round($antibiotik/$sampel*100, 2) : 0.0
        ];
    }
    return $rows;
}
