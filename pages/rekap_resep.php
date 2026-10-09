<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

$today = date('Y-m-d');
$renderPanel = static function (string $key, string $rawatLabel, string $resepLabel): void {
    ?>
    <div class="row mb-2">
      <div class="col-md-4 mb-3">
        <div class="summary-card">
          <div class="summary-label">Jumlah Resep <?= $resepLabel ?> <?= $rawatLabel ?></div>
          <div class="summary-value" id="<?= $key ?>_jml_resep">0</div>
        </div>
      </div>
      <div class="col-md-4 mb-3">
        <div class="summary-card">
          <div class="summary-label">Jumlah Obat Keluar</div>
          <div class="summary-value" id="<?= $key ?>_jml_obat">0</div>
        </div>
      </div>
      <div class="col-md-4 mb-3">
        <div class="summary-card">
          <div class="summary-label">Total Pengeluaran Obat</div>
          <div class="summary-value" id="<?= $key ?>_total_biaya">0</div>
        </div>
      </div>
    </div>

    <div class="card card-soft mb-3">
      <div class="card-header card-soft-header-primary py-2">
        <div class="card-section-title">Detail Obat <?= $resepLabel ?> <?= $rawatLabel ?></div>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table id="tbl_<?= $key ?>" class="table table-striped table-bordered nowrap" style="width: 100%">
            <thead>
              <tr>
                <th>Kode Obat</th>
                <th>Nama Obat</th>
                <th>Jumlah Keluar</th>
                <th>Total Biaya</th>
              </tr>
            </thead>
            <tfoot>
              <tr>
                <th colspan="2" class="text-right">Total:</th>
                <th></th>
                <th></th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
    <?php
};
?>
<style>
.rekap-resep-page .filter-card label {
  font-weight: 600;
  font-size: .85rem;
  margin-bottom: .35rem;
}
.rekap-resep-page .summary-card {
  border: 1px solid #e9ecef;
  border-radius: .75rem;
  padding: 1rem;
  background: #fff;
  height: 100%;
}
.rekap-resep-page .summary-label {
  font-size: .85rem;
  color: #6c757d;
  margin-bottom: .35rem;
}
.rekap-resep-page .summary-value {
  font-size: 1.6rem;
  font-weight: 700;
  line-height: 1.2;
}
.rekap-resep-page .nav-tabs .nav-link {
  font-weight: 600;
}
.rekap-resep-page .resep-kind-tabs {
  border-bottom: 0;
}
.rekap-resep-page .resep-kind-tabs .nav-link {
  border: 1px solid #dee2e6;
  margin-right: .35rem;
  border-radius: .35rem;
}
.rekap-resep-page .resep-kind-tabs .nav-link.active {
  color: #fff;
  background: #007bff;
  border-color: #007bff;
}
.rekap-resep-page .dataTables_wrapper .dt-buttons {
  margin-bottom: .5rem;
}
.rekap-resep-page table.dataTable thead th {
  vertical-align: middle;
  text-align: center;
  white-space: nowrap;
}
.rekap-resep-page .text-right {
  text-align: right !important;
}
</style>

