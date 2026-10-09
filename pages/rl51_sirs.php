<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
?>
<style>
.filter-card label { font-weight:600; font-size:.85rem; margin-bottom:.35rem; }
.filter-card .form-group { margin-bottom:.75rem; }
#rl51Table thead th {
  text-align: center;
  vertical-align: middle !important;
  white-space: nowrap;
}

#rl51Table thead tr:nth-child(1) th,
#rl51Table thead tr:nth-child(2) th,
#rl51Table thead tr:nth-child(3) th {
  background: #fff;
}

#rl51Table_wrapper .dataTables_scrollHead th {
  text-align: center;
  vertical-align: middle !important;
}
</style>

<div class="container-fluid px-2 px-md-3 py-3">
  <div class="mb-3">
    <h3 class="mb-1 font-weight-bold">RL 5.1 SIRS</h3>
    <div class="text-muted">Jumlah kasus baru menurut kelompok umur, jenis kelamin, dan jumlah kunjungan</div>
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
      <div class="card-section-title">Data RL 5.1 SIRS</div>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table id="rl51Table" class="table table-striped table-bordered nowrap" style="width:100%">
          <thead>
              <tr>
                <th rowspan="3">No</th>
                <th rowspan="3">Kode ICD 10</th>
                <th rowspan="3">Diagnosis Penyakit</th>
            
                <th colspan="50">Jumlah Kasus Baru Menurut Kelompok Umur &amp; Jenis Kelamin</th>
                <th colspan="3">Jumlah Kasus Baru Menurut Jenis Kelamin</th>
                <th colspan="3">Jumlah Kunjungan</th>
              </tr>
              <tr>
                <th colspan="2">&lt; 1 Jam</th>
                <th colspan="2">1 - 23 Jam</th>
                <th colspan="2">1 - 7 Hr</th>
                <th colspan="2">8 - 28 Hr</th>
                <th colspan="2">29 Hr - &lt; 3 Bln</th>
                <th colspan="2">3 - &lt; 6 Bln</th>
                <th colspan="2">6 - 11 Bln</th>
                <th colspan="2">1 - 4 Th</th>
                <th colspan="2">5 - 9 Th</th>
                <th colspan="2">10 - 14 Th</th>
                <th colspan="2">15 - 19 Th</th>
                <th colspan="2">20 - 24 Th</th>
                <th colspan="2">25 - 29 Th</th>
                <th colspan="2">30 - 34 Th</th>
                <th colspan="2">35 - 39 Th</th>
                <th colspan="2">40 - 44 Th</th>
                <th colspan="2">45 - 49 Th</th>
                <th colspan="2">50 - 54 Th</th>
                <th colspan="2">55 - 59 Th</th>
                <th colspan="2">60 - 64 Th</th>
                <th colspan="2">65 - 69 Th</th>
                <th colspan="2">70 - 74 Th</th>
                <th colspan="2">75 - 79 Th</th>
                <th colspan="2">80 - 84 Th</th>
                <th colspan="2">&gt; 85 Th</th>
            
                <th rowspan="2">L</th>
                <th rowspan="2">P</th>
                <th rowspan="2">Total</th>
            
                <th rowspan="2">L</th>
                <th rowspan="2">P</th>
                <th rowspan="2">Total</th>
              </tr>
              <tr>
                <th>L</th><th>P</th>
                <th>L</th><th>P</th>
                <th>L</th><th>P</th>
                <th>L</th><th>P</th>
                <th>L</th><th>P</th>
                <th>L</th><th>P</th>
                <th>L</th><th>P</th>
                <th>L</th><th>P</th>
                <th>L</th><th>P</th>
                <th>L</th><th>P</th>
                <th>L</th><th>P</th>
                <th>L</th><th>P</th>
                <th>L</th><th>P</th>
                <th>L</th><th>P</th>
                <th>L</th><th>P</th>
                <th>L</th><th>P</th>
                <th>L</th><th>P</th>
                <th>L</th><th>P</th>
                <th>L</th><th>P</th>
                <th>L</th><th>P</th>
                <th>L</th><th>P</th>
                <th>L</th><th>P</th>
                <th>L</th><th>P</th>
                <th>L</th><th>P</th>
                <th>L</th><th>P</th>
              </tr>
            </thead>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
const API = {
  dt: 'api/rl51-sirs-datatable.php',
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

function getColumns(){
  return [
    {data:'no'},
    {data:'kd_penyakit'},
    {data:'nm_penyakit'},

    {data:'u_lt1jam_l'},{data:'u_lt1jam_p'},
    {data:'u_1_23jam_l'},{data:'u_1_23jam_p'},
    {data:'u_1_7hr_l'},{data:'u_1_7hr_p'},
    {data:'u_8_28hr_l'},{data:'u_8_28hr_p'},
    {data:'u_29hr_3bln_l'},{data:'u_29hr_3bln_p'},
    {data:'u_3_6bln_l'},{data:'u_3_6bln_p'},
    {data:'u_6_11bln_l'},{data:'u_6_11bln_p'},
    {data:'u_1_4th_l'},{data:'u_1_4th_p'},
    {data:'u_5_9th_l'},{data:'u_5_9th_p'},
    {data:'u_10_14th_l'},{data:'u_10_14th_p'},
    {data:'u_15_19th_l'},{data:'u_15_19th_p'},
    {data:'u_20_24th_l'},{data:'u_20_24th_p'},
    {data:'u_25_29th_l'},{data:'u_25_29th_p'},
    {data:'u_30_34th_l'},{data:'u_30_34th_p'},
    {data:'u_35_39th_l'},{data:'u_35_39th_p'},
    {data:'u_40_44th_l'},{data:'u_40_44th_p'},
    {data:'u_45_49th_l'},{data:'u_45_49th_p'},
    {data:'u_50_54th_l'},{data:'u_50_54th_p'},
    {data:'u_55_59th_l'},{data:'u_55_59th_p'},
    {data:'u_60_64th_l'},{data:'u_60_64th_p'},
    {data:'u_65_69th_l'},{data:'u_65_69th_p'},
    {data:'u_70_74th_l'},{data:'u_70_74th_p'},
    {data:'u_75_79th_l'},{data:'u_75_79th_p'},
    {data:'u_80_84th_l'},{data:'u_80_84th_p'},
    {data:'u_gt85th_l'},{data:'u_gt85th_p'},

    {data:'kasus_baru_l'},
    {data:'kasus_baru_p'},
    {data:'kasus_baru_total'},

    {data:'kunjungan_l'},
    {data:'kunjungan_p'},
    {data:'kunjungan_total'}
  ].map((c, i) => i >= 3 ? {...c, className:'text-right'} : c);
}

function initTable(){
  dt = $('#rl51Table').DataTable({
    processing:true,
    serverSide:false,
    responsive:false,
    autoWidth:false,
    scrollX:true,
    pageLength:25,
    lengthMenu:[10,25,50,100],
    deferRender:true,
    dom:'lBfrtip',
    buttons:[
      { extend:'copyHtml5', text:'Copy', exportOptions:{columns:':visible'} },
      { extend:'excelHtml5', text:'Excel', filename:'rl51_sirs_'+new Date().toISOString().slice(0,10), exportOptions:{columns:':visible'} },
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
        showAlert('Gagal load data RL 5.1 SIRS', 'error');
      }
    },
    columns:getColumns(),
    order:[[1,'asc']]
  });

  dt.on('init.dt', function(){
      setTimeout(function(){
        dt.columns.adjust();
      }, 100);
    });
    
    dt.on('draw.dt', function(){
      setTimeout(function(){
        dt.columns.adjust();
      }, 50);
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