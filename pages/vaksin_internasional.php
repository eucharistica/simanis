<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

function vaksin_page_h($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$tanggalMulai = trim((string)($_GET['tanggal_mulai'] ?? date('Y-m-01')));
$tanggalAkhir = trim((string)($_GET['tanggal_akhir'] ?? date('Y-m-d')));
$statusKemenkes = (string)($_GET['status_kemenkes'] ?? 'all');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalMulai)) $tanggalMulai = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalAkhir)) $tanggalAkhir = date('Y-m-d');
if (!in_array($statusKemenkes, ['all', 'done', 'not'], true)) $statusKemenkes = 'all';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_kemkes'], $_POST['kemkes_id'])) {
    $markId = (int)$_POST['kemkes_id'];
    if ($markId > 0) {
        querywrite("UPDATE vaksin_internasional
                       SET kemkes_done = 1, kemkes_done_at = NOW()
                     WHERE id = {$markId}
                     LIMIT 1");
    }
}

$whereStatus = '';
if ($statusKemenkes === 'done') $whereStatus = ' AND v.kemkes_done = 1';
if ($statusKemenkes === 'not') $whereStatus = ' AND (v.kemkes_done = 0 OR v.kemkes_done IS NULL)';

$stmt = pdo()->prepare(
    "SELECT v.*, p.alamat
       FROM vaksin_internasional v
       LEFT JOIN pasien p ON p.no_rkm_medis = v.no_rkm_medis
      WHERE v.dov BETWEEN :mulai AND :akhir
        {$whereStatus}
      ORDER BY v.dov DESC, v.created_at DESC, v.id DESC"
);
$stmt->execute([':mulai' => $tanggalMulai, ':akhir' => $tanggalAkhir]);
$rows = $stmt->fetchAll();
?>