<div class="container-fluid px-2 px-md-3 py-3 rekap-resep-page">
  <div class="mb-3">
    <h3 class="mb-1 font-weight-bold">Rekap Resep</h3>
    <div class="text-muted">Rekap resep rawat jalan dan rawat inap, racik maupun non-racik</div>
  </div>

  <div id="alertContainer"></div>

  <div class="card card-soft filter-card mb-3">
    <div class="card-header card-soft-header py-2">
      <div class="card-section-title">Filter Tanggal</div>
    </div>
    <div class="card-body">
      <div class="form-row align-items-end">
        <div class="form-group col-md-2">
          <label for="tgl1">Tanggal Mulai</label>
          <input type="date" class="form-control form-control-sm" id="tgl1" value="<?= $today ?>">
        </div>
        <div class="form-group col-md-2">
          <label for="tgl2">Tanggal Akhir</label>
          <input type="date" class="form-control form-control-sm" id="tgl2" value="<?= $today ?>">
        </div>
      </div>
    </div>
    <div class="card-footer bg-white py-2">
      <div class="d-flex" style="gap: 8px">
        <button type="button" class="btn btn-sm btn-primary" id="btnFilter">Terapkan</button>
        <button type="button" class="btn btn-sm btn-secondary" id="btnReset">Reset</button>
      </div>
    </div>
  </div>

  <ul class="nav nav-tabs mb-3" id="rawatTab" role="tablist">
    <li class="nav-item">
      <a class="nav-link active rawat-tab" id="rawat-ralan-tab" data-toggle="tab" data-rawat="ralan" href="#rawat-ralan" role="tab">Rawat Jalan</a>
    </li>
    <li class="nav-item">
      <a class="nav-link rawat-tab" id="rawat-ranap-tab" data-toggle="tab" data-rawat="ranap" href="#rawat-ranap" role="tab">Rawat Inap</a>
    </li>
  </ul>

  <div class="tab-content">
    <div class="tab-pane fade show active" id="rawat-ralan" role="tabpanel">
      <ul class="nav nav-tabs resep-kind-tabs mb-3" role="tablist">
        <li class="nav-item">
          <a class="nav-link active resep-kind-tab" data-toggle="tab" data-key="ralan_nonracik" href="#ralan-nonracik" role="tab">Non Racik</a>
        </li>
        <li class="nav-item">
          <a class="nav-link resep-kind-tab" data-toggle="tab" data-key="ralan_racik" href="#ralan-racik" role="tab">Racik</a>
        </li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane fade show active" id="ralan-nonracik" role="tabpanel">
          <?php $renderPanel('ralan_nonracik', 'Rawat Jalan', 'Non Racik'); ?>
        </div>
        <div class="tab-pane fade" id="ralan-racik" role="tabpanel">
          <?php $renderPanel('ralan_racik', 'Rawat Jalan', 'Racik'); ?>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="rawat-ranap" role="tabpanel">
      <ul class="nav nav-tabs resep-kind-tabs mb-3" role="tablist">
        <li class="nav-item">
          <a class="nav-link active resep-kind-tab" data-toggle="tab" data-key="ranap_nonracik" href="#ranap-nonracik" role="tab">Non Racik</a>
        </li>
        <li class="nav-item">
          <a class="nav-link resep-kind-tab" data-toggle="tab" data-key="ranap_racik" href="#ranap-racik" role="tab">Racik</a>
        </li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane fade show active" id="ranap-nonracik" role="tabpanel">
          <?php $renderPanel('ranap_nonracik', 'Rawat Inap', 'Non Racik'); ?>
        </div>
        <div class="tab-pane fade" id="ranap-racik" role="tabpanel">
          <?php $renderPanel('ranap_racik', 'Rawat Inap', 'Racik'); ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const API = {
  summary: 'api/rekap-resep-summary.php',
  nonracik: 'api/rekap-resep-nonracik-datatable.php',
  racik: 'api/rekap-resep-racik-datatable.php'
};

const TODAY = <?= json_encode($today, JSON_UNESCAPED_UNICODE) ?>;
const REPORTS = {
  ralan_nonracik: { rawat: 'ralan', resep: 'nonracik' },
  ralan_racik: { rawat: 'ralan', resep: 'racik' },
  ranap_nonracik: { rawat: 'ranap', resep: 'nonracik' },
  ranap_racik: { rawat: 'ranap', resep: 'racik' }
};
const tables = {};
const loadedSummaries = new Set();

function showAlert(message, type = 'info') {
  const container = document.getElementById('alertContainer');
  const alert = document.createElement('div');
  alert.className = `alert alert-${type === 'error' ? 'danger' : 'info'} py-2 mb-2`;
  alert.textContent = message;
  container.appendChild(alert);
  setTimeout(() => alert.remove(), 2500);
}

