<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
?>
<style>
.tindakan-dokter-page .filter-card label{font-weight:600;font-size:.85rem;margin-bottom:.35rem}
.tindakan-dokter-page .filter-card .form-group{margin-bottom:.75rem}
.tindakan-dokter-page table.dataTable thead th{vertical-align:middle;white-space:nowrap}
.tindakan-list{margin:0;padding-left:1.1rem;white-space:normal;min-width:280px}
.soap-text{white-space:pre-wrap;word-break:break-word;min-height:42px}
</style>

<div class="container-fluid px-2 px-md-3 py-3 tindakan-dokter-page">
  <div class="mb-3">
    <h3 class="mb-1 font-weight-bold">Tindakan Harian</h3>
    <div class="text-muted">Daftar tindakan dokter pada pelayanan rawat jalan dan rawat inap</div>
  </div>
  <div id="alertContainer"></div>

  <div class="card card-soft filter-card mb-3">
    <div class="card-header card-soft-header py-2"><div class="card-section-title">Filter Data</div></div>
    <div class="card-body">
      <div class="form-row align-items-end">
        <div class="form-group col-md-2"><label for="tgl1">Tanggal Mulai</label><input type="date" class="form-control form-control-sm" id="tgl1" value="<?= date('Y-m-d') ?>"></div>
        <div class="form-group col-md-2"><label for="tgl2">Tanggal Akhir</label><input type="date" class="form-control form-control-sm" id="tgl2" value="<?= date('Y-m-d') ?>"></div>
        <div class="form-group col-md-3"><label for="kdPj">Cara Bayar</label><select id="kdPj" class="form-control form-control-sm"><option value="">Semua Cara Bayar</option></select></div>
        <div class="form-group col-md-3"><label for="kdDokter">Dokter</label><select id="kdDokter" class="form-control form-control-sm"><option value="">Semua Dokter</option></select></div>
        <div class="form-group col-md-2">
          <label for="statusPembayaran">Status</label>
          <select id="statusPembayaran" class="form-control form-control-sm">
            <option value="semua">Semua</option>
            <option value="piutang_belum">Piutang Belum Lunas</option>
            <option value="piutang_lunas">Piutang Sudah Lunas</option>
            <option value="sudah_non_piutang">Sudah Bayar Non Piutang</option>
            <option value="belum_closing">Belum Terclosing Kasir</option>
          </select>
        </div>
      </div>
    </div>
    <div class="card-footer bg-white py-2">
      <button type="button" class="btn btn-sm btn-primary" id="btnTampilkan"><i class="fas fa-search mr-1"></i>Tampilkan</button>
      <button type="button" class="btn btn-sm btn-secondary ml-1" id="btnReset">Reset</button>
    </div>
  </div>

  <div class="card card-soft">
    <div class="card-body">
      <ul class="nav nav-tabs" id="jenisRawatTabs" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tabRalan" role="tab"><i class="fas fa-stethoscope mr-1"></i>Rawat Jalan</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tabRanap" role="tab"><i class="fas fa-bed mr-1"></i>Rawat Inap</a></li>
      </ul>
      <div class="tab-content pt-3">
        <div class="tab-pane fade show active" id="tabRalan" role="tabpanel">
          <table id="dtRalan" class="table table-striped table-bordered nowrap" style="width:100%">
            <thead><tr><th>No. Rawat</th><th>No. RM</th><th>Dokter</th><th>Nama Pasien</th><th>Tindakan Dokter</th><th>Tindakan Paramedis</th><th>Tindakan Gabungan</th><th>Status</th><th>Pemeriksaan</th></tr></thead>
          </table>
          <hr class="my-4">
          <h5 class="font-weight-bold mb-1">Ringkasan Tindakan Rawat Jalan</h5>
          <div class="text-muted small mb-3">Tindakan gabungan dihitung pada peran dokter dan paramedis; jumlah kejadian unik tidak digandakan.</div>
          <table id="dtRingkasanRalan" class="table table-striped table-bordered nowrap" style="width:100%">
            <thead><tr><th>Dokter Registrasi</th><th>Nama Tindakan</th><th>Jumlah Dokter</th><th>Jumlah Paramedis</th><th>Jumlah Kejadian Unik</th></tr></thead>
          </table>
        </div>
        <div class="tab-pane fade" id="tabRanap" role="tabpanel">
          <table id="dtRanap" class="table table-striped table-bordered nowrap" style="width:100%">
            <thead><tr><th>No. Rawat</th><th>No. RM</th><th>Dokter</th><th>Nama Pasien</th><th>Tindakan Dokter</th><th>Tindakan Paramedis</th><th>Tindakan Gabungan</th></tr></thead>
          </table>
          <hr class="my-4">
          <h5 class="font-weight-bold mb-1">Ringkasan Tindakan Rawat Inap</h5>
          <div class="text-muted small mb-3">Tindakan gabungan dihitung pada peran dokter dan paramedis; jumlah kejadian unik tidak digandakan.</div>
          <table id="dtRingkasanRanap" class="table table-striped table-bordered nowrap" style="width:100%">
            <thead><tr><th>Dokter Registrasi</th><th>Nama Tindakan</th><th>Jumlah Dokter</th><th>Jumlah Paramedis</th><th>Jumlah Kejadian Unik</th></tr></thead>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="soapModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header"><div><h5 class="modal-title">Pemeriksaan Rawat Jalan</h5><small class="text-muted" id="soapPatient"></small></div><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
      <div class="modal-body" id="soapContent"><div class="text-center text-muted py-4">Memuat data...</div></div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button></div>
    </div>
  </div>
