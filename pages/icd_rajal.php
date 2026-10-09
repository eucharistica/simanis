<?php declare(strict_types=1); 
require_once __DIR__ . '/../config.php'; 
?>


  <div class="container-fluid px-2 px-md-3 py-3">
    <div class="mb-3">
      <h3 class="mb-1 font-weight-bold">Top ICD Rajal</h3>
      <div class="text-muted">Diagnosa & Prosedur Rawat Jalan Terbanyak</div>
    </div>

    <div class="card p-3 mb-3">
        <div class="font-weight-bold mb-2">Filter Data</div>
        
        <div class="card-body">
          <div class="row">
            <div class="col-md-3">
              <label>Tanggal Mulai</label>
              <input type="date" id="tgl1" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-3">
              <label>Tanggal Akhir</label>
              <input type="date" id="tgl2" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-4">
              <label>Poliklinik</label>
              <select id="kdPoli" class="form-control form-control-sm" multiple></select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
              <button class="btn btn-primary btn-sm" id="applyFilter">Terapkan</button>
              <button class="btn btn-secondary btn-sm ml-2" id="resetFilter">Reset</button>
            </div>
          </div>
        </div>
    </div>
    
    <div class="card p-3">
      <div class="font-weight-bold mb-2">Top ICD10 Diagnosa</div><br>
      <table id="dtIcd10" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
        <thead>
        <tr>
          <th>Kode</th>
          <th>Diagnosa</th>
          <th class="text-right">Total</th>
        </tr>
        </thead>
      </table>
    </div>
    <br>
    <div class="card p-3">  
      <div class="font-weight-bold mb-2">Top ICD9 Diagnosa</div><br>
      <table id="dtIcd9" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
        <thead>
        <tr>
          <th>Kode</th>
          <th>Prosedur</th>
          <th class="text-right">Total</th>
        </tr>
        </thead>
      </table>
    </div>
  </div>
  
<script>
const API = {
  poli: 'api/icd-poli-list.php',
  icd10: 'api/icd10-datatable.php',
  icd9: 'api/icd9-datatable.php'
};

let dtIcd10 = null, dtIcd9 = null;

function loadPoli(){
  fetch(API.poli).then(r=>r.json()).then(data=>{
    const sel = document.getElementById('kdPoli');
    sel.innerHTML = '';
    data.forEach(it=>{
      const opt = document.createElement('option');
      opt.value = it.value;
      opt.textContent = it.label;
      sel.appendChild(opt);
    });
  });
}

function values(id){
  return Array.from(document.getElementById(id).selectedOptions).map(o=>o.value).filter(Boolean);
}

function initIcd10(){
  dtIcd10 = $('#dtIcd10').DataTable({
    processing:true,
    serverSide:false,
    responsive:false,
    autoWidth:false,
    scrollX:true,
    lengthMenu:[10,25,50,100],
    dom:'lBfrtip',
    buttons:[
      { extend:'copyHtml5', text:'Copy' },
      { extend:'excelHtml5', text:'Excel', filename:'rm_rajal_'+new Date().toISOString().slice(0,10) },
    ],
    ordering: false,
    info: true,
    ajax: {
      url: API.icd10,
      type: 'POST',
      data: function(d){
        d.type = 'rajal';
        d.tgl1 = document.getElementById('tgl1').value;
        d.tgl2 = document.getElementById('tgl2').value;
        d.kd_poli = values('kdPoli');
        return d;
      }
    },
    columns: [
      { data: 'icd10', className: 'font-monospace', width: '15%' },
      { data: 'penyakit', width: '65%' },
      { data: 'total', className: 'text-right font-weight-bold', width: '20%' }
    ],
    language: {
      info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ diagnosa"
    }
  });
}

function initIcd9(){
  dtIcd9 = $('#dtIcd9').DataTable({
    processing:true,
    serverSide:false,
    responsive:false,
    autoWidth:false,
    scrollX:true,
    lengthMenu:[10,25,50,100],
    dom:'lBfrtip',
    buttons:[
      { extend:'copyHtml5', text:'Copy' },
      { extend:'excelHtml5', text:'Excel', filename:'rm_rajal_'+new Date().toISOString().slice(0,10) },
    ],
    ordering: false,
    info: true,
    ajax: {
      url: API.icd9,
      type: 'POST',
      data: function(d){
        d.type = 'rajal';
        d.tgl1 = document.getElementById('tgl1').value;
        d.tgl2 = document.getElementById('tgl2').value;
        d.kd_poli = values('kdPoli');
        return d;
      }
    },
    columns: [
      { data: 'icd9', className: 'font-monospace', width: '15%' },
      { data: 'prosedur', width: '65%' },
      { data: 'total', className: 'text-right font-weight-bold', width: '20%' }
    ],
    language: {
      info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ prosedur"
    }
  });
}

document.addEventListener('DOMContentLoaded', function(){
  loadPoli();
  
  setTimeout(() => {
    initIcd10();
    initIcd9();
  }, 200);

  document.getElementById('applyFilter').addEventListener('click', ()=>{
    dtIcd10?.ajax.reload();
    dtIcd9?.ajax.reload();
  });

  document.getElementById('resetFilter').addEventListener('click', ()=>{
    const today = new Date().toISOString().slice(0,10);
    document.getElementById('tgl1').value = today;
    document.getElementById('tgl2').value = today;
    Array.from(document.getElementById('kdPoli').options).forEach(o=>o.selected=false);
    dtIcd10?.ajax.reload();
    dtIcd9?.ajax.reload();
  });

  // auto reload
  ['tgl1','tgl2','kdPoli'].forEach(id=>{
    document.getElementById(id).addEventListener('change', ()=>{
      dtIcd10?.ajax.reload();
      dtIcd9?.ajax.reload();
    });
  });
});
</script>
