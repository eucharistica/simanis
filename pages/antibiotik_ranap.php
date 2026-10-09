<?php
declare(strict_types=1);
require_once __DIR__.'/../lib_auth.php';
require_login();
$today = date('Y-m-d');
?>
<style>
.antibiotik-page label{font-size:.85rem;font-weight:600}
.antibiotik-page .obat-list{min-width:300px;white-space:normal;padding-left:1.2rem;margin:0}
.antibiotik-page .obat-list li{margin-bottom:.5rem}
.antibiotik-page th{white-space:nowrap}.ab-check{margin-right:.45rem}
</style>
<link rel="stylesheet" href="plugins/select2/css/select2.min.css">
<link rel="stylesheet" href="plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
<div class="container-fluid px-2 px-md-3 py-3 antibiotik-page">
  <h3 class="font-weight-bold mb-1">RL 3.19.3 Kuantitas Penggunaan Antibiotik</h3>
  <p class="text-muted">Pasien rawat inap penerima antibiotik berdasarkan tanggal masuk pertama.</p>
  <div id="abAlert" role="alert"></div>
  <form id="abForm" class="card card-soft mb-3">
    <div class="card-header card-soft-header py-2">Filter Data</div>
    <div class="card-body">
      <div class="form-row align-items-end">
        <div class="form-group col-md-3"><label for="abStart">Tanggal Masuk Mulai</label><input id="abStart" type="date" class="form-control" required value="<?= date('Y-m-01') ?>"></div>
        <div class="form-group col-md-3"><label for="abEnd">Tanggal Masuk Akhir</label><input id="abEnd" type="date" class="form-control" required value="<?= $today ?>"></div>
        <div class="form-group col-md-6"><label for="abJenis">Jenis Antibiotik (bisa lebih dari satu)</label>
          <select id="abJenis" class="form-control" multiple aria-describedby="abJenisHelp"></select>
          <small id="abJenisHelp" class="text-muted">Klik pilihan untuk mencentang beberapa jenis.</small>
        </div><div class="form-group col-md-6"><label for="abObat">Nama Obat (opsional, bisa lebih dari satu)</label><select id="abObat" class="form-control" multiple size="4"></select><small class="text-muted">Jika dipilih, hanya obat yang dipilih yang ditampilkan.</small></div>
      </div>
      <small class="text-muted">LOS = selisih hari dari masuk pertama sampai pulang terakhir (minimum 1 hari). Pasien yang masih dirawat dihitung sampai hari ini. Riwayat pindah kamar digabung per nomor rawat.</small>
    </div>
    <div class="card-footer bg-white"><button id="abApply" class="btn btn-primary btn-sm" disabled>Tampilkan</button><button id="abReset" type="button" class="btn btn-secondary btn-sm ml-2" disabled>Reset</button></div>
  </form>
  <div class="card card-soft">
    <div class="card-header card-soft-header-primary"><strong>Daftar Pasien dan Pemberian Antibiotik</strong><div id="abContext" class="small text-muted mt-1" aria-live="polite">Belum dimuat</div></div>
    <div class="card-body">
      <p class="small text-muted">Jumlah obat adalah SUM(detail_pemberian_obat.jml) sepanjang kunjungan dengan status Ranap, bukan jumlah hari atau frekuensi pemberian. Total jml dapat mencampur satuan; rincian satuan tersedia per obat.</p>
      <table id="abTable" class="table table-striped table-bordered" style="width:100%"><thead><tr>
        <th>No.</th><th>No. Rawat</th><th>No. RM</th><th>Nama Pasien</th><th>Tanggal Masuk</th><th>Tanggal Pulang</th><th>Status Rawat</th><th>LOS (hari)</th><th>Antibiotik</th><th>Aturan Pakai</th><th>Total Jml</th>
      </tr></thead></table>
    </div>
  </div><div class="card card-soft mt-3"><div class="card-header card-soft-header">Ringkasan Pemberian Antibiotik</div><div class="card-body"><table class="table table-bordered table-sm mb-0"><thead><tr><th>Periode Tanggal Masuk</th><th>Total LOS (hari)</th><th>Total Jumlah</th></tr></thead><tbody><tr><td id="abSummaryPeriod">-</td><td id="abSummaryLos">0</td><td id="abSummaryQty">0</td></tr></tbody></table></div></div>