function formatNumber(value) {
  return new Intl.NumberFormat('id-ID', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(Number(value || 0));
}

function numberValue(value) {
  return parseFloat(String(value || 0).replace(/,/g, '')) || 0;
}

function readFilters(jenisRawat) {
  const tgl1 = document.getElementById('tgl1');
  const tgl2 = document.getElementById('tgl2');

  if (tgl1.value && tgl2.value && tgl1.value > tgl2.value) {
    tgl2.value = tgl1.value;
  }

  return {
    tgl1: tgl1.value,
    tgl2: tgl2.value,
    jenis_rawat: jenisRawat
  };
}

function resetFilters() {
  document.getElementById('tgl1').value = TODAY;
  document.getElementById('tgl2').value = TODAY;
}

function updateSummary(key, data) {
  document.getElementById(`${key}_jml_resep`).textContent = formatNumber(data.jumlah_resep);
  document.getElementById(`${key}_jml_obat`).textContent = formatNumber(data.jumlah_obat_keluar);
  document.getElementById(`${key}_total_biaya`).textContent = formatNumber(data.total_pengeluaran);
}

function loadSummary(jenisRawat) {
  loadedSummaries.add(jenisRawat);

  $.ajax({
    url: API.summary,
    type: 'POST',
    dataType: 'json',
    data: readFilters(jenisRawat),
    success: function (response) {
      updateSummary(`${jenisRawat}_nonracik`, response.non_racik);
      updateSummary(`${jenisRawat}_racik`, response.racik);
    },
    error: function (xhr) {
      console.error(xhr.responseText);
      showAlert(`Gagal mengambil summary resep ${jenisRawat === 'ralan' ? 'rawat jalan' : 'rawat inap'}.`, 'error');
    }
  });
}

function initTable(key) {
  if (tables[key]) {
    tables[key].columns.adjust();
    return;
  }

  const report = REPORTS[key];
  tables[key] = $(`#tbl_${key}`).DataTable({
    processing: true,
    serverSide: false,
    responsive: false,
    scrollX: true,
    pageLength: 10,
    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
    dom: 'lBfrtip',
    buttons: [
      { extend: 'copyHtml5', text: 'Copy' },
      { extend: 'excelHtml5', text: 'Excel', filename: `rekap_resep_${key}_${TODAY}` }
    ],
    ajax: {
      url: report.resep === 'racik' ? API.racik : API.nonracik,
      type: 'POST',
      data: function () {
        return readFilters(report.rawat);
      },
      dataSrc: 'data',
      error: function (xhr) {
        console.error(xhr.responseText);
        const response = xhr.responseJSON || {};
        showAlert(response.error || 'Gagal mengambil detail rekap resep.', 'error');
      }
    },
    columns: [
      { data: 'kode_brng', className: 'font-monospace' },
      { data: 'nama_brng' },
      { data: 'jumlah', className: 'text-right' },
      { data: 'total', className: 'text-right font-weight-bold' }
    ],
    order: [[2, 'desc'], [1, 'asc']],
    footerCallback: function () {
      const api = this.api();
      const sumColumn = index => api
        .column(index, { search: 'applied' })
        .data()
        .reduce((total, value) => total + numberValue(value), 0);

      $(api.column(2).footer()).html(formatNumber(sumColumn(2)));
      $(api.column(3).footer()).html(formatNumber(sumColumn(3)));
    },
    language: {
      info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
      lengthMenu: 'Tampilkan _MENU_ data',
      search: 'Cari:',
      zeroRecords: 'Data tidak ditemukan',
      emptyTable: 'Belum ada data'
    }
  });
}

function reloadAll() {
  loadedSummaries.forEach(loadSummary);
  Object.values(tables).forEach(table => table.ajax.reload(null, true));
}

document.addEventListener('DOMContentLoaded', function () {
  loadSummary('ralan');
  initTable('ralan_nonracik');

  document.getElementById('btnFilter').addEventListener('click', reloadAll);
  document.getElementById('btnReset').addEventListener('click', function () {
    resetFilters();
    reloadAll();
  });
  ['tgl1', 'tgl2'].forEach(function (id) {
    document.getElementById(id).addEventListener('change', reloadAll);
  });

  $('.rawat-tab').on('shown.bs.tab', function (event) {
    const jenisRawat = event.target.dataset.rawat;
    if (!loadedSummaries.has(jenisRawat)) {
      loadSummary(jenisRawat);
    }
    initTable(`${jenisRawat}_nonracik`);
  });

  $('.resep-kind-tab').on('shown.bs.tab', function (event) {
    initTable(event.target.dataset.key);
  });
});
</script>
<?php unset($renderPanel); ?>
