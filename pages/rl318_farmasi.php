<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
?>
<style>
.filter-card label { font-weight:600; font-size:.85rem; margin-bottom:.35rem; }
.filter-card .form-group { margin-bottom:.75rem; }
#rl318Table thead th { text-align:center; vertical-align:middle; white-space:nowrap; }
#rl318Table tbody td { vertical-align:middle; }
#rl318Table .num-box {
  min-width: 160px;
  background: #eef2f6;
  border: 1px solid #d9dee5;
  border-radius: .35rem;
  padding: .55rem .75rem;
  text-align: center;
  font-weight: 600;
}
</style>

<div class="container-fluid px-2 px-md-3 py-3">
  <div class="mb-3">
    <h3 class="mb-1 font-weight-bold">RL 3.18 Farmasi Resep</h3>
    <div class="text-muted">Rekap resep farmasi berdasarkan golongan obat dan jenis layanan</div>
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
          <input type="date" class="form-control form-control-sm" id="filterStartDate" value="<?= date('Y-m-01') ?>">
        </div>
        <div class="form-group col-md-2">
          <label for="filterEndDate">Tanggal Akhir</label>
          <input type="date" class="form-control form-control-sm" id="filterEndDate" value="<?= date('Y-m-d') ?>">
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
    <div class="card-header card-soft-header-primary py-2">
      <div class="card-section-title">Data RL 3.18 Farmasi Resep</div>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table id="rl318Table" class="table table-striped table-bordered nowrap" style="width:100%">
          <thead>
            <tr>
              <th style="width:60px">No</th>
              <th>Golongan Obat</th>
              <th>Rawat Jalan</th>
              <th>IGD</th>
              <th>Rawat Inap</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
const API = {
  dt: 'api/rl318-farmasi-datatable.php',
};

let dt = null;

function showAlert(msg, type='info'){
  const el = document.getElementById('alertContainer');
  const id = 'a' + Date.now();
  el.insertAdjacentHTML('beforeend', `<div id="${id}" class="alert alert-${type==='error'?'danger':'info'} py-2 mb-2">${msg}</div>`);
  setTimeout(()=>document.getElementById(id)?.remove(), 2500);
}

function readFilters(){
  const sEl = document.getElementById('filterStartDate');
  const eEl = document.getElementById('filterEndDate');
  if (sEl.value && eEl.value && sEl.value > eEl.value) eEl.value = sEl.value;
  return {
    startDate: sEl.value,
    endDate: eEl.value
  };
}

function resetFilters(){
  const now = new Date();
  const first = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0,10);
  const today = now.toISOString().slice(0,10);
  document.getElementById('filterStartDate').value = first;
  document.getElementById('filterEndDate').value = today;
}

function renderNum(v){
  return `<div class="num-box">${parseInt(v || 0, 10)}</div>`;
}

function initTable(){
  dt = $('#rl318Table').DataTable({
    processing:true,
    serverSide:false,
    responsive:false,
    autoWidth:false,
    paging:false,
    searching:false,
    info:false,
    ordering:false,
    dom:'Brt',
    buttons:[
      {
        extend:'copyHtml5',
        text:'Copy',
        exportOptions:{ columns:[0,1,2,3,4] }
      },
      {
        extend:'excelHtml5',
        text:'Excel',
        filename:'rl318_farmasi_' + new Date().toISOString().slice(0,10),
        exportOptions:{ columns:[0,1,2,3,4] }
      },
    ],
    ajax:{
      url: API.dt,
      type:'POST',
      data:function(d){
        const f = readFilters();
        d.startDate = f.startDate;
        d.endDate = f.endDate;
      },
      dataSrc:'data',
      error:function(xhr){
        console.error(xhr.responseText);
        showAlert('Gagal load data RL 3.18 Farmasi', 'error');
      }
    },
    columns:[
      {data:'no', className:'text-center'},
      {data:'golongan_obat'},
      {data:'rawat_jalan', render:function(d){ return renderNum(d); }},
      {data:'igd', render:function(d){ return renderNum(d); }},
      {data:'rawat_inap', render:function(d){ return renderNum(d); }},
    ]
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

document.addEventListener('DOMContentLoaded', function(){
  initTable();

  document.getElementById('btnFilter').addEventListener('click', reloadTable);
  document.getElementById('btnReset').addEventListener('click', function(){
    resetFilters();
    reloadTable();
  });

  ['filterStartDate','filterEndDate'].forEach(id => {
    document.getElementById(id).addEventListener('change', reloadTable);
  });
});
</script>