</div>

<script>
const API={list:'api/tindakan-harian-dokter-list.php',data:'api/tindakan-harian-dokter-data.php',soap:'api/tindakan-harian-dokter-soap.php',summary:'api/tindakan-harian-ringkasan.php'};
let dtRalan,dtRanap,dtRingkasanRalan,dtRingkasanRanap;
const esc=value=>String(value??'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c]));
function alertError(message){document.getElementById('alertContainer').innerHTML=`<div class="alert alert-danger py-2">${esc(message)}</div>`}
function filterData(d,mode){d.mode=mode;d.tgl1=$('#tgl1').val();d.tgl2=$('#tgl2').val();d.kd_pj=$('#kdPj').val();d.kd_dokter=$('#kdDokter').val();d.status=$('#statusPembayaran').val()}
function actionGroups(value){
  const groups={};
  String(value||'').split('||').filter(Boolean).forEach(item=>{
    const pos=item.indexOf('::');
    const date=pos>=0?item.slice(0,pos):'-';
    const name=pos>=0?item.slice(pos+2):item;
    (groups[date]??=[]).push(name);
  });
  return groups;
}
function actionPlain(value){
  const groups=actionGroups(value);
  if(!Object.keys(groups).length)return '-';
  return Object.entries(groups).map(([date,items])=>{
    const [y,m,d]=date.split('-');
    return `Tanggal ${d||''}-${m||''}-${y||''}\n${items.map((name,i)=>`${i+1}. ${name}`).join('\n')}`;
  }).join('\n\n');
}
function treatmentList(value,type){
  if(type!=='display')return actionPlain(value);
  const groups=actionGroups(value);
  if(!Object.keys(groups).length)return '<span class="text-muted">-</span>';
  return Object.entries(groups).map(([date,items])=>{
    const [y,m,d]=date.split('-');
    return `<div class="mb-2"><strong>Tanggal ${esc(`${d}-${m}-${y}`)}</strong><ol class="tindakan-list">${items.map(x=>`<li>${esc(x)}</li>`).join('')}</ol></div>`;
  }).join('');
}
function currentFilters(mode){return {mode,tgl1:$('#tgl1').val(),tgl2:$('#tgl2').val(),kd_pj:$('#kdPj').val(),kd_dokter:$('#kdDokter').val(),status:$('#statusPembayaran').val(),export_all:'1'}}
function columnChooser(labels){
  return {
    extend:'collection',
    text:'<i class="fas fa-columns mr-1"></i>Pilih Kolom',
    autoClose:false,
    buttons:labels.map((label,index)=>({
      text:label,
      columnIndex:index,
      init:function(dt,node,config){this.active(dt.column(config.columnIndex).visible())},
      action:function(_e,dt,_node,config){
        const column=dt.column(config.columnIndex);
        const visible=!column.visible();
        column.visible(visible);
        this.active(visible);
        setTimeout(()=>dt.columns.adjust(),50);
      }
    }))
  };
}
async function downloadExcel(mode){
  const body=new URLSearchParams(currentFilters(mode));
  try{
    const r=await fetch(API.data,{method:'POST',body,headers:{Accept:'application/json','Content-Type':'application/x-www-form-urlencoded'}});
    const j=await r.json();
    if(!r.ok||j.message)throw new Error(j.message||'Data Excel gagal dimuat.');
    const isRalan=mode==='ralan';
    const sourceTable=isRalan?dtRalan:dtRanap;
    const definitions=[
      {header:'No. Rawat',value:row=>row.no_rawat},
      {header:'No. RM',value:row=>row.no_rkm_medis},
      {header:'Dokter',value:row=>row.nm_dokter},
      {header:'Nama Pasien',value:row=>row.nm_pasien},
      {header:'Tindakan Dokter',value:row=>actionPlain(row.tindakan_dokter)},
      {header:'Tindakan Paramedis',value:row=>actionPlain(row.tindakan_paramedis)},
      {header:'Tindakan Gabungan',value:row=>actionPlain(row.tindakan_gabungan)}
    ];
    if(isRalan)definitions.push({header:'Status',value:row=>row.status||''});
    const selected=definitions.filter((_definition,index)=>sourceTable.column(index).visible());
    if(!selected.length)throw new Error('Pilih minimal satu kolom sebelum mengunduh Excel.');
    const headers=selected.map(definition=>definition.header);
    const table=document.createElement('table');
    table.style.display='none';
    const thead=table.createTHead().insertRow();headers.forEach(h=>{const th=document.createElement('th');th.textContent=h;thead.appendChild(th)});
    const tbody=table.createTBody();
    (j.data||[]).forEach(row=>{
      const values=selected.map(definition=>definition.value(row));
      const tr=tbody.insertRow();values.forEach(v=>{const td=tr.insertCell();td.textContent=v??''});
    });
    document.body.appendChild(table);
    const exportTable=$(table).DataTable({paging:false,searching:false,ordering:false,info:false,dom:'B',buttons:[{extend:'excelHtml5',title:`Tindakan Harian - ${isRalan?'Rawat Jalan':'Rawat Inap'}`,filename:`tindakan_harian_${mode}_${$('#tgl1').val()}_${$('#tgl2').val()}`} ]});
    exportTable.button(0).trigger();
    setTimeout(()=>{exportTable.destroy();table.remove()},500);
  }catch(e){alertError(e.message||'Gagal mengunduh Excel.')}
}
function commonOptions(mode,columns){
  const labels=mode==='ralan'
    ? ['No. Rawat','No. RM','Dokter','Nama Pasien','Tindakan Dokter','Tindakan Paramedis','Tindakan Gabungan','Status','Pemeriksaan']
    : ['No. Rawat','No. RM','Dokter','Nama Pasien','Tindakan Dokter','Tindakan Paramedis','Tindakan Gabungan'];
  return {processing:true,serverSide:true,responsive:false,scrollX:true,pageLength:25,lengthMenu:[10,25,50,100],dom:'lBfrtip',buttons:[{extend:'copyHtml5',text:'Copy',exportOptions:{columns:':visible:not(.no-export)'}},{text:'Excel Semua',action:()=>downloadExcel(mode)},columnChooser(labels)],ajax:{url:API.data,type:'POST',data:d=>filterData(d,mode),error:x=>{console.error(x.responseText);alertError('Gagal mengambil data tindakan dokter.')}},columns,order:[],language:{processing:'Memuat...',info:'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',lengthMenu:'Tampilkan _MENU_ data',search:'Cari:',zeroRecords:'Data tidak ditemukan',emptyTable:'Belum ada data'}};
}
async function loadFilters(){const r=await fetch(API.list,{headers:{Accept:'application/json'}});const j=await r.json();if(!r.ok||!j.ok)throw new Error(j.message||'Filter gagal dimuat.');[['kdPj',j.cara_bayar,'Semua Cara Bayar'],['kdDokter',j.dokter,'Semua Dokter']].forEach(([id,rows,label])=>{const s=document.getElementById(id);s.innerHTML=`<option value="">${label}</option>`;rows.forEach(row=>{const o=document.createElement('option');o.value=row.value;o.textContent=row.label;s.appendChild(o)})})}
function summaryOptions(mode){const labels=['Dokter Registrasi','Nama Tindakan','Jumlah Dokter','Jumlah Paramedis','Jumlah Kejadian Unik'];return {data:[],responsive:false,scrollX:true,pageLength:25,lengthMenu:[10,25,50,100,-1],dom:'lBfrtip',buttons:[{extend:'copyHtml5',text:'Copy',exportOptions:{columns:':visible'}},{extend:'excelHtml5',text:'Excel Ringkasan',filename:`ringkasan_tindakan_${mode}`,exportOptions:{columns:':visible'}},columnChooser(labels)],columns:[{data:'nm_dokter'},{data:'nm_perawatan'},{data:'jumlah_dokter',className:'text-right'},{data:'jumlah_paramedis',className:'text-right'},{data:'jumlah_unik',className:'text-right font-weight-bold'}],order:[[0,'asc'],[1,'asc']],language:{info:'Menampilkan _START_ sampai _END_ dari _TOTAL_ tindakan',lengthMenu:'Tampilkan _MENU_ tindakan',search:'Cari:',zeroRecords:'Data tidak ditemukan',emptyTable:'Belum ada data'}}}
async function reloadSummary(mode,table){try{const body=new URLSearchParams(currentFilters(mode));body.delete('export_all');const r=await fetch(API.summary,{method:'POST',body,headers:{Accept:'application/json','Content-Type':'application/x-www-form-urlencoded'}});const j=await r.json();if(!r.ok||!j.ok)throw new Error(j.message||'Ringkasan gagal dimuat.');table.clear().rows.add(j.data||[]).draw()}catch(e){alertError(e.message||'Gagal memuat ringkasan tindakan.')}}
function reload(){document.getElementById('alertContainer').innerHTML='';if($('#tgl1').val()>$('#tgl2').val())return alertError('Tanggal mulai tidak boleh melebihi tanggal akhir.');dtRalan.ajax.reload();dtRanap.ajax.reload();reloadSummary('ralan',dtRingkasanRalan);reloadSummary('ranap',dtRingkasanRanap)}
async function showSoap(row){$('#soapPatient').text(`${row.no_rawat} • ${row.no_rkm_medis} • ${row.nm_pasien}`);$('#soapContent').html('<div class="text-center text-muted py-4">Memuat data...</div>');$('#soapModal').modal('show');try{const r=await fetch(`${API.soap}?no_rawat=${encodeURIComponent(row.no_rawat)}`,{headers:{Accept:'application/json'}});const j=await r.json();if(!r.ok||!j.ok)throw new Error(j.message||'Pemeriksaan gagal dimuat.');if(!j.data.length){$('#soapContent').html('<div class="alert alert-info mb-0">Belum ada pemeriksaan dokter yang sesuai.</div>');return}$('#soapContent').html(j.data.map(x=>`<div class="card mb-3"><div class="card-header py-2 font-weight-bold">${esc(x.tgl_perawatan)} ${esc(x.jam_rawat)}</div><div class="card-body"><div class="row"><div class="col-md-6 mb-3"><strong>S — Keluhan</strong><div class="soap-text border rounded p-2 mt-1">${esc(x.keluhan)||'-'}</div></div><div class="col-md-6 mb-3"><strong>O — Pemeriksaan</strong><div class="soap-text border rounded p-2 mt-1">${esc(x.pemeriksaan)||'-'}</div></div><div class="col-md-6 mb-3"><strong>A — Penilaian</strong><div class="soap-text border rounded p-2 mt-1">${esc(x.penilaian)||'-'}</div></div><div class="col-md-6 mb-3"><strong>P — RTL</strong><div class="soap-text border rounded p-2 mt-1">${esc(x.rtl)||'-'}</div></div></div></div></div>`).join(''))}catch(e){$('#soapContent').html(`<div class="alert alert-danger mb-0">${esc(e.message)}</div>`)}}
document.addEventListener('DOMContentLoaded',async()=>{
  try{await loadFilters()}catch(e){alertError(e.message)}
  dtRalan=$('#dtRalan').DataTable(commonOptions('ralan',[{data:'no_rawat'},{data:'no_rkm_medis'},{data:'nm_dokter'},{data:'nm_pasien'},{data:'tindakan_dokter',orderable:false,render:treatmentList},{data:'tindakan_paramedis',orderable:false,render:treatmentList},{data:'tindakan_gabungan',orderable:false,render:treatmentList},{data:'status'},{data:null,orderable:false,searchable:false,className:'text-center no-export',render:()=>'<button class="btn btn-sm btn-info btn-soap"><i class="fas fa-notes-medical mr-1"></i>SOAP</button>'}]));
  dtRanap=$('#dtRanap').DataTable(commonOptions('ranap',[{data:'no_rawat'},{data:'no_rkm_medis'},{data:'nm_dokter'},{data:'nm_pasien'},{data:'tindakan_dokter',orderable:false,render:treatmentList},{data:'tindakan_paramedis',orderable:false,render:treatmentList},{data:'tindakan_gabungan',orderable:false,render:treatmentList}]));
  dtRingkasanRalan=$('#dtRingkasanRalan').DataTable(summaryOptions('rawat_jalan'));
  dtRingkasanRanap=$('#dtRingkasanRanap').DataTable(summaryOptions('rawat_inap'));
  reloadSummary('ralan',dtRingkasanRalan);
  reloadSummary('ranap',dtRingkasanRanap);
  $('#dtRalan tbody').on('click','.btn-soap',function(){showSoap(dtRalan.row($(this).closest('tr')).data())});
  $('#btnTampilkan').on('click',reload);
  $('#btnReset').on('click',()=>{const today=new Date().toISOString().slice(0,10);$('#tgl1,#tgl2').val(today);$('#kdPj,#kdDokter').val('');$('#statusPembayaran').val('semua');reload()});
  $('a[data-toggle="tab"]').on('shown.bs.tab',()=>setTimeout(()=>$.fn.dataTable.tables({visible:true,api:true}).columns.adjust(),50));
});
</script>
