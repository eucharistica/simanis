<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

$today = date('Y-m-d');
?>
<style>
.rekap-bhp-page .filter-card label {
  font-weight: 600;
  font-size: .85rem;
  margin-bottom: .35rem;
}
.rekap-bhp-page .filter-card .form-group {
  margin-bottom: .75rem;
}
.rekap-bhp-page .nav-tabs .nav-link {
  font-weight: 600;
}
.rekap-bhp-page table.dataTable thead th {
  vertical-align: middle;
  text-align: center;
  white-space: nowrap;
}
.rekap-bhp-page .text-right {
  text-align: right !important;
}
.rekap-bhp-page .dataTables_filter input,
.rekap-bhp-page .dataTables_length select {
  height: 32px;
}
</style>

<div class="container-fluid px-2 px-md-3 py-3 rekap-bhp-page">
  <div class="mb-3">
    <h3 class="mb-1 font-weight-bold">Rekap BHP</h3>
    <div class="text-muted">Rekap pengeluaran obat rawat jalan dan rawat inap</div>
  </div>

  <div id="alertContainer"></div>

  <div class="card card-soft filter-card mb-3">
    <div class="card-header card-soft-header py-2">
      <div class="card-section-title">Filter Data</div>
    </div>
    <div class="card-body">
      <div class="form-row align-items-end">
        <div class="form-group col-md-2">
          <label for="tgl1">Tanggal Mulai</label>
          <input type="date" class="form-control form-control-sm" id="tgl1" value="<?= $today ?>">
        </div>

        <div class="form-group col-md-2">
          <label for="tgl2">Tanggal Akhir</label>
          <input type="date" class="form-control form-control-sm" id="tgl2" value="<?= $today ?>">
        </div>

        <div class="form-group col-md-3" id="poliFilterGroup">
          <label for="kdPoli">Poli</label>
          <select id="kdPoli" class="form-control form-control-sm">
            <option value="">Semua Poli</option>
          </select>
        </div>

      </div>
    </div>
    <div class="card-footer bg-white py-2">
      <div class="d-flex" style="gap: 8px">
        <button type="button" class="btn btn-sm btn-primary" id="btnFilter">Terapkan</button>
        <button type="button" class="btn btn-sm btn-secondary" id="btnReset">Reset</button>
      </div>
    </div>
  </div>

  <ul class="nav nav-tabs mb-3" id="rekapBHPTab" role="tablist">
    <li class="nav-item">
      <a class="nav-link active" id="ralan-tab" data-toggle="tab" href="#ralan" role="tab">Rawat Jalan</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" id="ranap-tab" data-toggle="tab" href="#ranap" role="tab">Rawat Inap</a>
    </li>
  </ul>

  <div class="tab-content">
    <div class="tab-pane fade show active" id="ralan" role="tabpanel">
      <div class="card card-soft">
        <div class="card-header card-soft-header-primary py-2">
          <div class="card-section-title">Data BHP Rawat Jalan per Poli</div>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table id="dtRekapBHPRalan" class="table table-striped table-bordered nowrap" style="width: 100%">
              <thead>
                <tr>
                  <th>Poliklinik</th>
                  <th>Kode BHP</th>
                  <th>Nama BHP</th>
                  <th>Jumlah Keluar</th>
                  <th>Total Pengeluaran</th>
                </tr>
              </thead>
              <tfoot>
                <tr>
                  <th colspan="3" class="text-right">Total:</th>
                  <th></th>
                  <th></th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="ranap" role="tabpanel">
      <div class="card card-soft">
        <div class="card-header card-soft-header-primary py-2">
          <div class="card-section-title">Data BHP Rawat Inap</div>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table id="dtRekapBHPRanap" class="table table-striped table-bordered nowrap" style="width: 100%">
              <thead>
                <tr>
                  <th>Kode BHP</th>
                  <th>Nama BHP</th>
                  <th>Jumlah Keluar</th>
                  <th>Total Pengeluaran</th>
                </tr>
              </thead>
              <tfoot>
                <tr>
                  <th colspan="2" class="text-right">Total:</th>
                  <th></th>
                  <th></th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card card-soft mt-3">
    <div class="card-header card-soft-header-primary"><div class="card-section-title">Rekap Rata-rata Pengeluaran BHP dan Stok Terakhir</div></div>
    <div class="card-body"><div class="form-row align-items-end mb-3"><div class="form-group col-md-2 mb-0"><label for="summaryBulanAwal">Bulan Awal</label><input type="month" id="summaryBulanAwal" class="form-control form-control-sm" value="<?= date('Y-m') ?>"></div><div class="form-group col-md-2 mb-0"><label for="summaryBulanAkhir">Bulan Akhir</label><input type="month" id="summaryBulanAkhir" class="form-control form-control-sm" value="<?= date('Y-m') ?>"></div><div class="form-group col-md-2 mb-0"><button type="button" class="btn btn-sm btn-primary" id="btnSummaryFilter">Tampilkan</button></div></div><div class="table-responsive"><table id="dtRekapBHPSummary" class="table table-striped table-bordered nowrap" style="width:100%"><thead><tr><th>Kode BHP</th><th>Nama BHP</th><th>Stok Saat Ini</th><th>Persediaan (Rp.)</th><th>Jumlah Keluar</th><th>Rata-rata Pengeluaran (per bulan)</th></tr></thead><tfoot><tr><th colspan="3" class="text-right">Total Persediaan:</th><th></th><th></th><th></th></tr></tfoot></table></div></div>
  </div>

  <div class="card card-soft mt-3">
    <div class="card-header card-soft-header-primary"><div class="card-section-title">Total Pengeluaran BHP per Bulan</div></div>
    <div class="card-body"><div class="form-row align-items-end mb-3"><div class="form-group col-md-2 mb-0"><label>Bulan Awal</label><input type="month" id="chartBulanAwal" class="form-control form-control-sm" value="<?= date('Y-m') ?>"></div><div class="form-group col-md-2 mb-0"><label>Bulan Akhir</label><input type="month" id="chartBulanAkhir" class="form-control form-control-sm" value="<?= date('Y-m') ?>"></div><div class="form-group col-md-2 mb-0"><button type="button" class="btn btn-sm btn-primary" id="btnChartFilter">Tampilkan Chart</button></div></div><div style="height:360px"><canvas id="rekapBHPChart"></canvas></div><div class="alert alert-info mt-3 mb-0"><div><strong>Rata-rata total per bulan:</strong> <span id="chartAverageTotal">Rp 0</span></div><div><strong>Total seluruh bulan:</strong> <span id="chartGrandTotal">Rp 0</span></div></div></div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
