<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
?>
<style>
.filter-card label { font-weight:600; font-size:.85rem; margin-bottom:.35rem; }
.filter-card .form-group { margin-bottom:.75rem; }
</style>

  <div class="container-fluid px-2 px-md-3 py-3">
    <div class="mb-3">
      <h3 class="mb-1 font-weight-bold">Laporan Kunjungan Rawat jalan</h3>
      <div class="text-muted">Monitoring kunjungan pasien rawat jalan dengan status & dokumentasi</div>
    </div>

    <div id="alertContainer"></div>
    
    <!-- CARD FILTER -->
    <div class="card filter-card mb-3">
      <div class="card-header py-2">
        <strong>Filter Data</strong>
      </div>
    
      <div class="card-body">
        <div class="form-row align-items-end">
    
          <div class="form-group col-md-2">
            <label for="filterStartDate">Tanggal Mulai</label>
            <input type="date" class="form-control form-control-sm"
                   id="filterStartDate"
                   value="<?= date('Y-m-d') ?>">
          </div>
    
          <div class="form-group col-md-2">
            <label for="filterEndDate">Tanggal Akhir</label>
            <input type="date" class="form-control form-control-sm"
                   id="filterEndDate"
                   value="<?= date('Y-m-d') ?>">
          </div>
    
          <div class="form-group col-md-2">
            <label for="filterPoli">Poliklinik</label>
            <select id="filterPoli"
                    class="form-control form-control-sm"
                    multiple>
            </select>
          </div>
    
          <div class="form-group col-md-2">
            <label for="filterStatus">Status (stts)</label>
            <select id="filterStatus"
                    class="form-control form-control-sm"
                    multiple>
            </select>
          </div>
    
          <div class="form-group col-md-2">
            <label for="filterCaraBayar">Cara Bayar</label>
            <select id="filterCaraBayar"
                    class="form-control form-control-sm"
                    multiple>
            </select>
          </div>

        </div>
      </div>
      <div class="card-footer bg-white py-2">
          <div class="d-flex justify-content" style="gap:8px">
            <button type="button" class="btn btn-sm btn-primary" id="btnFilter">Terapkan</button>
            <button type="button" class="btn btn-sm btn-secondary" id="btnReset">Reset</button>
          </div>
        </div>
    </div>

    <!-- CARD TABLE -->
      <div class="card mb-3">
        <div class="card-header py-2">
          <strong>Pasien Rawat Jalan</strong>
        </div>
        <div class="card-body">
          <table id="patientVisitTable" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
            <thead>
            <tr>
              <th>No. RM</th>
              <th>Nama Pasien</th>
              <th>JK</th>
              <th>Umur Daftar</th>
              <th>Tgl Registrasi</th>
              <th>Poliklinik</th>
              <th>Dokter</th>
              <th>Cara Bayar</th>
              <th>Status Poli</th>
              <th>Status</th>
              <th>Billing</th>
              <th>SOAP Perawat</th>
              <th>Resep Dokter</th>
              <th>SOAP Dokter</th>
              <th>Validasi Resep</th>
              <th>Diagnosa</th>
            </tr>
            </thead>
          </table>
        </div>
      </div>
    
    <div class="card p-3 mt-3">
      <div class="font-weight-bold mb-2">Persentase EMR (SOAP Dokter)</div>
      <div class="table-responsive">
        <table class="table table-bordered table-sm" id="emrSummaryTable">
          <thead>
            <tr>
              <th>No</th>
              <th>Nama Dokter</th>
              <th>Poliklinik</th>
              <th>Total Pasien</th>
              <th>SOAP Diisi</th>
              <th>Persentase</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>

  </div>

<script>
const API = {
  poli: 'api/rajal-poli-list.php',
  stts: 'api/rajal-stts-list.php',
  bayar: 'api/rajal-carabayar-list.php',
  dt: 'api/rajal-datatable.php',
  summary: 'api/rajal-emr-soapdokter-summary.php',
};

let dt = null;
const state = { startDate:null, endDate:null, poli:[], status:[], caraBayar:[] };

function showAlert(msg, type='info'){
  const el = document.getElementById('alertContainer');
  const id = 'a' + Date.now();
  el.insertAdjacentHTML('beforeend', `<div id="${id}" class="alert alert-${type==='error'?'danger':'info'} py-2 mb-2">${msg}</div>`);
  setTimeout(()=>document.getElementById(id)?.remove(), 2500);
}

function debounce(fn, ms=250){
  let t=null;
  return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms); };
}

async function loadOptions(url, selectId){
  const r = await fetch(url, {headers:{'Accept':'application/json'}});
  const data = await r.json();
  const sel = document.getElementById(selectId);
  sel.innerHTML = '';
  (data||[]).forEach(it=>{
    const opt = document.createElement('option');
    opt.value = it.value;
    opt.textContent = it.label;
    sel.appendChild(opt);
  });
}

function getMultiValues(id){
  return Array.from(document.getElementById(id).selectedOptions).map(o=>o.value).filter(Boolean);
}

