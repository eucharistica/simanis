<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
?>
<style>
.lplpo-page .nav-tabs .nav-link{
  font-weight:600;
}
.lplpo-page .filter-card label {
  font-weight:600;
  font-size:.85rem;
  margin-bottom:.35rem;
}
.lplpo-page .filter-card .form-group {
  margin-bottom:.75rem;
}
.lplpo-page .dataTables_wrapper .dt-buttons{
  margin-bottom:.5rem;
}
.lplpo-page table.dataTable thead th{
  vertical-align:middle;
  text-align:center;
  white-space:nowrap;
}
.lplpo-page .text-right{
  text-align:right !important;
}
.lplpo-page .dataTables_filter input,
.lplpo-page .dataTables_length select{
  height:32px;
}
</style>

<div class="container-fluid px-2 px-md-3 py-3 lplpo-page">
  <div class="mb-3">
    <h3 class="mb-1 font-weight-bold">LPLPO</h3>
    <div class="text-muted">Laporan Pemakaian dan Lembar Permintaan Obat</div>
  </div>

  <div id="alertContainer"></div>

  <div class="card card-soft filter-card mb-3">
    <div class="card-header card-soft-header py-2">
      <div class="card-section-title">Filter Data</div>
    </div>
    <div class="card-body">
      <div class="form-row align-items-end">
        <div class="form-group col-md-2">
          <label for="tgl1">Tanggal Mulai</label>
          <input type="date" class="form-control form-control-sm" id="tgl1" value="<?= date('Y-m-01') ?>">
        </div>
        <div class="form-group col-md-2">
          <label for="tgl2">Tanggal Akhir</label>
          <input type="date" class="form-control form-control-sm" id="tgl2" value="<?= date('Y-m-d') ?>">
        </div>
      </div>
    </div>
    <div class="card-footer bg-white py-2">
      <div class="d-flex" style="gap:8px">
        <button type="button" class="btn btn-sm btn-primary" id="btnFilter">Terapkan</button>
        <button type="button" class="btn btn-sm btn-secondary" id="btnReset">Reset</button>
      </div>
    </div>
  </div>

  <div class="card card-soft">
    <div class="card-body">
      <ul class="nav nav-tabs mb-3" id="lplpoTabs" role="tablist">
        <li class="nav-item">
          <a class="nav-link active" id="tab-obat-link" data-toggle="tab" href="#tab-obat" role="tab">LPLPO Obat</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" id="tab-nonobat-link" data-toggle="tab" href="#tab-nonobat" role="tab">LPLPO Non Obat</a>
        </li>
      </ul>

      <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-obat" role="tabpanel">
          <div class="table-responsive">
            <table id="dtLplpoObat" class="table table-striped table-bordered nowrap" style="width:100%">
              <thead>
                <tr>
                  <th>Kode Barang</th>
                  <th>Nama Barang</th>
                  <!--<th>Satuan</th>-->
                  <th>Stok</th>
                  <th>Rawat Jalan Qty</th>
                  <th>Rawat Jalan Rp</th>
                  <th>IGD Qty</th>
                  <th>IGD Rp</th>
                  <th>Rawat Inap Qty</th>
                  <th>Rawat Inap Rp</th>
                  <th>Total Qty</th>
                  <th>Total Rp</th>
                </tr>
              </thead>
            </table>
          </div>
        </div>

        <div class="tab-pane fade" id="tab-nonobat" role="tabpanel">
          <div class="table-responsive">
            <table id="dtLplpoNonObat" class="table table-striped table-bordered nowrap" style="width:100%">
              <thead>
                <tr>
                  <th>Kode Barang</th>
                  <th>Nama Barang</th>
                  <!--<th>Satuan</th>-->
                  <th>Stok</th>
                  <th>Rawat Jalan Qty</th>
                  <th>Rawat Jalan Rp</th>
                  <th>IGD Qty</th>
                  <th>IGD Rp</th>
                  <th>Rawat Inap Qty</th>
                  <th>Rawat Inap Rp</th>
                  <th>Total Qty</th>
                  <th>Total Rp</th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const API = {
  dt: 'api/lplpo-datatable.php'
};