<style>
  .vaksin-page .filter-card label { font-weight: 600; font-size: .85rem; }
  .vaksin-page .table td, .vaksin-page .table th { vertical-align: middle; }
  .vaksin-page .status-consent { white-space: nowrap; }
  .vaksin-page .table-responsive { min-height: 180px; }
  .vaksin-page td.copyable-cell { cursor: pointer; position: relative; }
  .vaksin-page td.copyable-cell:hover { background-color: #e8f4ff; }
  .vaksin-page .copy-feedback {
    position: absolute; z-index: 2; top: 50%; left: 50%;
    transform: translate(-50%, -50%); padding: .2rem .45rem;
    border-radius: .2rem; background: #72c98b; color: #fff;
    font-size: .75rem; pointer-events: none;
  }
</style>

<div class="container-fluid px-2 px-md-3 py-3 vaksin-page">
  <div class="mb-3">
    <h3 class="mb-1 font-weight-bold">Vaksin Internasional</h3>
    <div class="text-muted">Monitoring data vaksinasi, persetujuan tindakan medis, dan tanda tangan elektronik.</div>
  </div>

  <div class="card filter-card mb-3">
    <div class="card-header py-2"><strong>Filter Data</strong></div>
    <form method="get" action="index.php">
      <input type="hidden" name="page" value="vaksin_internasional">
      <div class="card-body">
        <div class="form-row align-items-end">
          <div class="form-group col-12 col-md-3 mb-md-0">
            <label for="tanggal_mulai">Tanggal Vaksin Mulai</label>
            <input type="date" class="form-control form-control-sm" id="tanggal_mulai" name="tanggal_mulai" value="<?= vaksin_page_h($tanggalMulai) ?>">
          </div>
          <div class="form-group col-12 col-md-3 mb-md-0">
            <label for="tanggal_akhir">Tanggal Vaksin Sampai</label>
            <input type="date" class="form-control form-control-sm" id="tanggal_akhir" name="tanggal_akhir" value="<?= vaksin_page_h($tanggalAkhir) ?>">
          </div>
          <div class="form-group col-12 col-md-3 mb-md-0">
            <label for="status_kemenkes">Status Kemenkes</label>
            <select class="form-control form-control-sm" id="status_kemenkes" name="status_kemenkes">
              <option value="all" <?= $statusKemenkes === 'all' ? 'selected' : '' ?>>Semua</option>
              <option value="done" <?= $statusKemenkes === 'done' ? 'selected' : '' ?>>Sudah input</option>
              <option value="not" <?= $statusKemenkes === 'not' ? 'selected' : '' ?>>Belum input</option>
            </select>
          </div>
          <div class="form-group col-12 col-md-2 mb-md-0">
            <button type="submit" class="btn btn-sm btn-primary btn-block"><i class="fas fa-search"></i> Tampilkan</button>
          </div>
        </div>
      </div>
    </form>
  </div>

  <div class="card">
    <div class="card-header py-2 d-flex align-items-center justify-content-between">
      <strong>Data Vaksinasi</strong>
      <span class="badge badge-info"><?= count($rows) ?> data</span>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table id="dtVaksinInternasional" class="table table-bordered table-striped table-sm w-100">
          <thead>
            <tr>
              <th>No</th>
              <th>Tanggal Vaksin</th>
              <th>Nama</th>
              <th>NIK</th>
              <th>No. RM</th>
              <th>Email</th>
              <th>Jenis Vaksin</th>
              <th>Keperluan</th>
              <th>No. E-ICV</th>
              <th>No. Paspor</th>
              <th>Consent</th>
              <th>Kemenkes</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $index => $row): ?>
            <?php $consentDone = !empty($row['consent_signature_file']); ?>
            <?php
              $nama = trim(($row['fname'] ?? '') . ' ' . ($row['lname'] ?? ''));
              $detailQuery = http_build_query([
                  'page' => 'vaksin_internasional_view',
                  'id' => (int)$row['id'],
                  'tanggal_mulai' => $tanggalMulai,
                  'tanggal_akhir' => $tanggalAkhir,
                  'status_kemenkes' => $statusKemenkes,
              ]);
            ?>
            <tr>
              <td><?= $index + 1 ?></td>
              <td class="copyable-cell" data-copy-cell="<?= vaksin_page_h($row['dov']) ?>"><?= vaksin_page_h($row['dov']) ?></td>
              <td class="copyable-cell" data-copy-cell="<?= vaksin_page_h($nama) ?>"><?= vaksin_page_h($nama) ?></td>
              <td class="copyable-cell" data-copy-cell="<?= vaksin_page_h($row['no_ktp']) ?>"><?= vaksin_page_h($row['no_ktp']) ?></td>
              <td class="copyable-cell" data-copy-cell="<?= vaksin_page_h($row['no_rkm_medis']) ?>"><?= vaksin_page_h($row['no_rkm_medis']) ?></td>
              <td class="copyable-cell" data-copy-cell="<?= vaksin_page_h($row['email']) ?>"><?= vaksin_page_h($row['email']) ?></td>
              <td class="copyable-cell" data-copy-cell="<?= vaksin_page_h($row['vaksin_nama']) ?>"><?= vaksin_page_h($row['vaksin_nama']) ?></td>
              <td class="copyable-cell" data-copy-cell="<?= vaksin_page_h($row['keperluan']) ?>"><?= vaksin_page_h($row['keperluan']) ?></td>
              <td class="copyable-cell" data-copy-cell="<?= vaksin_page_h($row['no_eicv']) ?>"><?= vaksin_page_h($row['no_eicv']) ?></td>
              <td class="copyable-cell" data-copy-cell="<?= vaksin_page_h($row['no_paspor']) ?>"><?= vaksin_page_h($row['no_paspor']) ?></td>
              <td class="status-consent">
                <?php if ($consentDone): ?>
                  <span class="badge badge-success"><i class="fas fa-check"></i> Sudah TTD</span>
                  <?php if (!empty($row['consent_agreed_at'])): ?><br><small><?= vaksin_page_h($row['consent_agreed_at']) ?></small><?php endif; ?>
                <?php else: ?>
                  <span class="badge badge-secondary">Belum</span>
                <?php endif; ?>
              </td>
              <td class="status-consent">
                <?php if (!empty($row['kemkes_done'])): ?>
                  <span class="badge badge-success">Sudah input</span><br><small><?= vaksin_page_h($row['kemkes_done_at']) ?></small>
                <?php else: ?>
                  <form method="post" class="d-inline">
                    <input type="hidden" name="kemkes_id" value="<?= (int)$row['id'] ?>">
                    <button type="submit" name="mark_kemkes" class="btn btn-xs btn-outline-success" title="Tandai sudah input ke Kemenkes"><i class="fas fa-clipboard-check"></i></button>
                  </form>
                  <span class="badge badge-secondary">Belum</span>
                <?php endif; ?>
              </td>
              <td class="text-nowrap">
                <a class="btn btn-sm btn-primary" href="index.php?<?= vaksin_page_h($detailQuery) ?>">
                  <i class="fas fa-eye"></i> Lihat Form
                </a>
                <?php if (!empty($row['passport_file'])): ?>
                  <a class="btn btn-sm btn-outline-secondary" href="vaksin_file.php?id=<?= (int)$row['id'] ?>&amp;type=passport" target="_blank" rel="noopener noreferrer" title="Lihat paspor"><i class="fas fa-passport"></i></a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
window.addEventListener('load', function () {
  var table = document.getElementById('dtVaksinInternasional');
  if (table) table.addEventListener('click', function (event) {
    var cell = event.target.closest('td.copyable-cell');
    if (!cell || !table.contains(cell)) return;
    var text = cell.getAttribute('data-copy-cell') || '';
    if (!text) return;
    var done = function () {
      var old = cell.querySelector('.copy-feedback');
      if (old) old.remove();
      var badge = document.createElement('span');
      badge.className = 'copy-feedback';
      badge.textContent = 'Copied!';
      cell.appendChild(badge);
      setTimeout(function () { badge.remove(); }, 1200);
    };
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text).then(done);
    } else {
      var area = document.createElement('textarea');
      area.value = text;
      area.style.position = 'fixed';
      area.style.opacity = '0';
      document.body.appendChild(area);
      area.focus();
      area.select();
      try { document.execCommand('copy'); done(); } finally { document.body.removeChild(area); }
    }
  });

  if (window.jQuery && $.fn.DataTable && !$.fn.dataTable.isDataTable('#dtVaksinInternasional')) {
    $('#dtVaksinInternasional').DataTable({
      responsive: true,
      autoWidth: false,
      pageLength: 25,
      order: [[1, 'desc']],
      dom: 'lBfrtip',
      buttons: [
        { extend: 'copyHtml5', text: 'Copy' },
        { extend: 'excelHtml5', text: 'Excel', title: 'Vaksin Internasional' },
        { extend: 'print', text: 'Print' }
      ],
      language: { emptyTable: 'Belum ada data pada rentang tanggal ini.' }
    });
  }
});
</script>
