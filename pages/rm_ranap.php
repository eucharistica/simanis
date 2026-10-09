<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
?>
<style>
  /* rapihin jarak label + input kecil */
  .filter-card label { font-weight:600; font-size:.85rem; margin-bottom:.35rem; }
  .filter-card .form-group { margin-bottom:.75rem; }
</style>

<div class="container-fluid px-2 px-md-3 py-3">
  <div class="mb-3">
    <h3 class="mb-1 font-weight-bold">Laporan Kunjungan Rawat Inap</h3>
    <div class="text-muted">Pasien aktif & pasien pulang, dengan filter bangsal/cara bayar</div>
  </div>

  <ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item">
      <a class="nav-link active" data-toggle="tab" href="#tabAktif" role="tab">Pasien Aktif</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-toggle="tab" href="#tabPulang" role="tab">Pasien Ranap</a>
    </li>
  </ul>

  <div class="tab-content">
    <!-- TAB AKTIF -->
    <div class="tab-pane fade show active" id="tabAktif" role="tabpanel">

      <!-- CARD FILTER -->
      <div class="card filter-card mb-3">
        <div class="card-header py-2">
          <strong>Filter Pasien Aktif</strong>
        </div>
        <div class="card-body">
          <div class="form-row">
            <div class="form-group col-md-4">
              <label>Cara Bayar</label>
              <select id="aCaraBayar" class="form-control form-control-sm" multiple></select>
            </div>
            <div class="form-group col-md-4">
              <label>Bangsal</label>
              <select id="aBangsal" class="form-control form-control-sm" multiple></select>
            </div>
          </div>
        </div>
        <div class="card-footer bg-white py-2">
          <div class="d-flex justify-content" style="gap:8px">
            <button type="button" class="btn btn-sm btn-primary" id="aApply">Terapkan</button>
            <button type="button" class="btn btn-sm btn-secondary" id="aReset">Reset</button>
          </div>
        </div>
      </div>

      <!-- CARD TABLE -->
      <div class="card mb-3">
        <div class="card-header py-2">
          <strong>Daftar Pasien Aktif</strong>
        </div>
        <div class="card-body">
          <table id="dtRanapAktif" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
            <thead>
              <tr>
                <th>No. RM</th>
                <th>Nama</th>
                <th>JK</th>
                <th>Umur Daftar</th>
                <th>Status Pasien</th>
                <th>Bangsal</th>
                <th>Kamar</th>
                <th>Tgl Masuk</th>
                <th>Jam Masuk</th>
                <th>ICD 10</th>
                <th>Diagnosa</th>
                <th>Cara Bayar</th>
                <th>DPJP</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>

    </div>

    <!-- TAB PULANG -->
    <div class="tab-pane fade" id="tabPulang" role="tabpanel">

      <!-- CARD FILTER -->
      <div class="card filter-card mb-3">
        <div class="card-header py-2">
          <strong>Filter Pasien Pulang</strong>
        </div>
        <div class="card-body">

          <div class="form-row">
            <div class="form-group col-md-3">
              <label>Filter Tanggal</label>

              <div class="custom-control custom-radio">
                <input type="radio" id="dateMasuk" name="dateMode" class="custom-control-input" value="masuk">
                <label class="custom-control-label" for="dateMasuk">Tgl Masuk</label>
              </div>
              <div class="custom-control custom-radio">
                <input type="radio" id="dateKeluar" name="dateMode" class="custom-control-input" value="keluar" checked>
                <label class="custom-control-label" for="dateKeluar">Tgl Keluar</label>
              </div>
            </div>

            <div class="form-group col-md-3">
              <label>Dari</label>
              <input type="date" id="pTgl1" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group col-md-3">
              <label>s/d</label>
              <input type="date" id="pTgl2" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
            </div>

            <div class="form-group col-md-3">
              <label>Dokter (DPJP)</label>
              <select id="pDokter" class="form-control form-control-sm" multiple></select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-4">
              <label>Cara Bayar</label>
              <select id="pCaraBayar" class="form-control form-control-sm" multiple></select>
            </div>
            <div class="form-group col-md-4">
              <label>Bangsal</label>
              <select id="pBangsal" class="form-control form-control-sm" multiple></select>
            </div>
          </div>

        </div>
        <div class="card-footer bg-white py-2">
          <div class="d-flex justify-content" style="gap:8px">
            <button type="button" class="btn btn-sm btn-primary" id="pApply">Terapkan</button>
            <button type="button" class="btn btn-sm btn-secondary" id="pReset">Reset</button>
          </div>
        </div>
      </div>

      <!-- CARD TABLE -->
      <div class="card mb-3">
        <div class="card-header py-2">
          <strong>Daftar Pasien Pulang</strong>
        </div>
        <div class="card-body">
          <table id="dtRanapPulang" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
            <thead>
              <tr>
                <th>No. RM</th>
                <th>Nama</th>
                <th>JK</th>
                <th>Umur Daftar</th>
                <th>Status Pasien</th>
                <th>Bangsal</th>
                <th>Kamar</th>
                <th>Tgl Masuk</th>
                <th>Jam Masuk</th>
                <th>ICD 10</th>
                <th>Diagnosa</th>
                <th>Tgl Keluar</th>
                <th>Jam Keluar</th>
                <th>Status Pulang</th>
                <th>Cara Bayar</th>
                <th>DPJP</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