let dtObat = null;
let dtNonObat = null;

function showAlert(msg, type='info'){
  const el = document.getElementById('alertContainer');
  const id = 'a' + Date.now();
  el.insertAdjacentHTML('beforeend',
    `<div id="${id}" class="alert alert-${type==='error'?'danger':'info'} py-2 mb-2">${msg}</div>`);
  setTimeout(()=>document.getElementById(id)?.remove(), 2500);
}

function readFilters(){
  const t1 = document.getElementById('tgl1');
  const t2 = document.getElementById('tgl2');
  if (t1.value && t2.value && t1.value > t2.value) t2.value = t1.value;
  return { tgl1: t1.value, tgl2: t2.value };
}

function resetFilters(){
  const now = new Date();
  const first = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0,10);
  const today = now.toISOString().slice(0,10);
  document.getElementById('tgl1').value = first;
  document.getElementById('tgl2').value = today;
}

function initTable(selector, jenis, filename){
  return $(selector).DataTable({
    processing: true,
    serverSide: false,
    responsive: false,
    scrollX: true,
    pageLength: 10,
    lengthMenu: [[10, 30, 50, 100], [10, 30, 50, 100]],
    dom: 'lBfrtip',
    buttons: [
      { extend:'copyHtml5', text:'Copy' },
      { extend:'excelHtml5', text:'Excel', filename: filename }
    ],
    ajax: {
      url: API.dt,
      type: 'POST',
      data: function(d){
        const f = readFilters();
        d.tgl1 = f.tgl1;
        d.tgl2 = f.tgl2;
        d.jenis = jenis;
      },
      dataSrc: 'data',
      error: function(xhr){
        console.error(xhr.responseText);
        showAlert('Gagal memuat data LPLPO', 'error');
      }
    },
    columns: [
      { data:'kode_brng', className:'font-monospace' },
      { data:'nama_brng' },
    //   { data:'satuan', className:'text-center' },
      { data:'stok', className:'text-right' },
      { data:'rajal_qty', className:'text-right' },
      { data:'rajal_rp', className:'text-right' },
      { data:'igd_qty', className:'text-right' },
      { data:'igd_rp', className:'text-right' },
      { data:'ranap_qty', className:'text-right' },
      { data:'ranap_rp', className:'text-right' },
      { data:'total_qty', className:'text-right font-weight-bold' },
      { data:'total_rp', className:'text-right font-weight-bold' }
    ],
    order: [[1, 'asc']],
    language: {
      info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
      lengthMenu: "Tampilkan _MENU_ data",
      search: "Cari:",
      zeroRecords: "Data tidak ditemukan",
      emptyTable: "Belum ada data"
    }
  });
}

function reloadAll(){
  dtObat?.ajax.reload(null, true);
  dtNonObat?.ajax.reload(null, true);
}

document.addEventListener('DOMContentLoaded', function(){
  dtObat = initTable('#dtLplpoObat', 'obat', 'lplpo_obat_' + new Date().toISOString().slice(0,10));
  dtNonObat = initTable('#dtLplpoNonObat', 'nonobat', 'lplpo_nonobat_' + new Date().toISOString().slice(0,10));

  document.getElementById('btnFilter').addEventListener('click', reloadAll);
  document.getElementById('btnReset').addEventListener('click', function(){
    resetFilters();
    reloadAll();
  });

  ['tgl1','tgl2'].forEach(id => {
    document.getElementById(id).addEventListener('change', reloadAll);
  });

  $('a[data-toggle="tab"]').on('shown.bs.tab', function () {
    dtObat?.columns.adjust();
    dtNonObat?.columns.adjust();
  });
});
</script>