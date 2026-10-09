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
    <h3 class="mb-1 font-weight-bold">Laporan ISPA</h3>
    <div class="text-muted">Rekap ISPA dan Pneumonia berdasarkan jenis layanan, umur, jenis kelamin, dan antibiotik</div>
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
      <div class="card-section-title">Rekap Laporan ISPA</div>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table id="laporanISPATable" class="table table-striped table-bordered nowrap" style="width:100%">
          <thead>
			  <tr>
			    <th rowspan="2">Jenis Layanan</th>
			    <th rowspan="2">Kategori</th>
			
			    <th colspan="4" class="text-center">&lt; 1 Th</th>
			    <th colspan="4" class="text-center">1 Th - &lt; 5 Th</th>
			    <th colspan="4" class="text-center">5-9 Th</th>
			    <th colspan="4" class="text-center">10-18 Th</th>
			    <th colspan="4" class="text-center">19-59 Th</th>
			    <th colspan="4" class="text-center">&gt;=60 Th</th>
			
			    <th rowspan="2">Total</th>
			  </tr>
			  <tr>
			    <th>L</th>
			    <th>P</th>
			    <th>Sub Total</th>
			    <th>Diberi Obat Antibiotik</th>
			
			    <th>L</th>
			    <th>P</th>
			    <th>Sub Total</th>
			    <th>Diberi Obat Antibiotik</th>
			
			    <th>L</th>
			    <th>P</th>
			    <th>Sub Total</th>
			    <th>Diberi Obat Antibiotik</th>
			
			    <th>L</th>
			    <th>P</th>
			    <th>Sub Total</th>
			    <th>Diberi Obat Antibiotik</th>
			
			    <th>L</th>
			    <th>P</th>
			    <th>Sub Total</th>
			    <th>Diberi Obat Antibiotik</th>
			
			    <th>L</th>
			    <th>P</th>
			    <th>Sub Total</th>
			    <th>Diberi Obat Antibiotik</th>
			  </tr>
			</thead>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
const API = {
  dt: 'api/laporan-ispa-datatable.php',
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
  const today = new Date().toISOString().slice(0,10);
  document.getElementById('filterStartDate').value = today;
  document.getElementById('filterEndDate').value = today;
}

function initDataTable(){
  dt = $('#laporanISPATable').DataTable({
    processing:true,
    serverSide:true,
    responsive:false,
    autoWidth:false,
    scrollX:true,
    pageLength:10,
    lengthMenu:[10,25,50,100],
    dom:'lBfrtip',
    buttons:[
      { extend:'copyHtml5', text:'Copy' },
      { extend:'excelHtml5', text:'Excel', filename:'laporan_ispa_' + new Date().toISOString().slice(0,10) }
    ],
    ajax:{
      url: API.dt,
      type:'POST',
      data:function(d){
        const f = readFilters();
        d.startDate = f.startDate;
        d.endDate = f.endDate;
        return d;
      },
      error:function(xhr){
        console.error(xhr.responseText);
        showAlert('Gagal load data laporan ISPA', 'error');
      }
    },
    columns:[
	  {data:'jenis_layanan'},
	  {data:'kategori_ispa'},
	
	  {data:'u1_l', className:'text-right'},
	  {data:'u1_p', className:'text-right'},
	  {data:'u1_sub', className:'text-right font-weight-bold'},
	  {data:'u1_antibiotik', className:'text-right'},
	
	  {data:'u14_l', className:'text-right'},
	  {data:'u14_p', className:'text-right'},
	  {data:'u14_sub', className:'text-right font-weight-bold'},
	  {data:'u14_antibiotik', className:'text-right'},
	
	  {data:'u5_l', className:'text-right'},
	  {data:'u5_p', className:'text-right'},
	  {data:'u5_sub', className:'text-right font-weight-bold'},
	  {data:'u5_antibiotik', className:'text-right'},
	
	  {data:'u10_l', className:'text-right'},
	  {data:'u10_p', className:'text-right'},
	  {data:'u10_sub', className:'text-right font-weight-bold'},
	  {data:'u10_antibiotik', className:'text-right'},
	
	  {data:'u19_l', className:'text-right'},
	  {data:'u19_p', className:'text-right'},
	  {data:'u19_sub', className:'text-right font-weight-bold'},
	  {data:'u19_antibiotik', className:'text-right'},
	
	  {data:'u60_l', className:'text-right'},
	  {data:'u60_p', className:'text-right'},
	  {data:'u60_sub', className:'text-right font-weight-bold'},
	  {data:'u60_antibiotik', className:'text-right'},
	
	  {data:'grand_total', className:'text-right font-weight-bold'},
	],
    ordering:false,
    searching:false
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
  initDataTable();

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