</div>
<script>
document.addEventListener('DOMContentLoaded',async()=>{
  const endpoint='api/antibiotik-ranap.php';
  const byId=id=>document.getElementById(id);
  const escape=value=>String(value??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const format=new Intl.NumberFormat('id-ID',{maximumFractionDigits:6});
  const text=$.fn.dataTable.render.text();
  const table=$('#abTable').DataTable({data:[],scrollX:true,autoWidth:false,pageLength:25,lengthMenu:[10,25,50,100],order:[[4,'desc'],[1,'asc']],dom:'lBfrtip',
    buttons:[{extend:'copyHtml5',text:'Copy',exportOptions:{columns:':visible',orthogonal:'export'}},{extend:'excelHtml5',text:'Excel Semua',title:'Pemberian Antibiotik Ranap',filename:'pemberian_antibiotik_ranap',
      exportOptions:{columns:':visible',orthogonal:'export',modifier:{search:'none',page:'all'}}}],
    columns:[
      {data:null,orderable:false,searchable:false,render:(_v,_t,_r,meta)=>meta.row+1},
      {data:'no_rawat',render:text},{data:'no_rkm_medis',render:text},{data:'nm_pasien',render:text},
      {data:'tgl_masuk',render:text},{data:'tgl_keluar',defaultContent:'-',render:(v,t)=>v?text.display(v):'-'},
      {data:'status_rawat',render:text},{data:'los',className:'text-right',defaultContent:'-'},
      {data:'obat',orderable:false,render:(items,type)=>{
        if(type==='display')return '<ul class="obat-list">'+items.map(o=>'<li><strong>'+escape(o.nama)+'</strong><br><small>'+escape(o.tanggal_pemberian||'-')+'</small><br>'+format.format(o.jumlah)+' '+escape(o.satuan)+'</li>').join('')+'</ul>';
        return items.map(o=>o.nama+' ['+o.jenis+']: '+o.jumlah+' '+o.satuan).join('\n');
      }},
      {data:'obat',orderable:false,render:(items,type)=>items.map(o=>o.aturan_pakai||'-').join(type==='display'?'<br>':'\n')},
      {data:'total_jml',className:'text-right font-weight-bold',render:(v,t)=>t==='display'?format.format(v):v}
    ],language:{emptyTable:'Tidak ada pasien penerima antibiotik pada filter ini.',search:'Cari:',lengthMenu:'Tampilkan _MENU_ kunjungan',info:'Menampilkan _START_–_END_ dari _TOTAL_ kunjungan',infoEmpty:'0 kunjungan',zeroRecords:'Data tidak ditemukan'}
  });
  function showError(msg){byId('abAlert').textContent=msg;byId('abAlert').className='alert alert-danger'}
  async function request(url,options){const response=await fetch(url,options);const result=await response.json();if(!response.ok||!result.ok)throw new Error(result.message||'Gagal memuat data.');return result}
  async function load(){
    const start=byId('abStart').value,end=byId('abEnd').value;
    const selected=Array.from(byId('abJenis').selectedOptions);
    if(!start||!end||start>end){showError('Periksa rentang tanggal masuk.');return}
    const body=new URLSearchParams({tgl1:start,tgl2:end});selected.forEach(o=>body.append('jenis[]',o.value));Array.from(byId('abObat').selectedOptions).forEach(o=>body.append('obat[]',o.value));
    byId('abAlert').textContent='';byId('abAlert').className='';
    $('#abForm :input').prop('disabled',true);table.buttons().enable(false);table.clear().draw();byId('abContext').textContent='Memuat data…';
    try{
      const result=await request(endpoint,{method:'POST',body,headers:{Accept:'application/json'}});
      table.rows.add(result.data.rows||result.data).draw();table.columns.adjust();if(result.data.summary){byId('abSummaryPeriod').textContent=start+' s.d. '+end;byId('abSummaryLos').textContent=format.format(result.data.summary.total_los);byId('abSummaryQty').textContent=format.format(result.data.summary.total_jumlah);}table.buttons().enable(true);
      byId('abContext').textContent=start+' s.d. '+end+' • '+selected.map(o=>o.text).join(', ')+' • '+(result.data.rows||result.data).length+' kunjungan • Acuan LOS: '+result.tanggal_acuan;
    }catch(e){showError(e.message);byId('abContext').textContent='Data gagal dimuat; silakan coba kembali.'}
    finally{$('#abForm :input').prop('disabled',false)}
  }
  byId('abForm').addEventListener('submit',e=>{e.preventDefault();load()});
  byId('abReset').addEventListener('click',()=>{byId('abStart').value=byId('abStart').defaultValue;byId('abEnd').value=byId('abEnd').defaultValue;Array.from(byId('abJenis').options).forEach(o=>o.selected=true);load()});
  table.buttons().enable(false);
  try{
    const result=await request(endpoint+'?action=options',{headers:{Accept:'application/json'}});
    result.data.forEach(o=>byId('abJenis').add(new Option(o.nama,o.kdjns,true,true)));(result.obat||[]).forEach(o=>byId('abObat').add(new Option(o.nama_brng,o.kode_brng)));
    if($.fn.select2){['#abJenis','#abObat'].forEach(sel=>$(sel).select2({width:'100%',closeOnSelect:false,placeholder:'(Semua)',templateResult:function(d){if(!d.id)return d.text;return $('<span><input type="checkbox" class="ab-check" '+(d.selected?'checked':'')+'/> '+d.text+'</span>');},templateSelection:function(d){return d.text;}}));}
    if(!result.data.length)throw new Error('Jenis antibiotik tidak ditemukan.');
    byId('abApply').disabled=false;byId('abReset').disabled=false;await load();
  }catch(e){showError(e.message)}
});
</script>