const API = {
  poliList: 'api/poli-list.php',
  dt: 'api/rekap-bhp-poli-datatable.php',
  summary: 'api/rekap-obat-poli-summary.php'
};

const TODAY = <?= json_encode($today, JSON_UNESCAPED_UNICODE) ?>;
let dtRalan = null;
let dtRanap = null;
let dtSummary = null;

function showAlert(message, type = 'info') {
  const container = document.getElementById('alertContainer');
  const alert = document.createElement('div');
  alert.className = `alert alert-${type === 'error' ? 'danger' : 'info'} py-2 mb-2`;
  alert.textContent = message;
  container.appendChild(alert);
  setTimeout(() => alert.remove(), 2500);
}

function readFilters(jenisRawat) {
  const tgl1 = document.getElementById('tgl1');
  const tgl2 = document.getElementById('tgl2');

  if (tgl1.value && tgl2.value && tgl1.value > tgl2.value) {
    tgl2.value = tgl1.value;
  }

  return {
    tgl1: tgl1.value,
    tgl2: tgl2.value,
    jenis_rawat: jenisRawat,
    kd_poli: jenisRawat === 'ralan' ? document.getElementById('kdPoli').value : '',
    kode_kategori: '',
    golongan_obat: ''
  };
}

function resetFilters() {
  document.getElementById('tgl1').value = TODAY;
  document.getElementById('tgl2').value = TODAY;
  document.getElementById('kdPoli').value = '';
}

function loadPoli() {
  $.getJSON(API.poliList, function (response) {
    const $poli = $('#kdPoli').empty().append(
      $('<option>', { value: '', text: 'Semua Poli' })
    );

    (response.data || []).forEach(function (poli) {
      $poli.append($('<option>', {
        value: poli.kd_poli,
        text: poli.nm_poli
      }));
    });
  }).fail(function (xhr) {
    console.error(xhr.responseText);
    showAlert('Gagal memuat daftar poli.', 'error');
  });
}

function numberValue(value) {
  return parseFloat(String(value || 0).replace(/,/g, '')) || 0;
}

function formatNumber(value) {
  return new Intl.NumberFormat('id-ID', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  }).format(value);
}

function sumColumn(api, index) {
  return api
    .column(index, { search: 'applied' })
    .data()
    .reduce((total, value) => total + numberValue(value), 0);
}