const API = {
  bangsal: 'api/ranap-bangsal-list.php',
  bayar: 'api/ranap-carabayar-list.php',
  dokter: 'api/ranap-dokter-list.php',
  dt: 'api/ranap-datatable.php',
};

function debounce(fn, ms=250){ let t=null; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms); }; }

async function loadOptions(url, selId){
  const r = await fetch(url, {headers:{'Accept':'application/json'}});
  const data = await r.json();
  const sel = document.getElementById(selId);
  sel.innerHTML = '';
  (data||[]).forEach(it=>{
    const opt = document.createElement('option');
    opt.value = it.value;
    opt.textContent = it.label;
    sel.appendChild(opt);
  });
}

function values(id){
  return Array.from(document.getElementById(id).selectedOptions).map(o=>o.value).filter(Boolean);
}
function clearMulti(id){
  const el = document.getElementById(id);
  Array.from(el.options).forEach(o => o.selected = false);
}

let dtAktif=null, dtPulang=null;

function initAktif(){
  dtAktif = $('#dtRanapAktif').DataTable({
    processing:true,
    serverSide:false,
    responsive:false,
    autoWidth:false,
    scrollX:true,
    dom:'lBfrtip',
    buttons:[
      {extend:'copyHtml5',text:'Copy'},
      {extend:'excelHtml5',text:'Excel',filename:'rm_ranap_aktif_'+new Date().toISOString().slice(0,10)}
    ],
    ajax:{
      url: API.dt,
      type:'POST',
      data: function(d){
        d.mode='aktif';
        d.kd_pj = values('aCaraBayar');
        d.kd_bangsal = values('aBangsal');
        return d;
      }
    },
    columns:[
      {data:'no_rkm_medis'},
      {data:'nm_pasien'},
      {data:'jk'},
      {data:'umur_status'},
      {data:'stts_daftar'},
      {data:'nm_bangsal'},
      {data:'kd_kamar'},
      {data:'tgl_masuk'},
      {data:'jam_masuk'},
      {data:'kd_penyakit'},
      {data:'nm_penyakit'},
      {data:'cara_bayar'},
      {data:'nm_dokter'},
    ]
  });

  dtAktif.on('xhr.dt draw.dt', ()=> setTimeout(()=>dtAktif.columns.adjust(), 0));
}

