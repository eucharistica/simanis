<?php
declare(strict_types=1);
require_once __DIR__.'/../lib_auth.php';
require_login();
?>
<style>
.rl3192ranap-page .filter-card label{font-weight:600;font-size:.85rem;margin-bottom:.35rem}
.rl3192ranap-page th{text-align:center;vertical-align:middle!important;white-space:normal;min-width:150px}
.rl3192ranap-page th:first-child{min-width:55px}.rl3192ranap-page th:nth-child(2){min-width:95px}.rl3192ranap-page th:nth-child(3){min-width:230px}
.rl3192ranap-page td{vertical-align:middle!important}.rl3192ranap-page .metric{font-size:1.05rem;font-weight:700}
</style>
<div class="container-fluid px-2 px-md-3 py-3 rl3192ranap-page">
  <div class="mb-3">
    <h3 class="mb-1 font-weight-bold">RL 3.19.2 Penggunaan Antibiotik Ranap</h3>
    <div class="text-muted">Proporsi kunjungan rawat inap yang mendapatkan antibiotik Access, Watch, atau Reserve.</div>
  </div>
  <div id="rl3192ranapAlert" role="alert"></div>
  <form id="rl3192ranapForm" class="card card-soft filter-card mb-3">
    <div class="card-header card-soft-header py-2"><div class="card-section-title">Filter Periode Survei</div></div>
    <div class="card-body"><div class="form-row align-items-end">
      <div class="form-group col-md-3"><label for="rl3192ranapStart">Tanggal Mulai</label><input type="date" id="rl3192ranapStart" class="form-control form-control-sm" value="<?= date('Y-m-01') ?>" required></div>
      <div class="form-group col-md-3"><label for="rl3192ranapEnd">Tanggal Akhir</label><input type="date" id="rl3192ranapEnd" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div>
    </div>
    <small class="text-muted">Antibiotik dihitung dari tanggal pemberian obat. Sampel dihitung dari tanggal registrasi pada periode kalender yang sama. Kunjungan batal tidak dihitung.</small></div>
    <div class="card-footer bg-white py-2"><button class="btn btn-sm btn-primary" id="rl3192ranapApply">Terapkan</button><button type="button" class="btn btn-sm btn-secondary ml-2" id="rl3192ranapReset">Reset</button></div>
  </form>
  <div class="card card-soft">
    <div class="card-header card-soft-header-primary py-2"><div class="card-section-title">Data RL 3.19.2</div><div id="rl3192ranapContext" class="small text-muted mt-1" aria-live="polite">Memuat data…</div></div>
    <div class="card-body"><div class="table-responsive">
      <table id="rl3192ranapTable" class="table table-striped table-bordered" style="width:100%"><thead><tr>
        <th>No.</th><th>Kode Spesialis</th><th>Spesialis</th><th>Pasien Mendapatkan Antibiotik Jumlah</th><th>Jumlah Sampel Periode Survei</th><th>Pasien Mendapatkan Antibiotik Persen</th>
      </tr></thead><tfoot><tr><th colspan="3" class="text-right">Total</th><th></th><th></th><th></th></tr></tfoot></table>
    </div></div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  const get=id=>document.getElementById(id);
  const percent=new Intl.NumberFormat('id-ID',{minimumFractionDigits:2,maximumFractionDigits:2});
  let totals={antibiotik:0,sampel:0,persen:0};
  const table=$('#rl3192ranapTable').DataTable({processing:true,serverSide:false,paging:false,searching:false,info:false,ordering:false,dom:'Brt',
    buttons:[{extend:'copyHtml5',text:'Copy',exportOptions:{columns:':visible'}},{extend:'excelHtml5',text:'Excel',title:'RL 3.19.2 Penggunaan Antibiotik Ranap',filename:'rl_3_19_2_penggunaan_antibiotik_ranap',exportOptions:{columns:':visible'}}],
    ajax:{url:'api/rl3192ranap-antibiotik-rajal.php',type:'POST',data:d=>{d.startDate=get('rl3192ranapStart').value;d.endDate=get('rl3192ranapEnd').value},dataSrc:json=>{
      if(!json.ok)throw new Error(json.message||'Gagal memuat data.');
      totals.antibiotik=json.data.reduce((n,r)=>n+Number(r.jumlah_antibiotik||0),0);totals.sampel=json.data.reduce((n,r)=>n+Number(r.jumlah_sampel||0),0);totals.persen=totals.sampel?totals.antibiotik/totals.sampel*100:0;
      get('rl3192ranapContext').textContent=json.filters.startDate+' s.d. '+json.filters.endDate+' • '+json.data.length+' spesialis';return json.data;
    },error:x=>{let msg='Gagal memuat data RL 3.19.2.';try{msg=JSON.parse(x.responseText).message||msg}catch(_e){}showError(msg)}},
    columns:[{data:'no',className:'text-center'},{data:'kd_sps'},{data:'nm_sps'},{data:'jumlah_antibiotik',className:'text-right metric'},{data:'jumlah_sampel',className:'text-right metric'},{data:'persen',className:'text-right metric',render:(v,type)=>type==='display'?percent.format(v)+' %':v}],
    footerCallback:function(){const api=this.api();$(api.column(3).footer()).html(totals.antibiotik);$(api.column(4).footer()).html(totals.sampel);$(api.column(5).footer()).html(percent.format(totals.persen)+' %')},
    language:{processing:'Memuat…',emptyTable:'Tidak ada data.',zeroRecords:'Data tidak ditemukan'}
  });
  function showError(message){get('rl3192ranapAlert').textContent=message;get('rl3192ranapAlert').className='alert alert-danger';get('rl3192ranapContext').textContent='Data gagal dimuat.'}
  function reload(){get('rl3192ranapAlert').textContent='';get('rl3192ranapAlert').className='';if(!get('rl3192ranapStart').value||!get('rl3192ranapEnd').value||get('rl3192ranapStart').value>get('rl3192ranapEnd').value){showError('Periksa rentang tanggal.');return}table.ajax.reload()}
  get('rl3192ranapForm').addEventListener('submit',e=>{e.preventDefault();reload()});
  get('rl3192ranapReset').addEventListener('click',()=>{get('rl3192ranapStart').value=get('rl3192ranapStart').defaultValue;get('rl3192ranapEnd').value=get('rl3192ranapEnd').defaultValue;reload()});
});
</script>

