<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

function vaksin_view_h($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$id = (int)($_GET['id'] ?? 0);
$tanggalMulai = (string)($_GET['tanggal_mulai'] ?? '');
$tanggalAkhir = (string)($_GET['tanggal_akhir'] ?? '');
$statusKemenkes = (string)($_GET['status_kemenkes'] ?? 'all');
if ($id < 1) {
    echo '<div class="alert alert-danger m-3">ID data tidak valid.</div>';
    return;
}

$backQuery = http_build_query(array_filter([
    'page' => 'vaksin_internasional',
    'tanggal_mulai' => $tanggalMulai,
    'tanggal_akhir' => $tanggalAkhir,
    'status_kemenkes' => $statusKemenkes,
], static fn($value) => $value !== ''));

$stmt = pdo()->prepare(
    'SELECT v.*, p.alamat
       FROM vaksin_internasional v
       LEFT JOIN pasien p ON p.no_rkm_medis = v.no_rkm_medis
      WHERE v.id = :id
      LIMIT 1'
);
$stmt->execute([':id' => $id]);
$row = $stmt->fetch();
if (!$row) {
    echo '<div class="alert alert-warning m-3">Data vaksin tidak ditemukan.</div>';
    return;
}

$consentDone = !empty($row['consent_signature_file']);
?>

<style>
  .vaksin-view .data-label { color: #6c757d; font-size: .8rem; text-transform: uppercase; letter-spacing: .03em; }
  .vaksin-view .data-value { font-weight: 500; white-space: pre-wrap; word-break: break-word; }
  .vaksin-view .signature-image { max-width: 100%; width: 700px; height: 220px; object-fit: contain; border: 1px dashed #adb5bd; background: #fff; }
  .vaksin-view .info-row { border-bottom: 1px solid #edf0f2; padding: .65rem 0; }
  .vaksin-view .info-row:last-child { border-bottom: 0; }
</style>

<div class="container-fluid px-2 px-md-3 py-3 vaksin-view">
  <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
    <div>
      <h3 class="mb-1 font-weight-bold">Form Vaksin Internasional</h3>
      <div class="text-muted">Mode baca saja — data tidak dapat diubah dari halaman ini.</div>
    </div>
    <a href="index.php?<?= htmlspecialchars($backQuery, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-secondary mt-2 mt-md-0"><i class="fas fa-arrow-left"></i> Kembali</a>
  </div>

  <div class="card mb-3">
    <div class="card-header"><strong>Data Pasien</strong></div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6 info-row"><div class="data-label">NIK</div><div class="data-value"><?= vaksin_view_h($row['no_ktp']) ?></div></div>
        <div class="col-md-6 info-row"><div class="data-label">No. Rekam Medis</div><div class="data-value"><?= vaksin_view_h($row['no_rkm_medis']) ?></div></div>
        <div class="col-md-6 info-row"><div class="data-label">Nama Lengkap</div><div class="data-value"><?= vaksin_view_h(trim(($row['fname'] ?? '') . ' ' . ($row['lname'] ?? ''))) ?></div></div>
        <div class="col-md-6 info-row"><div class="data-label">Jenis Kelamin</div><div class="data-value"><?= ($row['jk'] ?? '') === 'L' ? 'Laki-laki' : (($row['jk'] ?? '') === 'P' ? 'Perempuan' : '-') ?></div></div>
        <div class="col-md-6 info-row"><div class="data-label">Tanggal Lahir</div><div class="data-value"><?= vaksin_view_h($row['tgl_lahir']) ?></div></div>
        <div class="col-md-6 info-row"><div class="data-label">Alamat</div><div class="data-value"><?= vaksin_view_h($row['alamat']) ?></div></div>
        <div class="col-md-6 info-row"><div class="data-label">Email</div><div class="data-value"><?= vaksin_view_h($row['email']) ?></div></div>
        <div class="col-md-6 info-row"><div class="data-label">Nomor Telepon/WA</div><div class="data-value"><?= vaksin_view_h($row['no_wa']) ?></div></div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header"><strong>Data Vaksinasi</strong></div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-4 info-row"><div class="data-label">Tanggal Vaksin</div><div class="data-value"><?= vaksin_view_h($row['dov']) ?></div></div>
        <div class="col-md-8 info-row"><div class="data-label">Jenis Vaksin</div><div class="data-value"><?= vaksin_view_h($row['vaksin_nama']) ?></div></div>
        <div class="col-md-4 info-row"><div class="data-label">Kode Tindakan</div><div class="data-value"><?= vaksin_view_h($row['kd_jenis_prw']) ?></div></div>
        <div class="col-md-4 info-row"><div class="data-label">Keperluan</div><div class="data-value"><?= vaksin_view_h($row['keperluan']) ?></div></div>
        <div class="col-md-4 info-row"><div class="data-label">Nomor E-ICV / Buku Kuning</div><div class="data-value"><?= vaksin_view_h($row['no_eicv']) ?></div></div>
        <div class="col-md-4 info-row"><div class="data-label">Nomor Paspor</div><div class="data-value"><?= vaksin_view_h($row['no_paspor']) ?></div></div>
        <div class="col-md-8 info-row"><div class="data-label">File Paspor</div><div class="data-value">
          <?php if (!empty($row['passport_file'])): ?><a href="vaksin_file.php?id=<?= (int)$row['id'] ?>&amp;type=passport" target="_blank" rel="noopener noreferrer"><i class="fas fa-eye"></i> Lihat file paspor</a><?php else: ?>Tidak ada file paspor<?php endif; ?>
        </div></div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <strong>Form Persetujuan Tindakan Medis</strong>
      <?php if ($consentDone): ?><span class="badge badge-success"><i class="fas fa-check"></i> Sudah ditandatangani</span><?php else: ?><span class="badge badge-secondary">Belum lengkap</span><?php endif; ?>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6 info-row"><div class="data-label">Tindakan Kedokteran</div><div class="data-value"><?= vaksin_view_h($row['consent_action'] ?: 'Injeksi Vaksin') ?></div></div>
        <div class="col-md-6 info-row"><div class="data-label">Indikasi dan Tujuan</div><div class="data-value"><?= vaksin_view_h($row['consent_indication'] ?: 'Pencegahan Penyakit') ?></div></div>
        <div class="col-md-6 info-row"><div class="data-label">Tata Cara</div><div class="data-value"><?= vaksin_view_h($row['consent_procedure'] ?: 'Menyuntikkan vaksin ke tubuh') ?></div></div>
        <div class="col-md-6 info-row"><div class="data-label">Risiko</div><div class="data-value"><?= vaksin_view_h($row['consent_risk'] ?: 'Nyeri, Demam (KIPI)') ?></div></div>
        <div class="col-md-6 info-row"><div class="data-label">Komplikasi</div><div class="data-value"><?= vaksin_view_h($row['consent_complications'] ?: 'Flebitis') ?></div></div>
        <div class="col-md-6 info-row"><div class="data-label">Prognosis</div><div class="data-value"><?= vaksin_view_h($row['consent_prognosis'] ?: 'Dubia Ad Bonam') ?></div></div>
        <div class="col-md-4 info-row"><div class="data-label">Hubungan dengan Pasien</div><div class="data-value"><?= vaksin_view_h($row['consent_recipient_relation']) ?></div></div>
        <div class="col-md-8 info-row"><div class="data-label">Nama Penerima Informasi</div><div class="data-value"><?= vaksin_view_h($row['consent_recipient_name']) ?></div></div>
        <div class="col-md-8 info-row"><div class="data-label">Alamat Penerima Informasi</div><div class="data-value"><?= vaksin_view_h($row['consent_recipient_address']) ?></div></div>
        <div class="col-md-4 info-row"><div class="data-label">Telepon/WA Penerima</div><div class="data-value"><?= vaksin_view_h($row['consent_recipient_phone']) ?></div></div>
        <div class="col-12 info-row"><div class="data-label">Waktu Persetujuan</div><div class="data-value"><?= vaksin_view_h($row['consent_agreed_at']) ?></div></div>
      </div>
      <div class="mt-3">
        <div class="data-label mb-2">Tanda Tangan Elektronik</div>
        <?php if ($consentDone): ?>
          <img class="signature-image" src="vaksin_file.php?id=<?= (int)$row['id'] ?>&amp;type=signature" alt="Tanda tangan elektronik">
          <div class="small text-muted mt-2">Hash: <?= vaksin_view_h($row['consent_signature_hash']) ?></div>
        <?php else: ?>
          <div class="alert alert-warning mb-0">Tanda tangan belum tersimpan.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
