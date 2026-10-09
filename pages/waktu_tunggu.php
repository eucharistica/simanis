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
    <h3 class="mb-1 font-weight-bold">Waktu Tunggu Layanan</h3>
    <div class="text-muted">Monitoring waktu tunggu pasien IGD dan Poliklinik</div>
  </div>

  <div id="alertContainer"></div>

  <div class="card card-soft filter-card mb-3">
    <div class="card-header card-soft-header py-2">
      <div class="card-section-title">Filter Data</div>
    </div>

    <div class="card-body">
      <div class="form-row align-items-end">
        <div class="form-group col-md-2">
          <label for="filterStartDate">Tanggal Mulai</label>
          <input type="date" class="form-control form-control-sm" id="filterStartDate" value="<?= date('Y-m-d') ?>">
        </div>

        <div class="form-group col-md-2">
          <label for="filterEndDate">Tanggal Akhir</label>
          <input type="date" class="form-control form-control-sm" id="filterEndDate" value="<?= date('Y-m-d') ?>">
        </div>

        <div class="form-group col-md-3">
          <label for="filterPoli">Poliklinik</label>
          <select id="filterPoli" class="form-control form-control-sm" multiple></select>
          <small class="text-muted">Dipakai untuk tab Poli.</small>
        </div>

        <div class="form-group col-md-2">
          <label for="filterStatus">Status</label>
          <select id="filterStatus" class="form-control form-control-sm" multiple></select>
        </div>

        <div class="form-group col-md-3">
          <label for="filterCaraBayar">Cara Bayar</label>
          <select id="filterCaraBayar" class="form-control form-control-sm" multiple></select>
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

  <div class="card card-soft mb-3">
    <div class="card-header card-soft-header-info py-2">
      <ul class="nav nav-tabs card-header-tabs border-0" id="wtTab" role="tablist">
        <li class="nav-item">
          <a class="nav-link active text-dark" id="tab-igd-link" data-toggle="tab" href="#tab-igd" role="tab">
            <i class="fas fa-ambulance mr-1"></i> Waktu Tunggu IGD
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-dark" id="tab-poli-link" data-toggle="tab" href="#tab-poli" role="tab">
            <i class="fas fa-stethoscope mr-1"></i> Waktu Tunggu Poli
          </a>
        </li>
      </ul>
    </div>

    <div class="card-body">
      <div class="tab-content">
        <!-- TAB IGD -->
        <div class="tab-pane fade show active" id="tab-igd" role="tabpanel">
          <div class="table-responsive">
            <table id="dtWaktuTungguIGD" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
              <thead>
                <tr>
                  <th>Tgl Registrasi</th>
                  <th>Jam Reg</th>
                  <th>No. RM</th>
                  <th>Nama Pasien</th>
                  <th>JK</th>
                  <th>Umur</th>
                  <th>Dokter</th>
                  <th>Cara Bayar</th>
                  <th>Status</th>
                  <th>Awal Kep</th>
                  <th>Triase</th>
                  <th>Asesmen Medis</th>
                  <th>Reg ke Perawatan (menit)</th>
                  <th>Diagnosa</th>
                </tr>
              </thead>
            </table>
          </div>
        </div>

        <!-- TAB POLI -->
        <div class="tab-pane fade" id="tab-poli" role="tabpanel">
          <div class="table-responsive">
            <table id="dtWaktuTungguPoli" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
              <thead>
                <tr>
                  <th>Tgl Registrasi</th>
                  <th>No. RM</th>
                  <th>Nama Pasien</th>
                  <th>JK</th>
                  <th>Umur</th>
                  <th>Poliklinik</th>
                  <th>Dokter</th>
                  <th>Cara Bayar</th>
                  <th>Status Poli</th>
                  <th>Status</th>
                  <th>SOAP Perawat</th>
                  <th>SOAP Dokter</th>
                  <th>Interval (mnt)</th>
                  <th>Diagnosa</th>
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
  poliList : 'api/rajal-poli-list.php',
  sttsList : 'api/rajal-stts-list.php',
  bayarList: 'api/rajal-carabayar-list.php',
  igdDt    : 'api/waktu-tunggu-igd-datatable.php',
  poliDt   : 'api/waktu-tunggu-poli-datatable.php',
};

let dtIgd = null;
let dtPoli = null;

const state = {
  startDate: null,
  endDate: null,
  poli: [],
  status: [],
  caraBayar: []
};