function commonTableOptions(jenisRawat) {
  return {
    processing: true,
    serverSide: false,
    responsive: false,
    scrollX: true,
    pageLength: 10,
    lengthMenu: [[10, 30, 50, 100], [10, 30, 50, 100]],
    dom: 'lBfrtip',
    buttons: [
      { extend: 'copyHtml5', text: 'Copy' },
      { extend: 'excelHtml5', text: 'Excel', filename: `rekap_bhp_${jenisRawat}_${TODAY}` }
    ],
    ajax: {
      url: API.dt,
      type: 'POST',
      data: function () {
        return readFilters(jenisRawat);
      },
      dataSrc: 'data',
      error: function (xhr) {
        console.error(xhr.responseText);
        const response = xhr.responseJSON || {};
        showAlert(response.error || 'Gagal memuat rekap obat.', 'error');
      }
    },
    language: {
      info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
      lengthMenu: 'Tampilkan _MENU_ data',
      search: 'Cari:',
      zeroRecords: 'Data tidak ditemukan',
      emptyTable: 'Belum ada data'
    }
  };
}

function initRalanTable() {
  const options = commonTableOptions('ralan');
  options.columns = [
    { data: 'nm_poli' },
    { data: 'kode_brng', className: 'font-monospace' },
    { data: 'nama_brng' },
    { data: 'jumlah', className: 'text-right' },
    { data: 'total', className: 'text-right font-weight-bold' }
  ];
  options.order = [[0, 'asc'], [2, 'asc']];
  options.footerCallback = function () {
    const api = this.api();
    $(api.column(3).footer()).html(formatNumber(sumColumn(api, 3)));
    $(api.column(4).footer()).html(formatNumber(sumColumn(api, 4)));
  };
  dtRalan = $('#dtRekapBHPRalan').DataTable(options);
}

function initRanapTable() {
  if (dtRanap) {
    return;
  }

  const options = commonTableOptions('ranap');
  options.columns = [
    { data: 'kode_brng', className: 'font-monospace' },
    { data: 'nama_brng' },
    { data: 'jumlah', className: 'text-right' },
    { data: 'total', className: 'text-right font-weight-bold' }
  ];
  options.order = [[1, 'asc']];
  options.footerCallback = function () {
    const api = this.api();
    $(api.column(2).footer()).html(formatNumber(sumColumn(api, 2)));
    $(api.column(3).footer()).html(formatNumber(sumColumn(api, 3)));
  };
  dtRanap = $('#dtRekapBHPRanap').DataTable(options);
}

function reloadTables() {
  if (dtRalan) {
    dtRalan.ajax.reload(null, true);
  }
  if (dtRanap) {
    dtRanap.ajax.reload(null, true);
  }
}

function setActiveRawat(jenisRawat) {
  $('#poliFilterGroup').toggle(jenisRawat === 'ralan');
}

function initSummaryTable(){
  dtSummary=$('#dtRekapBHPSummary').DataTable({processing:true,serverSide:false,responsive:false,scrollX:true,pageLength:10,dom:'lBfrtip',buttons:[{extend:'copyHtml5',text:'Copy'},{extend:'excelHtml5',text:'Excel',filename:'rekap_rata_rata_bhp',exportOptions:{format:{body:function(data,row,col){return col>=2 ? "'"+String(data) : data;}}}}],ajax:{url:API.summary,type:'POST',data:function(){return {bulan_awal:$('#summaryBulanAwal').val(),bulan_akhir:$('#summaryBulanAkhir').val(),jenis:'bhp'};},dataSrc:'data',error:function(){showAlert('Gagal memuat rekap rata-rata BHP.','error');}},columns:[{data:'kode_brng',className:'font-monospace'},{data:'nama_brng'},{data:'stok',className:'text-right',render:function(v){return formatNumber(Math.floor(Number(v||0)));}},{data:'persediaan',className:'text-right font-weight-bold',render:function(v,t){const n=Number(v||0);return (t==='display'||t==='filter')?formatNumber(n):n;}},{data:'jumlah_keluar',className:'text-right',render:function(v){return formatNumber(v);}},{data:'rata2_pengeluaran',className:'text-right font-weight-bold',render:function(v){return formatNumber(v);}}],order:[[1,'asc']],footerCallback:function(){const api=this.api();$(api.column(3).footer()).html(formatNumber(sumColumn(api,3)));},language:{info:'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',lengthMenu:'Tampilkan _MENU_ data',search:'Cari:',zeroRecords:'Data tidak ditemukan',emptyTable:'Belum ada data'}});
}