function initPulang(){
  dtPulang = $('#dtRanapPulang').DataTable({
    processing:true,
    serverSide:false,
    responsive:false,
    autoWidth:false,
    scrollX:true,
    dom:'lBfrtip',
    buttons:[
      {extend:'copyHtml5',text:'Copy'},
      {extend:'excelHtml5',text:'Excel',filename:'rm_ranap_pulang_'+new Date().toISOString().slice(0,10)}
    ],
    ajax:{
      url: API.dt,
      type:'POST',
      data: function(d){
        d.mode='pulang';
        d.dateMode = document.querySelector('input[name="dateMode"]:checked')?.value || 'keluar';
        d.tgl1 = document.getElementById('pTgl1').value;
        d.tgl2 = document.getElementById('pTgl2').value;
        d.kd_dokter = values('pDokter');
        d.kd_pj = values('pCaraBayar');
        d.kd_bangsal = values('pBangsal');
        return d;
      }
    },
    columns:[
      {data:'no_rkm_medis'},
      {data:'nm_pasien'},
      {data:'jk'},
      {data:'umur_status'},
      {data:'stts_daftar'},
      {data:'nm_bangsal'},
      {data:'kd_kamar'},
      {data:'tgl_masuk'},
      {data:'jam_masuk'},
      {data:'kd_penyakit'},
      {data:'nm_penyakit'},
      {data:'tgl_keluar'},
      {data:'jam_keluar'},
      {data:'stts_pulang'},
      {data:'cara_bayar'},
      {data:'nm_dokter'},
    ]
  });

  dtPulang.on('xhr.dt draw.dt', ()=> setTimeout(()=>dtPulang.columns.adjust(), 0));
}

// reload handlers
const reloadAktif = debounce(()=>dtAktif?.ajax.reload(null,true), 200);
const reloadPulang = debounce(()=>dtPulang?.ajax.reload(null,true), 200);

async function init(){
  await Promise.all([
    loadOptions(API.bayar,'aCaraBayar'),
    loadOptions(API.bangsal,'aBangsal'),
    loadOptions(API.bayar,'pCaraBayar'),
    loadOptions(API.bangsal,'pBangsal'),
    loadOptions(API.dokter,'pDokter'),
  ]);

  initAktif();
  initPulang();

  // tombol apply/reset
  document.getElementById('aApply').addEventListener('click', ()=>dtAktif.ajax.reload(null,true));
  document.getElementById('pApply').addEventListener('click', ()=>dtPulang.ajax.reload(null,true));

  document.getElementById('aReset').addEventListener('click', ()=>{
    clearMulti('aCaraBayar');
    clearMulti('aBangsal');
    dtAktif.ajax.reload(null,true);
  });

  document.getElementById('pReset').addEventListener('click', ()=>{
    const today = new Date().toISOString().slice(0,10);
    document.getElementById('pTgl1').value = today;
    document.getElementById('pTgl2').value = today;
    document.getElementById('dateKeluar').checked = true;

    clearMulti('pDokter');
    clearMulti('pCaraBayar');
    clearMulti('pBangsal');

    dtPulang.ajax.reload(null,true);
  });

  // auto reload (optional)
  ['aCaraBayar','aBangsal'].forEach(id=>document.getElementById(id).addEventListener('change', reloadAktif));
  ['pCaraBayar','pBangsal','pDokter','pTgl1','pTgl2'].forEach(id=>document.getElementById(id).addEventListener('change', reloadPulang));
  document.querySelectorAll('input[name="dateMode"]').forEach(el=>el.addEventListener('change', reloadPulang));

  // Fix tab render (columns.adjust saat tab dibuka) [web:205]
  document.querySelector('a[data-toggle="tab"][href="#tabAktif"]').addEventListener('shown.bs.tab', ()=>setTimeout(()=>dtAktif.columns.adjust(),50));
  document.querySelector('a[data-toggle="tab"][href="#tabPulang"]').addEventListener('shown.bs.tab', ()=>setTimeout(()=>dtPulang.columns.adjust(),50));
}
document.addEventListener('DOMContentLoaded', init);
</script>
