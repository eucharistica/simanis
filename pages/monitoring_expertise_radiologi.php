<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

function mer_e(?string $value): string {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function mer_datetime(?string $date, ?string $time): string {
  if (!$date || $date === '0000-00-00') return '-';
  $timestamp = strtotime($date.' '.($time ?: '00:00:00'));
  return $timestamp ? date('d-m-Y H:i', $timestamp) : mer_e($date.' '.$time);
}

$today = date('Y-m-d');
$tglAwal = (string)($_GET['tgl_awal'] ?? $today);
$tglAkhir = (string)($_GET['tgl_akhir'] ?? $today);
$filter = (string)($_GET['hasil'] ?? 'belum');
$jenisRawat = (string)($_GET['jenis_rawat'] ?? 'semua');
$poliFilter = $_GET['kd_poli'] ?? [];
if (!is_array($poliFilter)) $poliFilter = [$poliFilter];
$poliFilter = array_values(array_unique(array_filter(array_map(static fn($v): string => trim((string)$v), $poliFilter), static fn(string $v): bool => $v !== '')));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglAwal)) $tglAwal = $today;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglAkhir)) $tglAkhir = $today;
if ($tglAwal > $tglAkhir) [$tglAwal, $tglAkhir] = [$tglAkhir, $tglAwal];
if (!in_array($filter, ['belum','sudah','semua'], true)) $filter = 'belum';
if (!in_array($jenisRawat, ['semua','Ralan','Ranap'], true)) $jenisRawat = 'semua';
$poliOptions = pdo()->query("SELECT kd_poli, nm_poli FROM poliklinik ORDER BY nm_poli")->fetchAll();

$sql = "
  SELECT
    pr.no_rawat, rp.no_rkm_medis, ps.nm_pasien,
    COALESCE(pl.nm_poli, '-') AS nm_poli,
    pr.status AS jenis_rawat, pr.kd_jenis_prw, jpr.nm_perawatan,
    pr.tgl_periksa, pr.jam,
    hr.hasil, hr.tgl_hasil, hr.jam_hasil
  FROM periksa_radiologi pr
  INNER JOIN reg_periksa rp ON rp.no_rawat = pr.no_rawat
  INNER JOIN pasien ps ON ps.no_rkm_medis = rp.no_rkm_medis
  LEFT JOIN poliklinik pl ON pl.kd_poli = rp.kd_poli
  INNER JOIN jns_perawatan_radiologi jpr ON jpr.kd_jenis_prw = pr.kd_jenis_prw
  LEFT JOIN (
    SELECT no_rawat, tgl_periksa, jam,
      MAX(NULLIF(TRIM(hasil), '')) AS hasil,
      MAX(tgl_hasil) AS tgl_hasil,
      MAX(jam_hasil) AS jam_hasil
    FROM hasil_radiologi
    GROUP BY no_rawat, tgl_periksa, jam
  ) hr ON hr.no_rawat = pr.no_rawat
      AND hr.tgl_periksa = pr.tgl_periksa
      AND hr.jam = pr.jam
  WHERE pr.tgl_periksa BETWEEN :tgl_awal AND :tgl_akhir
    AND pr.kd_jenis_prw NOT IN ('J000120','J000145','RAD-11101')
    AND rp.kd_pj = 'BPJ'
    AND (:jenis_rawat = 'semua' OR pr.status = :jenis_rawat_status)
";
$params = ['tgl_awal'=>$tglAwal, 'tgl_akhir'=>$tglAkhir, 'jenis_rawat'=>$jenisRawat, 'jenis_rawat_status'=>$jenisRawat];
if ($poliFilter) {
  $placeholders = [];
  foreach ($poliFilter as $i => $kodePoli) {
    $key = 'poli_'.$i;
    $placeholders[] = ':'.$key;
    $params[$key] = $kodePoli;
  }
  $sql .= " AND rp.kd_poli IN (".implode(',', $placeholders).")";
}
$sql .= " ORDER BY pr.no_rawat, pr.tgl_periksa, pr.jam, jpr.nm_perawatan ";
$stmt = pdo()->prepare($sql);
$stmt->execute($params);
$allRows = $stmt->fetchAll();