function showAlert(msg, type='info'){
  const el = document.getElementById('alertContainer');
  const id = 'a' + Date.now();
  el.insertAdjacentHTML('beforeend',
    `<div id="${id}" class="alert alert-${type==='error'?'danger':'info'} py-2 mb-2">${msg}</div>`);
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

function initTables(){
  dtIgd = $('#dtWaktuTungguIGD').DataTable({
    processing:true,
    serverSide:false,
    responsive:false,
    autoWidth:false,
    scrollX:true,
    pageLength:10,
    lengthMenu:[10,25,50,100],
    dom:'lBfrtip',
    buttons:[
      { extend:'copyHtml5', text:'Copy' },
      { extend:'excelHtml5', text:'Excel', filename:'waktu_tunggu_igd_' + new Date().toISOString().slice(0,10) },
    ],
    ajax:{
      url: API.igdDt,
      type:'POST',
      data:function(d){
        readFilters();
        d.startDate = state.startDate;
        d.endDate = state.endDate;
        d.status = state.status;
        d.caraBayar = state.caraBayar;
        return d;
      },
      error:function(xhr){
        console.error(xhr.responseText);
        showAlert('Gagal load data waktu tunggu IGD', 'error');
      }
    },
    columns:[
      {data:'tgl_registrasi'},
      {data:'jam_reg'},
      {data:'no_rkm_medis'},
      {data:'nm_pasien'},
      {data:'jk'},
      {data:'umur_status'},
      {data:'nm_dokter'},
      {data:'cara_bayar'},
      {data:'status_pasien'},
      {data:'jam_awal_keperawatan'},
      {data:'jam_triase'},
      {data:'jam_asesmen_medis'},
      {data:'interval_reg_perawatan'},
      {data:'diagnosa_pasien'},
    ]

  });

  dtPoli = $('#dtWaktuTungguPoli').DataTable({
    processing:true,
    serverSide:false,
    responsive:false,
    autoWidth:false,
    scrollX:true,
    pageLength:10,
    lengthMenu:[10,25,50,100],
    dom:'lBfrtip',
    buttons:[
      { extend:'copyHtml5', text:'Copy' },
      { extend:'excelHtml5', text:'Excel', filename:'waktu_tunggu_poli_' + new Date().toISOString().slice(0,10) },
    ],
    ajax:{
      url: API.poliDt,
      type:'POST',
      data:function(d){
        readFilters();
        d.startDate = state.startDate;
        d.endDate = state.endDate;
        d.poli = state.poli;
        d.status = state.status;
        d.caraBayar = state.caraBayar;
        return d;
      },
      error:function(xhr){
        console.error(xhr.responseText);
        showAlert('Gagal load data waktu tunggu poli', 'error');
      }
    },
    columns:[
      {data:'tgl_registrasi'},
      {data:'no_rkm_medis'},
      {data:'nm_pasien'},
      {data:'jk'},
      {data:'umur_status'},
      {data:'nm_poli'},
      {data:'nm_dokter'},
      {data:'cara_bayar'},
      {data:'status_poli'},
      {data:'status_pasien'},
      {data:'soap_perawat'},
      {data:'soap_dokter'},
      {data:'interval_menit'},
      {data:'diagnosa_pasien'},
    ]
  });

  dtIgd.on('draw.dt', function(){ dtIgd.columns.adjust(); });
  dtPoli.on('draw.dt', function(){ dtPoli.columns.adjust(); });

  $('a[data-toggle="tab"]').on('shown.bs.tab', function(){
    $.fn.dataTable.tables({visible:true, api:true}).columns.adjust().responsive.recalc();
  });
}

function reloadAll(){
  if (dtIgd) dtIgd.ajax.reload(null, true);
  if (dtPoli) dtPoli.ajax.reload(null, true);
}

const autoReload = debounce(reloadAll, 250);

async function init(){
  try{
    await Promise.all([
      loadOptions(API.poliList, 'filterPoli'),
      loadOptions(API.sttsList, 'filterStatus'),
      loadOptions(API.bayarList, 'filterCaraBayar'),
    ]);

    initTables();

    document.getElementById('btnFilter').addEventListener('click', reloadAll);
    document.getElementById('btnReset').addEventListener('click', ()=>{
      resetFilters();
      reloadAll();
    });

    ['filterStartDate','filterEndDate','filterPoli','filterStatus','filterCaraBayar']
      .forEach(id => document.getElementById(id).addEventListener('change', autoReload));

  }catch(e){
    console.error(e);
    showAlert('Gagal init halaman waktu tunggu', 'error');
  }
}

document.addEventListener('DOMContentLoaded', init);
</script>