function readFilters(){
  const sEl = document.getElementById('filterStartDate');
  const eEl = document.getElementById('filterEndDate');
  if (sEl.value && eEl.value && sEl.value > eEl.value) eEl.value = sEl.value;

  state.startDate = sEl.value;
  state.endDate = eEl.value;
  state.poli = getMultiValues('filterPoli');
  state.status = getMultiValues('filterStatus');
  state.caraBayar = getMultiValues('filterCaraBayar');
}

function buildFormData(){
  readFilters();
  const fd = new FormData();
  fd.append('startDate', state.startDate);
  fd.append('endDate', state.endDate);
  state.poli.forEach(v => fd.append('poli[]', v));
  state.status.forEach(v => fd.append('status[]', v));
  state.caraBayar.forEach(v => fd.append('caraBayar[]', v));
  return fd;
}

async function reloadSummary(){
  try{
    const r = await fetch(API.summary, {
      method:'POST',
      body: buildFormData(),
      headers:{'Accept':'application/json'}
    });
    const j = await r.json();

    const tbody = document.querySelector('#emrSummaryTable tbody');
    tbody.innerHTML = '';
    (j.data || []).forEach((row, i) => {
      tbody.insertAdjacentHTML('beforeend', `
        <tr>
          <td>${i+1}</td>
          <td>${row.nm_dokter ?? '-'}</td>
          <td>${row.nm_poli ?? '-'}</td>
          <td class="text-right">${row.total_pasien ?? 0}</td>
          <td class="text-right">${row.soap_diisi ?? 0}</td>
          <td class="text-right">${row.persentase ?? 0} %</td>
        </tr>
      `);
    });
  }catch(e){
    console.error(e);
    showAlert('Gagal load summary SOAP dokter', 'error');
  }
}

function initDataTable(){
  dt = $('#patientVisitTable').DataTable({
    processing:true,
    serverSide:false,
    responsive:false,
    autoWidth:false,
    scrollX:true,
    pageLength:10,
    lengthMenu:[10,25,50,100],
    deferRender:true,
    dom:'lBfrtip',
    buttons:[
      { extend:'copyHtml5', text:'Copy', exportOptions:{columns:':visible'} },
      { extend:'excelHtml5', text:'Excel', filename:'rm_rajal_'+new Date().toISOString().slice(0,10), exportOptions:{columns:':visible'} },
    ],
    ajax:{
      url: API.dt,
      type:'POST',
      data: function(d){
        readFilters();
        d.startDate = state.startDate;
        d.endDate = state.endDate;
        d.poli = state.poli;
        d.status = state.status;
        d.caraBayar = state.caraBayar;
      },
      dataSrc:'data',
      error:function(xhr){
        console.error(xhr.responseText);
        showAlert('Gagal load data table', 'error');
      }
    },
    columns:[
      {data:'no_rkm_medis'},
      {data:'nm_pasien'},
      {data:'jk'},
      {data:'umur_status'},
      {data:'tgl_registrasi'},
      {data:'nm_poli'},
      {data:'nm_dokter'},
      {data:'cara_bayar'},
      {data:'status_poli'},
      {data:'status_pasien'},
      {data:'status_bayar'},
      {data:'soap_perawat'},
      {data:'resep_dokter'},
      {data:'soap_dokter'},
      {data:'validasi_resep'},
      {data:'diagnosa_pasien'},
    ]
  });

  dt.on('xhr.dt', function(){
    setTimeout(()=>dt.columns.adjust(), 0);
    reloadSummary();
  });

  dt.on('draw.dt', function(){
    dt.columns.adjust();
  });
}

function reloadTable(){
  if (!dt) return;
  dt.ajax.reload(function(){
    dt.columns.adjust();
  }, true);
}

function reloadAll(){
  reloadTable();
  reloadSummary();
}

const autoReload = debounce(reloadAll, 250);

function resetFilters(){
  const today = new Date().toISOString().slice(0,10);
  document.getElementById('filterStartDate').value = today;
  document.getElementById('filterEndDate').value = today;

  ['filterPoli','filterStatus','filterCaraBayar'].forEach(id=>{
    const el = document.getElementById(id);
    Array.from(el.options).forEach(o=>o.selected=false);
  });

  readFilters();
}

async function init(){
  try{
    await Promise.all([
      loadOptions(API.poli,'filterPoli'),
      loadOptions(API.stts,'filterStatus'),
      loadOptions(API.bayar,'filterCaraBayar'),
    ]);

    initDataTable();
    reloadSummary();

    document.getElementById('btnFilter').addEventListener('click', reloadAll);
    document.getElementById('btnReset').addEventListener('click', ()=>{
      resetFilters();
      reloadAll();
    });

    ['filterStartDate','filterEndDate','filterPoli','filterStatus','filterCaraBayar']
      .forEach(id => document.getElementById(id).addEventListener('change', autoReload));

  }catch(e){
    console.error(e);
    showAlert('Gagal init halaman rm_rajal', 'error');
  }
}

document.addEventListener('DOMContentLoaded', init);
</script>