$totalSudah = 0;
$totalBelum = 0;
foreach ($allRows as &$row) {
  $row['sudah_expertise'] = trim((string)$row['hasil']) !== '';
  $row['sudah_expertise'] ? $totalSudah++ : $totalBelum++;
}
unset($row);

$rows = array_values(array_filter($allRows, static function(array $row) use ($filter): bool {
  if ($filter === 'belum') return !$row['sudah_expertise'];
  if ($filter === 'sudah') return $row['sudah_expertise'];
  return true;
}));

$chartLabels = [];
$cursor = new DateTime($tglAwal.' 00:00:00');
$lastMonth = new DateTime($tglAkhir.' 00:00:00');
$cursor->modify('first day of this month');
$lastMonth->modify('first day of next month');
while ($cursor < $lastMonth) { $chartLabels[] = $cursor->format('Y-m'); $cursor->modify('+1 month'); }
$chartPoliNames = [];
$chartCounts = [];
foreach ($rows as $row) {
  $month = substr((string)$row['tgl_periksa'], 0, 7);
  $poli = (string)$row['nm_poli'];
  if (!isset($chartCounts[$poli])) { $chartPoliNames[] = $poli; $chartCounts[$poli] = array_fill(0, count($chartLabels), 0); }
  $index = array_search($month, $chartLabels, true);
  if ($index !== false) $chartCounts[$poli][$index]++;
}
$chartData = ['labels'=>$chartLabels, 'datasets'=>[]];
$chartColors = ['#4e73df','#1cc88a','#f6c23e','#e74a3b','#36b9cc','#858796','#6f42c1','#fd7e14'];
foreach ($chartPoliNames as $i => $name) {
  $chartData['datasets'][] = ['label'=>$name, 'data'=>$chartCounts[$name], 'backgroundColor'=>$chartColors[$i % count($chartColors)], 'borderColor'=>$chartColors[$i % count($chartColors)], 'borderWidth'=>1, 'borderRadius'=>5];
}
?>