document.addEventListener('DOMContentLoaded', function () {
  loadPoli();
  initRalanTable();
  initSummaryTable();
  setActiveRawat('ralan');

  document.getElementById('btnFilter').addEventListener('click', reloadTables);
  document.getElementById('btnSummaryFilter').addEventListener('click', function(){dtSummary.ajax.reload(null,true);});
  document.getElementById('btnReset').addEventListener('click', function () {
    resetFilters();
    reloadTables();
  });

  ['tgl1', 'tgl2'].forEach(function (id) {
    document.getElementById(id).addEventListener('change', reloadTables);
  });
  document.getElementById('kdPoli').addEventListener('change', function () {
    if (dtRalan) {
      dtRalan.ajax.reload(null, true);
    }
  });

  $('a[data-toggle="tab"]').on('shown.bs.tab', function (event) {
    const jenisRawat = event.target.id === 'ranap-tab' ? 'ranap' : 'ralan';
    setActiveRawat(jenisRawat);

    if (jenisRawat === 'ranap') {
      initRanapTable();
      dtRanap.columns.adjust();
    } else {
      dtRalan.columns.adjust();
    }
  });
});
</script>
<script>
let rekapBHPChart=null;
const rekapBHPValueLabels={id:'rekapBHPValueLabels',afterDatasetsDraw(chart){const c=chart.ctx;c.save();c.font='600 10px Arial';c.textAlign='center';c.textBaseline='bottom';c.fillStyle='#344054';chart.data.datasets.forEach((ds,di)=>{const meta=chart.getDatasetMeta(di);meta.data.forEach((bar,i)=>{const v=Number(ds.data[i]||0);if(!v)return;c.fillText(new Intl.NumberFormat('id-ID',{notation:'compact',maximumFractionDigits:1}).format(v),bar.x,bar.y-4);});});c.restore();}};
async function loadRekapBHPChart(){const body=new URLSearchParams({bulan_awal:document.getElementById('chartBulanAwal').value,bulan_akhir:document.getElementById('chartBulanAkhir').value});const r=await fetch('api/rekap-bhp-poli-chart.php',{method:'POST',body});const j=await r.json();if(!j.ok){showAlert(j.message||'Gagal memuat chart.','error');return}const avg=(j.data.total||[]).reduce((a,v)=>a+Number(v||0),0)/Math.max(1,(j.data.total||[]).length);document.getElementById('chartAverageTotal').textContent=new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0}).format(avg);document.getElementById('chartGrandTotal').textContent=new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0}).format((j.data.total||[]).reduce((a,v)=>a+Number(v||0),0));if(rekapBHPChart)rekapBHPChart.destroy();rekapBHPChart=new Chart(document.getElementById('rekapBHPChart'),{type:'bar',plugins:[rekapBHPValueLabels],data:{labels:j.data.labels.map(x=>{const [y,m]=x.split('-');return new Date(Number(y),Number(m)-1,1).toLocaleDateString('id-ID',{month:'short',year:'numeric'});}),datasets:[{label:'Rawat Jalan',data:j.data.ralan,backgroundColor:'#4e73df',borderColor:'#3b5fc0',borderWidth:1,borderRadius:6,barPercentage:.78},{label:'Rawat Inap',data:j.data.ranap,backgroundColor:'#f6c23e',borderColor:'#d9a514',borderWidth:1,borderRadius:6,barPercentage:.78},{label:'Total',data:j.data.total,backgroundColor:'#1cc88a',borderColor:'#159a6b',borderWidth:1,borderRadius:6,barPercentage:.78}]},options:{responsive:true,maintainAspectRatio:false,layout:{padding:{top:22}},scales:{x:{grid:{display:false},ticks:{font:{weight:'600'}}},y:{beginAtZero:true,grid:{color:'rgba(0,0,0,.08)',borderDash:[4,4]},ticks:{callback:v=>new Intl.NumberFormat('id-ID',{notation:'compact'}).format(v)}}},plugins:{legend:{position:'top',labels:{usePointStyle:true,padding:18}},tooltip:{callbacks:{label:c=>c.dataset.label+': '+new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0}).format(c.raw)}}}}});}
document.addEventListener('DOMContentLoaded',()=>{document.getElementById('btnChartFilter').addEventListener('click',loadRekapBHPChart);loadRekapBHPChart();});
</script>