<style>
  .mer-id { font-family:Consolas,"Courier New",monospace; white-space:nowrap; }
  .mer-sub { color:#6c757d; font-size:.78rem; }
  .mer-action { min-width:250px; white-space:normal; }
  .mer-result { min-width:220px; white-space:normal; max-width:420px; }
  .mer-number { font-size:1.55rem; font-weight:700; line-height:1; }
  #dtExpertiseRadiologi tbody tr:hover { background:#f8fbff; }
</style>
<link rel="stylesheet" href="plugins/select2/css/select2.min.css">
<link rel="stylesheet" href="plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">

<div class="mb-3">
  <h4 class="mb-1">Monitoring Expertise Radiologi</h4>
  <div class="text-muted small">Setiap tindakan radiologi dicocokkan dengan hasil expertise berdasarkan nomor rawat, tanggal, dan jam pemeriksaan yang sama.</div>
</div>

<div class="card mb-3"><div class="card-body py-3">
  <form method="get" class="form-row align-items-end">
    <input type="hidden" name="page" value="monitoring_expertise_radiologi">
    <div class="form-group col-sm-6 col-lg-2 mb-2"><label class="small mb-1" for="tgl_awal">Tanggal Periksa Mulai</label><input class="form-control" type="date" id="tgl_awal" name="tgl_awal" value="<?= mer_e($tglAwal) ?>"></div>
    <div class="form-group col-sm-6 col-lg-2 mb-2"><label class="small mb-1" for="tgl_akhir">Tanggal Periksa Akhir</label><input class="form-control" type="date" id="tgl_akhir" name="tgl_akhir" value="<?= mer_e($tglAkhir) ?>"></div>
    <div class="form-group col-sm-6 col-lg-2 mb-2"><label class="small mb-1" for="jenis_rawat">Jenis Rawat</label><select class="form-control" id="jenis_rawat" name="jenis_rawat"><option value="semua" <?= $jenisRawat==='semua'?'selected':'' ?>>Semua</option><option value="Ralan" <?= $jenisRawat==='Ralan'?'selected':'' ?>>Rawat Jalan</option><option value="Ranap" <?= $jenisRawat==='Ranap'?'selected':'' ?>>Rawat Inap</option></select></div>
    <div class="form-group col-sm-6 col-lg-2 mb-2"><label class="small mb-1" for="hasil">Status Expertise</label><select class="form-control" id="hasil" name="hasil"><option value="belum" <?= $filter==='belum'?'selected':'' ?>>Belum ada Expertise</option><option value="sudah" <?= $filter==='sudah'?'selected':'' ?>>Sudah ada Expertise</option><option value="semua" <?= $filter==='semua'?'selected':'' ?>>Semua tindakan</option></select></div>
    <div class="w-100 d-none d-lg-block"></div>
    <div class="form-group col-sm-12 col-lg-4 mb-2"><label class="small mb-1" for="kd_poli">Poliklinik <span class="text-muted font-weight-normal">(bisa pilih beberapa)</span></label><select class="form-control" id="kd_poli" name="kd_poli[]" multiple aria-describedby="kd_poli_help"><?php foreach ($poliOptions as $poli): ?><option value="<?= mer_e($poli['kd_poli']) ?>" <?= in_array((string)$poli['kd_poli'], $poliFilter, true) ? 'selected' : '' ?>><?= mer_e($poli['nm_poli']) ?></option><?php endforeach; ?></select><small id="kd_poli_help" class="form-text text-muted">Klik pilihan untuk mencentang beberapa poli.</small></div>
    <div class="form-group col-lg-2 mb-2"><button class="btn btn-primary btn-block"><i class="fas fa-search mr-1"></i> Tampilkan</button></div>
  </form>
</div></div>

<div class="row mb-3">
  <div class="col-sm-4 mb-2 mb-sm-0"><div class="card h-100"><div class="card-body py-3"><div class="text-muted small">Total tindakan</div><div class="mer-number"><?= count($allRows) ?></div></div></div></div>
  <div class="col-sm-4 mb-2 mb-sm-0"><div class="card h-100 border-danger"><div class="card-body py-3"><div class="text-danger small">Belum ada Expertise</div><div class="mer-number text-danger"><?= $totalBelum ?></div></div></div></div>
  <div class="col-sm-4"><div class="card h-100 border-success"><div class="card-body py-3"><div class="text-success small">Sudah ada Expertise</div><div class="mer-number text-success"><?= $totalSudah ?></div></div></div></div>
</div>

<div class="card"><div class="card-body"><div class="table-responsive">
  <table id="dtExpertiseRadiologi" class="table table-bordered table-striped table-sm" style="width:100%">
    <thead class="thead-light"><tr><th>No Rawat</th><th>No RM</th><th>Pasien</th><th>Poli</th><th>Waktu Periksa</th><th>Pemeriksaan Radiologi</th><th>Status Expertise</th><th>Hasil</th></tr></thead>
    <tbody><?php foreach ($rows as $row): ?><tr>
      <td class="mer-id"><?= mer_e($row['no_rawat']) ?></td>
      <td class="mer-id"><?= mer_e($row['no_rkm_medis']) ?></td>
      <td><?= mer_e($row['nm_pasien']) ?><div class="mer-sub"><?= mer_e($row['jenis_rawat']) ?></div></td>
      <td><?= mer_e($row['nm_poli']) ?></td>
      <td data-order="<?= mer_e($row['tgl_periksa'].' '.$row['jam']) ?>"><?= mer_datetime($row['tgl_periksa'],$row['jam']) ?></td>
      <td class="mer-action"><div><?= mer_e($row['nm_perawatan']) ?></div><div class="mer-sub"><?= mer_e($row['kd_jenis_prw']) ?></div></td>
      <td><?= $row['sudah_expertise'] ? '<span class="badge badge-success">Sudah ada Expertise</span>' : '<span class="badge badge-danger">Belum ada Expertise</span>' ?></td>
      <td class="mer-result">
        <?php if ($row['sudah_expertise']): ?>
          <div><?= nl2br(mer_e(mb_strimwidth((string)$row['hasil'],0,220,'…','UTF-8'))) ?></div>
          <div class="mer-sub">Hasil: <?= mer_datetime($row['tgl_hasil'],$row['jam_hasil']) ?></div>
        <?php else: ?><span class="text-muted">-</span><?php endif; ?>
      </td>
    </tr><?php endforeach; ?></tbody>
  </table>
</div></div></div>

<div class="card mt-3"><div class="card-header bg-light"><strong>Jumlah Tindakan Radiologi per Bulan</strong></div><div class="card-body"><div style="height:360px"><canvas id="merMonthlyChart" aria-label="Jumlah tindakan radiologi per bulan berdasarkan poliklinik"></canvas></div></div></div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function(){
  function ready(cb,n){if(window.jQuery&&jQuery.fn&&jQuery.fn.DataTable){cb(jQuery);return}if(n>0)setTimeout(function(){ready(cb,n-1)},50)}
  ready(function($){
    if ($.fn.select2) {
      $('#kd_poli').select2({width:'100%', closeOnSelect:false, placeholder:'(Semua)', allowClear:true,
        templateResult:function(d){if(!d.id)return d.text;return $('<span><input type="checkbox" class="mer-poli-check" '+(d.selected?'checked':'')+'/> '+d.text+'</span>');},
        templateSelection:function(d){return d.text;}
      });
    }
    if($.fn.dataTable.isDataTable('#dtExpertiseRadiologi'))return;
    $('#dtExpertiseRadiologi').DataTable({responsive:true,pageLength:25,lengthMenu:[10,25,50,100],order:[],autoWidth:false,dom:'Bfrtip',buttons:[{extend:'copyHtml5',text:'<i class="far fa-copy"></i> Copy',className:'btn btn-outline-secondary btn-sm'},{extend:'excelHtml5',text:'<i class="far fa-file-excel"></i> Excel',className:'btn btn-outline-success btn-sm',title:'Monitoring Expertise Radiologi <?= mer_e($jenisRawat === 'Ralan' ? 'Rawat Jalan' : ($jenisRawat === 'Ranap' ? 'Rawat Inap' : 'Semua Jenis')) ?> <?= mer_e($tglAwal) ?> s.d. <?= mer_e($tglAkhir) ?>'}],language:{emptyTable:'Tidak ada tindakan radiologi pada tanggal dan filter ini.',search:'Cari:',lengthMenu:'Tampilkan _MENU_ data',info:'Menampilkan _START_–_END_ dari _TOTAL_ data',infoEmpty:'Tidak ada data',zeroRecords:'Data tidak ditemukan',paginate:{previous:'Sebelumnya',next:'Berikutnya'}}});
  },100);
  const chartData = <?= json_encode($chartData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  const valueLabels = {id:'merValueLabels', afterDatasetsDraw(chart){const ctx=chart.ctx;ctx.save();ctx.font='600 11px Arial';ctx.textAlign='center';ctx.fillStyle='#344054';chart.data.datasets.forEach((ds,di)=>{const meta=chart.getDatasetMeta(di);meta.data.forEach((bar,i)=>{const value=Number(ds.data[i]||0);if(value>0)ctx.fillText(value.toLocaleString('id-ID'),bar.x,bar.y-6);});});ctx.restore();}};
  if(window.Chart && document.getElementById('merMonthlyChart')) new Chart(document.getElementById('merMonthlyChart'),{type:'bar',plugins:[valueLabels],data:{labels:chartData.labels.map(v=>{const [y,m]=v.split('-');return new Date(Number(y),Number(m)-1,1).toLocaleDateString('id-ID',{month:'short',year:'numeric'});}),datasets:chartData.datasets},options:{responsive:true,maintainAspectRatio:false,layout:{padding:{top:24}},scales:{x:{grid:{display:false}},y:{beginAtZero:true,ticks:{precision:0}}},plugins:{legend:{position:'top',labels:{usePointStyle:true,padding:16}},tooltip:{callbacks:{label:c=>c.dataset.label+': '+Number(c.raw||0).toLocaleString('id-ID')+' tindakan'}}}}});
})();
</script>
