<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

// Exclude poli
$exclude_poli = ["U0015", "U0016", "U0035", "U0033", "U0041"];
$exclude_list = "'" . implode("','", $exclude_poli) . "'";

// Tanggal
$bln_ini = date('Y-m');
$bln_lalu = date('Y-m', strtotime('-1 month'));
$tgl_hari_ini = date('Y-m-d');
$tgl_kemarin = date('Y-m-d', strtotime('-1 days'));
$tgl_minggu_lalu = date('Y-m-d', strtotime('-7 days'));

// Filter pulang (tab 2) - default hari ini
$pk1 = $_GET['pk1'] ?? date('Y-m-d');
$pk2 = $_GET['pk2'] ?? date('Y-m-d');

// Metrics queries (optimized - single query per metric)
$total_bulan_ini = (int)fetch_assoc(query("SELECT COUNT(*) as jml FROM reg_periksa WHERE kd_poli NOT IN ($exclude_list) AND status_lanjut='Ralan' AND stts!='Batal' AND tgl_registrasi LIKE '$bln_ini%'"))['jml'];
$total_bulan_lalu = (int)fetch_assoc(query("SELECT COUNT(*) as jml FROM reg_periksa WHERE kd_poli NOT IN ($exclude_list) AND status_lanjut='Ralan' AND stts!='Batal' AND tgl_registrasi LIKE '$bln_lalu%'"))['jml'];

$kunjungan_hari_ini = (int)fetch_assoc(query("SELECT COUNT(*) as jml FROM reg_periksa WHERE kd_poli NOT IN ($exclude_list) AND status_lanjut='Ralan' AND stts!='Batal' AND tgl_registrasi='$tgl_hari_ini'"))['jml'];
$kunjungan_minggu_lalu = (int)fetch_assoc(query("SELECT COUNT(*) as jml FROM reg_periksa WHERE kd_poli NOT IN ($exclude_list) AND status_lanjut='Ralan' AND stts!='Batal' AND tgl_registrasi='$tgl_minggu_lalu'"))['jml'];

$ranap_bulan_ini = (int)fetch_assoc(query("SELECT COUNT(*) as jml FROM reg_periksa WHERE status_lanjut='Ranap' AND stts!='Batal' AND tgl_registrasi LIKE '$bln_ini%'"))['jml'];
$ranap_bulan_lalu = (int)fetch_assoc(query("SELECT COUNT(*) as jml FROM reg_periksa WHERE status_lanjut='Ranap' AND stts!='Batal' AND tgl_registrasi LIKE '$bln_lalu%'"))['jml'];
$ranap_hari_ini = (int)fetch_assoc(query("SELECT COUNT(*) as jml FROM reg_periksa WHERE status_lanjut='Ranap' AND stts!='Batal' AND tgl_registrasi='$tgl_hari_ini'"))['jml'];
$ranap_kemarin = (int)fetch_assoc(query("SELECT COUNT(*) as jml FROM reg_periksa WHERE status_lanjut='Ranap' AND stts!='Batal' AND tgl_registrasi='$tgl_kemarin'"))['jml'];

$ranap_hari_poli = q("
  SELECT COUNT(*) as jml,
         IFNULL(poliklinik.nm_poli,'Poliklinik Lain') as nm_poli
  FROM reg_periksa
  LEFT JOIN poliklinik ON poliklinik.kd_poli = reg_periksa.kd_poli
  WHERE status_lanjut='Ranap'
    AND stts!='Batal'
    AND tgl_registrasi='$tgl_hari_ini'
  GROUP BY reg_periksa.kd_poli
  ORDER BY jml DESC
");

$ranap_kemarin_poli = q("
  SELECT COUNT(*) as jml,
         IFNULL(poliklinik.nm_poli,'Poliklinik Lain') as nm_poli
  FROM reg_periksa
  LEFT JOIN poliklinik ON poliklinik.kd_poli = reg_periksa.kd_poli
  WHERE status_lanjut='Ranap'
    AND stts!='Batal'
    AND tgl_registrasi='$tgl_kemarin'
  GROUP BY reg_periksa.kd_poli
  ORDER BY jml DESC
");


// Metrics
$metrics = [
    'kunjungan_bulan' => ['value' => $total_bulan_ini, 'prev' => $total_bulan_lalu, 'trend' => $total_bulan_ini > $total_bulan_lalu ? 'up' : 'down'],
    'kunjungan_hari' => ['value' => $kunjungan_hari_ini, 'prev' => $kunjungan_minggu_lalu, 'trend' => $kunjungan_hari_ini > $kunjungan_minggu_lalu ? 'up' : 'down'],
    'ranap_bulan' => ['value' => $ranap_bulan_ini, 'prev' => $ranap_bulan_lalu, 'trend' => $ranap_bulan_ini > $ranap_bulan_lalu ? 'up' : 'down'],
    'ranap_hari' => ['value' => $ranap_kemarin]
];

// Filter tanggal
$tgl1 = $_GET['tgl1'] ?? $tgl_hari_ini;
$tgl2 = $_GET['tgl2'] ?? $tgl_hari_ini;
$date_range = "$tgl1 s/d $tgl2";

// Chart Poli (optimized query)
$poli_data = q("SELECT COUNT(*) as jml, IFNULL(poliklinik.nm_poli, 'Poliklinik Lain') as nm_poli, reg_periksa.kd_poli 
                FROM reg_periksa LEFT JOIN poliklinik ON poliklinik.kd_poli = reg_periksa.kd_poli 
                WHERE reg_periksa.kd_poli NOT IN ($exclude_list) AND status_lanjut='Ralan' 
                AND stts!='Batal' AND tgl_registrasi BETWEEN '$tgl1' AND '$tgl2'
                GROUP BY reg_periksa.kd_poli ORDER BY jml DESC");

$chart_labels = []; $chart_data = []; $poli_kd_map = [];
foreach ($poli_data as $row) {
    $chart_labels[] = $row['nm_poli'];
    $chart_data[] = (int)$row['jml'];
    $poli_kd_map[] = $row['kd_poli'];
}
$chart_data_json = ['poli' => ['labels' => $chart_labels, 'data' => $chart_data, 'kd_poli' => $poli_kd_map]];

// ========== TAB 1: Ranap Aktif (tgl_keluar = '0000-00-00') ==========
$bangsal_aktif = q("
  SELECT COUNT(*) as jml, bangsal.nm_bangsal, bangsal.kd_bangsal
  FROM kamar_inap
  INNER JOIN kamar ON kamar.kd_kamar = kamar_inap.kd_kamar
  INNER JOIN bangsal ON bangsal.kd_bangsal = kamar.kd_bangsal
  WHERE kamar_inap.stts_pulang='-'
    AND kamar_inap.tgl_keluar='0000-00-00'
  GROUP BY bangsal.kd_bangsal
  ORDER BY jml DESC
");

$aktif_labels=[]; $aktif_data=[]; $aktif_kd=[];
foreach($bangsal_aktif as $r){
  $aktif_labels[]=$r['nm_bangsal'];
  $aktif_data[]=(int)$r['jml'];
  $aktif_kd[]=$r['kd_bangsal'];
}

// ========== TAB 2: Sudah pulang (filter tgl_keluar) ==========
$bangsal_pulang = q("
  SELECT COUNT(*) as jml, bangsal.nm_bangsal, bangsal.kd_bangsal
  FROM kamar_inap
  INNER JOIN kamar ON kamar.kd_kamar = kamar_inap.kd_kamar
  INNER JOIN bangsal ON bangsal.kd_bangsal = kamar.kd_bangsal
  WHERE kamar_inap.tgl_keluar BETWEEN '$pk1' AND '$pk2'
    AND kamar_inap.tgl_keluar <> '0000-00-00'
  GROUP BY bangsal.kd_bangsal
  ORDER BY jml DESC
");

$pulang_labels=[]; $pulang_data=[]; $pulang_kd=[];
foreach($bangsal_pulang as $r){
  $pulang_labels[]=$r['nm_bangsal'];
  $pulang_data[]=(int)$r['jml'];
  $pulang_kd[]=$r['kd_bangsal'];
}

// Inject ke json chart
$chart_data_json['bangsalAktif'] = ['labels'=>$aktif_labels,'data'=>$aktif_data,'kd_bangsal'=>$aktif_kd];
$chart_data_json['bangsalPulang'] = ['labels'=>$pulang_labels,'data'=>$pulang_data,'kd_bangsal'=>$pulang_kd];


?>

<!-- Metrics Cards – Soft style -->
<div class="row mb-4">
  <!-- Ralan bulan ini -->
  <div class="col-xl-3 col-md-6 mb-3">
    <div class="card card-soft h-100">
      <div class="card-body py-3 px-3">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="metric-label mb-1">Ralan Bulan Ini</div>
            <div class="metric-value mb-1">
              <?= number_format($metrics['kunjungan_bulan']['value']) ?>
            </div>
            <div class="metric-sub">
              <span class="metric-chip">
                <i class="fas fa-arrow-<?= $metrics['kunjungan_bulan']['trend'] ?>"></i>
                <?= $metrics['kunjungan_bulan']['trend'] == 'up' ? '+' : '' ?>
                <?= number_format($metrics['kunjungan_bulan']['value'] - $metrics['kunjungan_bulan']['prev']) ?>
                <span class="ml-1">vs bln lalu</span>
              </span>
            </div>
          </div>
          <div class="rounded-circle d-flex align-items-center justify-content-center"
               style="width:38px;height:38px;background:rgba(59,130,246,.08);color:#2563eb;">
            <i class="fas fa-user-friends"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Ralan hari ini -->
  <div class="col-xl-3 col-md-6 mb-3">
    <div class="card card-soft h-100">
      <div class="card-body py-3 px-3">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="metric-label mb-1">Ralan Hari Ini</div>
            <div class="metric-value mb-1 text-emerald-600">
              <?= number_format($metrics['kunjungan_hari']['value']) ?>
            </div>
            <div class="metric-sub">
              <span class="metric-chip">
                <i class="fas fa-arrow-<?= $metrics['kunjungan_hari']['trend'] ?>"></i>
                <?= $metrics['kunjungan_hari']['trend'] == 'up' ? '+' : '' ?>
                <?= number_format($metrics['kunjungan_hari']['value'] - $metrics['kunjungan_hari']['prev']) ?>
                <span class="ml-1">vs mgg lalu</span>
              </span>
            </div>
          </div>
          <div class="rounded-circle d-flex align-items-center justify-content-center"
               style="width:38px;height:38px;background:rgba(16,185,129,.08);color:#059669;">
            <i class="fas fa-calendar-day"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Ranap bulan ini -->
  <div class="col-xl-3 col-md-6 mb-3">
    <div class="card card-soft h-100">
      <div class="card-body py-3 px-3">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="metric-label mb-1">Ranap Bulan Ini</div>
            <div class="metric-value mb-1">
              <?= number_format($metrics['ranap_bulan']['value']) ?>
            </div>
            <div class="metric-sub">
              <span class="metric-chip">
                <i class="fas fa-arrow-<?= $metrics['ranap_bulan']['trend'] ?>"></i>
                <?= $metrics['ranap_bulan']['trend'] == 'up' ? '+' : '' ?>
                <?= number_format($metrics['ranap_bulan']['value'] - $metrics['ranap_bulan']['prev']) ?>
                <span class="ml-1">vs bln lalu</span>
              </span>
            </div>
          </div>
          <div class="rounded-circle d-flex align-items-center justify-content-center"
               style="width:38px;height:38px;background:rgba(56,189,248,.08);color:#0284c7;">
            <i class="fas fa-procedures"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Masuk ranap hari ini -->
  <div class="col-xl-3 col-md-6 mb-3">
    <div class="card card-soft h-100" style="cursor:pointer"
         data-toggle="collapse" data-target="#ranapHariDetail" aria-expanded="false">
      <div class="card-body py-3 px-3">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="metric-label mb-1">Masuk Ranap Kemarin</div>
            <div class="metric-value mb-1 text-rose-600">
              <?= number_format($metrics['ranap_hari']['value']) ?>
            </div>
            <div class="metric-sub">
              <span class="metric-chip">
                <i class="fas fa-list-ul"></i>
                Klik untuk detail
              </span>
            </div>
          </div>
          <div class="rounded-circle d-flex align-items-center justify-content-center"
               style="width:38px;height:38px;background:rgba(248,113,113,.08);color:#dc2626;">
            <i class="fas fa-bed"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="collapse mb-3" id="ranapHariDetail">
  <div class="card card-soft">
    <div class="card-header card-soft-header-danger py-2">
      <div class="d-flex justify-content-between align-items-center">
        <div class="card-section-title">
          Ranap Kemarin Per Poli
          <span class="badge badge-light ml-2"><?= htmlspecialchars($tgl_kemarin) ?></span>
        </div>
        <button class="btn btn-xs btn-outline-secondary" onclick="closeRanapDetail()">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>
    <div class="card-body p-2">
      <div class="table-responsive">
        <table class="table table-sm table-hover">
          <thead class="thead-light">
            <tr>
              <th scope="col" style="width:70%;">Poliklinik</th>
              <th scope="col" class="text-right">Jumlah</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($ranap_kemarin_poli as $r): ?>
            <tr>
              <td>
                <strong><?= htmlspecialchars($r['nm_poli']) ?></strong>
              </td>
              <td class="text-right">
                <span class="badge badge-primary px-2"><?= number_format((int)$r['jml']) ?></span>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($ranap_kemarin_poli)): ?>
            <tr>
              <td colspan="2" class="text-center py-4 text-muted">
                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                Belum ada data masuk ranap hari ini.
              </td>
            </tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <!-- Ralan per bulan -->
  <div class="col-lg-6 mb-4">
    <div class="card card-soft">
      <div class="card-header card-soft-header-primary py-2">
        <div class="d-flex justify-content-between align-items-center">
          <div class="card-section-title">Rawat Jalan Per Bulan</div>
          <div class="d-flex align-items-center">
            <select id="tahunRalan" class="form-control form-control-sm mr-2" style="width:auto;">
              <?php
                $tahun_now = (int)date('Y');
                for ($t = $tahun_now; $t >= $tahun_now-4; $t--) {
                  $sel = $t == $tahun_now ? 'selected' : '';
                  echo "<option value=\"$t\" $sel>$t</option>";
                }
              ?>
            </select>
            <button class="btn btn-xs btn-outline-secondary" id="refreshRalan">
              <i class="fas fa-sync-alt"></i>
            </button>
          </div>
        </div>
      </div>
      <div class="card-body p-3">
        <div class="chart-container" style="min-height:260px;">
          <canvas id="ralanBulanChart"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- Ranap per bulan -->
  <div class="col-lg-6 mb-4">
    <div class="card card-soft">
      <div class="card-header card-soft-header-info py-2">
        <div class="d-flex justify-content-between align-items-center">
          <div class="card-section-title">Rawat Inap Per Bulan</div>
          <div class="d-flex align-items-center">
            <select id="tahunRanap" class="form-control form-control-sm mr-2" style="width:auto;">
              <?php
                $tahun_now = (int)date('Y');
                for ($t = $tahun_now; $t >= $tahun_now-4; $t--) {
                  $sel = $t == $tahun_now ? 'selected' : '';
                  echo "<option value=\"$t\" $sel>$t</option>";
                }
              ?>
            </select>
            <button class="btn btn-xs btn-outline-secondary" id="refreshRanap">
              <i class="fas fa-sync-alt"></i>
            </button>
          </div>
        </div>
      </div>
      <div class="card-body p-3">
        <div class="chart-container" style="min-height:260px;">
          <canvas id="ranapBulanChart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Poli Chart -->
<div class="card card-soft mb-4" id="poliCard">
  <div class="card-header card-soft-header-primary py-2">
    <div class="d-flex justify-content-between align-items-center">
      <div class="card-section-title">Kunjungan Poliklinik</div>
      <button class="btn btn-xs btn-outline-secondary bg-white" id="refreshPoli">
        <i class="fas fa-sync-alt"></i>
      </button>
    </div>
  </div>
  <div class="card-body p-2">
    <form id="filterForm" method="GET" class="form-inline">
      <input type="hidden" name="page" value="dashboard">
      <div class="input-group input-group-sm mr-2">
        <div class="input-group-prepend">
          <span class="input-group-text">Dari</span>
        </div>
        <input type="date" id="tgl1" name="tgl1" class="form-control" value="<?= $tgl1 ?>">
      </div>
      <div class="input-group input-group-sm mr-2">
        <div class="input-group-prepend">
          <span class="input-group-text">s/d</span>
        </div>
        <input type="date" id="tgl2" name="tgl2" class="form-control" value="<?= $tgl2 ?>">
      </div>
      <a href="?page=dashboard" class="btn btn-secondary btn-sm ml-1">Reset</a>
    </form>
  </div>
  <div class="card-body p-3 pt-1">
    <div class="chart-container">
      <canvas id="poliChart"></canvas>
    </div>
  </div>
</div>

<!-- Detail kunjungan poli (chart) -->
<div class="card card-soft mb-4" id="poliDetailCard" style="display:none;">
  <div class="card-header card-soft-header py-2 d-flex justify-content-between align-items-center">
    <div class="card-section-title" id="poliDetailTitle">Detail Kunjungan Poli</div>
    <button class="btn btn-xs btn-outline-secondary" id="closePoliDetail">
      Tutup
    </button>
  </div>
  <div class="card-body p-3">
    <div class="chart-container" style="min-height:260px;">
      <canvas id="poliDetailChart"></canvas>
    </div>
  </div>
</div>


<!-- Bangsal Chart -->
<div class="card card-soft mb-4">
  <div class="card-header card-soft-header-info py-2">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
      <div class="card-section-title">Kunjungan Rawat Inap</div>
      <ul class="nav nav-tabs card-header-tabs ml-3 border-0" role="tablist">
        <li class="nav-item">
          <a class="nav-link active text-dark" data-toggle="tab" href="#tabRanapAktif" role="tab">Ranap Aktif</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-dark" data-toggle="tab" href="#tabRanapPulang" role="tab">Sudah Pulang</a>
        </li>
      </ul>
    </div>
  </div>

  <div class="card-body">
    <div class="tab-content">
      <!-- TAB 1: AKTIF -->
      <div class="tab-pane fade show active" id="tabRanapAktif" role="tabpanel">
        <div class="chart-container">
          <canvas id="bangsalAktifChart"></canvas>
        </div>

        <div class="card card-soft mt-3" id="bangsalAktifDetailCard" style="display:none;">
          <div class="card-header card-soft-header py-2 d-flex justify-content-between align-items-center">
            <div class="card-section-title" id="bangsalAktifDetailTitle">Detail</div>
            <button class="btn btn-xs btn-outline-secondary" id="closeBangsalAktifDetail">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="card-body p-2">
            <div class="table-responsive">
              <table class="table table-sm table-hover mb-0">
                <thead class="thead-light">
                  <tr>
                    <th scope="col" style="width:14%;">No RM</th>
                    <th scope="col" style="width:34%;">Nama Pasien</th>
                    <th scope="col" style="width:18%;">Kamar</th>
                    <th scope="col" style="width:34%;">Masuk</th>
                  </tr>
                </thead>
                <tbody id="bangsalAktifDetailBody">
                  <tr>
                    <td colspan="4" class="text-center py-4 text-muted">
                      <i class="fas fa-bed fa-2x mb-2"></i><br>
                      Klik salah satu bangsal pada chart untuk melihat pasien ranap aktif.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 2: PULANG -->
      <div class="tab-pane fade" id="tabRanapPulang" role="tabpanel">
        <form id="pulangFilterForm" class="form-inline mb-2">
          <input type="hidden" name="page" value="dashboard">
          <div class="input-group input-group-sm mr-2">
            <div class="input-group-prepend">
              <span class="input-group-text">Dari</span>
            </div>
            <input type="date" id="pk1" name="pk1" class="form-control" value="<?= htmlspecialchars($pk1) ?>">
          </div>
          <div class="input-group input-group-sm mr-2">
            <div class="input-group-prepend">
              <span class="input-group-text">s/d</span>
            </div>
            <input type="date" id="pk2" name="pk2" class="form-control" value="<?= htmlspecialchars($pk2) ?>">
          </div>
          <a class="btn btn-sm btn-secondary" href="?page=dashboard">Reset</a>
        </form>

        <div class="chart-container">
          <canvas id="bangsalPulangChart"></canvas>
        </div>

        <div class="card card-soft mt-3" id="bangsalPulangDetailCard" style="display:none;">
          <div class="card-header card-soft-header py-2 d-flex justify-content-between align-items-center">
            <div class="card-section-title" id="bangsalPulangDetailTitle">Detail</div>
            <button class="btn btn-xs btn-outline-secondary" id="closeBangsalPulangDetail">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="card-body p-2">
            <div class="table-responsive">
              <table class="table table-sm table-hover mb-0">
                <thead class="thead-light">
                  <tr>
                    <th scope="col" style="width:12%;">No RM</th>
                    <th scope="col" style="width:28%;">Nama Pasien</th>
                    <th scope="col" style="width:16%;">Kamar</th>
                    <th scope="col" style="width:22%;">Masuk</th>
                    <th scope="col" style="width:22%;">Keluar</th>
                  </tr>
                </thead>
                <tbody id="bangsalPulangDetailBody">
                  <tr>
                    <td colspan="5" class="text-center py-4 text-muted">
                      <i class="fas fa-door-open fa-2x mb-2"></i><br>
                      Klik salah satu bangsal pada chart untuk melihat pasien yang sudah pulang.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- Data Operasi -->
<div class="card card-soft mb-4">
  <div class="card-header card-soft-header-primary py-2">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
      <div class="card-section-title">Data Tindakan Operasi</div>

      <form id="operasiFilterForm" class="form-inline">
        <div class="input-group input-group-sm mr-2">
          <div class="input-group-prepend">
            <span class="input-group-text">Dari</span>
          </div>
          <input type="date" id="operasiStartDate" class="form-control" value="<?= date('Y-m-d') ?>">
        </div>

        <div class="input-group input-group-sm mr-2">
          <div class="input-group-prepend">
            <span class="input-group-text">s/d</span>
          </div>
          <input type="date" id="operasiEndDate" class="form-control" value="<?= date('Y-m-d') ?>">
        </div>

        <button type="button" class="btn btn-sm btn-primary" id="btnFilterOperasi">Tampilkan</button>
      </form>
    </div>
  </div>

  <div class="card-body">
    <div class="chart-container">
      <canvas id="operasiChart"></canvas>
    </div>
    <div id="operasiChartEmpty" class="text-center text-muted py-4" style="display:none;">
      Tidak ada data operasi pada rentang tanggal ini.
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
/* =========================
   DASHBOARD CHARTS (AJAX)
   ========================= */

// 1) DATA AWAL dari PHP
const dash = <?= json_encode($chart_data_json) ?>;

// 2) UTIL: warna
const PALETTE = [
  '#4e79a7', '#59a14f', '#f28e2b', '#e15759', '#76b7b2',
  '#edc949', '#af7aa1', '#ff9da7', '#9c755f', '#bab0ab',
  '#1f77b4', '#17becf', '#2ca02c', '#ff7f0e', '#d62728',
];

function hexToRgba(hex, a){
  const h = hex.replace('#','');
  const r = parseInt(h.slice(0,2),16);
  const g = parseInt(h.slice(2,4),16);
  const b = parseInt(h.slice(4,6),16);
  return `rgba(${r},${g},${b},${a})`;
}
function colorsForBars(n, alpha=0.85){
  return Array.from({length:n}, (_,i)=>hexToRgba(PALETTE[i % PALETTE.length], alpha));
}
function bordersForBars(n){
  return Array.from({length:n}, (_,i)=>PALETTE[i % PALETTE.length]);
}
function makeVerticalGradient(ctx, top='rgba(13,110,253,0.95)', bottom='rgba(13,110,253,0.25)'){
  const g = ctx.createLinearGradient(0, 0, 0, 400);
  g.addColorStop(0, top);
  g.addColorStop(1, bottom);
  return g;
}

// 3) UTIL: angka di atas bar (tidak kepotong)
const numberPlugin = {
  id: 'numberPlugin',
  afterDatasetsDraw(chart) {
    const {ctx, chartArea} = chart;
    ctx.save();
    ctx.fillStyle = '#495057';
    ctx.font = 'bold 12px sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'bottom';

    chart.data.datasets.forEach((ds, i) => {
      const meta = chart.getDatasetMeta(i);
      meta.data.forEach((bar, idx) => {
        const v = ds.data[idx];
        let y = bar.y - 8;
        if (y < chartArea.top + 10) y = chartArea.top + 10;
        ctx.fillText(v, bar.x, y);
      });
    });

    ctx.restore();
  }
};

function suggestedMaxFrom(arr){
  const max = Math.max(...(arr || [0]), 0);
  return max <= 0 ? 1 : Math.ceil(max * 1.15);
}

// 4) UTIL: html escape (sekali saja)
function escapeHtml(s){
  return String(s ?? '')
    .replaceAll('&','&amp;')
    .replaceAll('<','&lt;')
    .replaceAll('>','&gt;')
    .replaceAll('"','&quot;')
    .replaceAll("'","&#039;");
}

// 5) UTIL: fetch json + debounce
async function getJSON(url){
  const r = await fetch(url, {headers: {'Accept': 'application/json'}});
  if (!r.ok) throw new Error(`HTTP ${r.status}`);
  return r.json();
}
function debounce(fn, ms=250){
  let t=null;
  return (...args) => { clearTimeout(t); t=setTimeout(()=>fn(...args), ms); };
}

/* =========================
   CHART INSTANCES
   ========================= */
let ralanBulanChart = null;
let ranapBulanChart = null;
   
let poliChart = null;
let poliDetailChart = null;
let bangsalAktifChart = null;
let bangsalPulangChart = null;
let operasiChartInstance = null;

/* =========================
   INIT: POLI CHART
   ========================= */
(function initPoli(){
  const ctx = document.getElementById('poliChart')?.getContext('2d');
  if (!ctx) return;

  poliChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: dash.poli.labels,
      datasets: [{
        label: 'Poliklinik',
        data: dash.poli.data,
        backgroundColor: colorsForBars(dash.poli.data.length, 0.65),
        borderColor: bordersForBars(dash.poli.data.length),
        borderWidth: 1,
        borderRadius: 6,
        maxBarThickness: 36
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      layout: { padding: { top: 18 } },
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, suggestedMax: suggestedMaxFrom(dash.poli.data) },
        x: { ticks: { maxRotation: 45 } }
      },
      onClick: async (evt, els) => {
          if (!els.length) return;
          const idx = els[0].index;
          const kd  = dash.poli.kd_poli[idx];
          const nm  = dash.poli.labels[idx];
          await loadPoliDetailChart(kd, nm);
        }
    },
    plugins: [numberPlugin]
  });
})();

/* =========================
   INIT: BANGSAL CHARTS
   ========================= */
(function initBangsalAktif(){
  const ctx = document.getElementById('bangsalAktifChart')?.getContext('2d');
  if (!ctx || !dash.bangsalAktif) return;

  bangsalAktifChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: dash.bangsalAktif.labels,
      datasets: [{
        label: 'Ranap aktif',
        data: dash.bangsalAktif.data,
        backgroundColor: colorsForBars(dash.bangsalAktif.data.length, 0.9),
        borderColor: bordersForBars(dash.bangsalAktif.data.length),
        borderWidth: 1,
        borderRadius: 6,
        maxBarThickness: 42
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      layout: { padding: { top: 18 } },
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, suggestedMax: suggestedMaxFrom(dash.bangsalAktif.data) },
        x: { ticks: { maxRotation: 0 } }
      },
      onClick: async (evt, els) => {
        if (!els.length) return;
        const idx = els[0].index;
        await loadBangsalAktif(dash.bangsalAktif.kd_bangsal[idx], dash.bangsalAktif.labels[idx]);
      }
    },
    plugins: [numberPlugin]
  });
})();

(function initBangsalPulang(){
  const ctx = document.getElementById('bangsalPulangChart')?.getContext('2d');
  if (!ctx || !dash.bangsalPulang) return;

  bangsalPulangChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: dash.bangsalPulang.labels,
      datasets: [{
        label: 'Pulang',
        data: dash.bangsalPulang.data,
        backgroundColor: colorsForBars(dash.bangsalPulang.data.length, 0.9),
        borderColor: bordersForBars(dash.bangsalPulang.data.length),
        borderWidth: 1,
        borderRadius: 6,
        maxBarThickness: 42
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      layout: { padding: { top: 18 } },
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, suggestedMax: suggestedMaxFrom(dash.bangsalPulang.data) },
        x: { ticks: { maxRotation: 0 } }
      },
      onClick: async (evt, els) => {
        if (!els.length) return;
        const idx = els[0].index;
        await loadBangsalPulang(dash.bangsalPulang.kd_bangsal[idx], dash.bangsalPulang.labels[idx]);
      }
    },
    plugins: [numberPlugin]
  });

  // Fix chart di tab hidden (tanpa jQuery)
    (function(){
      const tabLink = document.querySelector('a[data-toggle="tab"][href="#tabRanapPulang"]');
      if (!tabLink) return;
    
      // Bootstrap 4 akan trigger event 'shown.bs.tab' pada element tab [web:91]
      tabLink.addEventListener('shown.bs.tab', function () {
        setTimeout(() => {
          if (bangsalPulangChart) {
            bangsalPulangChart.resize();
            bangsalPulangChart.update();
          }
        }, 50);
      });
    })();

})();

/* =========================
   AJAX REFRESH (NO RELOAD)
   ========================= */
async function refreshPoliChart(tgl1, tgl2){
  const json = await getJSON(`api/poli-range.php?tgl1=${encodeURIComponent(tgl1)}&tgl2=${encodeURIComponent(tgl2)}`);

  dash.poli.labels = json.labels;
  dash.poli.data = json.data;
  dash.poli.kd_poli = json.kd_poli;

  poliChart.data.labels = json.labels;
  poliChart.data.datasets[0].data = json.data;
  poliChart.data.datasets[0].backgroundColor = colorsForBars(json.data.length, 0.65);
  poliChart.data.datasets[0].borderColor = bordersForBars(json.data.length);
  poliChart.options.scales.y.suggestedMax = suggestedMaxFrom(json.data);

  poliChart.update(); // update setelah data berubah [web:84]
}

async function refreshBangsalPulangChart(pk1, pk2){
  const json = await getJSON(`api/bangsal-pulang-chart.php?pk1=${encodeURIComponent(pk1)}&pk2=${encodeURIComponent(pk2)}`);

  dash.bangsalPulang = { labels: json.labels, data: json.data, kd_bangsal: json.kd_bangsal };

  bangsalPulangChart.data.labels = json.labels;
  bangsalPulangChart.data.datasets[0].data = json.data;
  bangsalPulangChart.data.datasets[0].backgroundColor = colorsForBars(json.data.length, 0.9);
  bangsalPulangChart.data.datasets[0].borderColor = bordersForBars(json.data.length);
  bangsalPulangChart.options.scales.y.suggestedMax = suggestedMaxFrom(json.data);

  bangsalPulangChart.update(); // [web:84]
}

/* =========================
   EVENTS: FILTER INPUTS
   ========================= */
(function bindFilters(){
  // Filter kunjungan poli
  const t1 = document.getElementById('tgl1');
  const t2 = document.getElementById('tgl2');

  if (t1 && t2 && poliChart){
    const run = debounce(async () => {
      if (!t1.value || !t2.value) return;
      if (t1.value > t2.value) t2.value = t1.value;
      await refreshPoliChart(t1.value, t2.value);
    }, 250);

    t1.addEventListener('change', run);
    t2.addEventListener('change', run);
  }

  // Filter ranap pulang
  const pk1 = document.getElementById('pk1');
  const pk2 = document.getElementById('pk2');

  if (pk1 && pk2 && bangsalPulangChart){
    const run2 = debounce(async () => {
      if (!pk1.value || !pk2.value) return;
      if (pk1.value > pk2.value) pk2.value = pk1.value;
      await refreshBangsalPulangChart(pk1.value, pk2.value);
    }, 250);

    pk1.addEventListener('change', run2);
    pk2.addEventListener('change', run2);
  }
})();

(function initBulanCharts(){
  const tahunRalan = document.getElementById('tahunRalan');
  const tahunRanap = document.getElementById('tahunRanap');

  if (tahunRalan) {
    const load = () => loadRalanPerBulan(tahunRalan.value);
    tahunRalan.addEventListener('change', load);
    document.getElementById('refreshRalan')?.addEventListener('click', load);
    load(); // initial
  }

  if (tahunRanap) {
    const load2 = () => loadRanapPerBulan(tahunRanap.value);
    tahunRanap.addEventListener('change', load2);
    document.getElementById('refreshRanap')?.addEventListener('click', load2);
    load2(); // initial
  }
})();


/* =========================
   DETAIL LOADERS
   ========================= */

async function loadBangsalAktif(kd, nm){
  try{
    const json = await getJSON(`api/bangsal-aktif.php?kd_bangsal=${encodeURIComponent(kd)}`);

    document.getElementById('bangsalAktifDetailTitle').textContent = `Ranap Aktif - ${nm} (${json.rows.length})`;
    const body = document.getElementById('bangsalAktifDetailBody');

    body.innerHTML = (json.rows || []).map(r => `
      <tr>
        <td>${escapeHtml(r.no_rkm_medis)}</td>
        <td>${escapeHtml(r.nm_pasien)}</td>
        <td>${escapeHtml(r.kd_kamar)}</td>
        <td>${escapeHtml(r.tgl_masuk)} ${escapeHtml(r.jam_masuk)}</td>
      </tr>
    `).join('') || `<tr><td colspan="4" class="text-center text-muted">Tidak ada data.</td></tr>`;

    document.getElementById('bangsalAktifDetailCard').style.display = 'block';
    document.getElementById('bangsalAktifDetailCard').scrollIntoView({behavior:'smooth', block:'start'});
  }catch(e){
    console.error(e);
    alert('Gagal load detail ranap aktif!');
  }
}

async function loadBangsalPulang(kd, nm){
  try{
    const pk1 = document.getElementById('pk1').value;
    const pk2 = document.getElementById('pk2').value;

    const json = await getJSON(`api/bangsal-pulang.php?kd_bangsal=${encodeURIComponent(kd)}&pk1=${encodeURIComponent(pk1)}&pk2=${encodeURIComponent(pk2)}`);

    document.getElementById('bangsalPulangDetailTitle').textContent = `Pulang - ${nm} (${json.rows.length})`;
    const body = document.getElementById('bangsalPulangDetailBody');

    body.innerHTML = (json.rows || []).map(r => `
      <tr>
        <td>${escapeHtml(r.no_rkm_medis)}</td>
        <td>${escapeHtml(r.nm_pasien)}</td>
        <td>${escapeHtml(r.kd_kamar)}</td>
        <td>${escapeHtml(r.tgl_masuk)} ${escapeHtml(r.jam_masuk)}</td>
        <td>${escapeHtml(r.tgl_keluar)} ${escapeHtml(r.jam_keluar)}</td>
      </tr>
    `).join('') || `<tr><td colspan="5" class="text-center text-muted">Tidak ada data.</td></tr>`;

    document.getElementById('bangsalPulangDetailCard').style.display = 'block';
    document.getElementById('bangsalPulangDetailCard').scrollIntoView({behavior:'smooth', block:'start'});
  }catch(e){
    console.error(e);
    alert('Gagal load detail ranap pulang!');
  }
}

async function loadPoliDetailChart(kdPoli, nmPoli){
  const t1 = document.getElementById('tgl1')?.value || '<?= $tgl1 ?>';
  const t2 = document.getElementById('tgl2')?.value || '<?= $tgl2 ?>';

  try{
    const data = await getJSON(
      `api/dokter-poli.php?kd_poli=${encodeURIComponent(kdPoli)}&tgl1=${encodeURIComponent(t1)}&tgl2=${encodeURIComponent(t2)}`
    );

    const title = document.getElementById('poliDetailTitle');
    title.textContent = `Kunjungan per Dokter - ${nmPoli} (${data.labels.length} dokter)`;

    const card = document.getElementById('poliDetailCard');
    card.style.display = 'block';

    const canvas = document.getElementById('poliDetailChart');
    const ctx = canvas.getContext('2d');

    if (poliDetailChart) {
      poliDetailChart.destroy();
    }

    poliDetailChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: data.labels,
        datasets: [{
          label: 'Kunjungan',
          data: data.datasets[0].data,
          backgroundColor: colorsForBars(data.datasets[0].data.length, 0.8),
          borderColor: bordersForBars(data.datasets[0].data.length),
          borderWidth: 1,
          borderRadius: 6,
          maxBarThickness: 28
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: { top: 18 } },
        plugins: { legend: { display: false } },
        scales: {
          y: {
            beginAtZero: true,
            suggestedMax: suggestedMaxFrom(data.datasets[0].data)
          },
          x: {
            ticks: {
              maxRotation: 45,
              minRotation: 0,
              font: { size: 11 }
            }
          }
        }
      },
      plugins: [numberPlugin]
    });

  }catch(e){
    console.error(e);
    alert('Gagal load detail kunjungan per dokter!');
  }
}

async function loadRalanPerBulan(tahun){
  const json = await getJSON(`api/ralan-per-bulan.php?tahun=${encodeURIComponent(tahun)}`);

  const ctx = document.getElementById('ralanBulanChart')?.getContext('2d');
  if (!ctx) return;

  if (ralanBulanChart) ralanBulanChart.destroy();

  ralanBulanChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: json.bulan,
      datasets: [{
        label: 'Total',
        data: json.jumlah_pasien,
        backgroundColor: 'rgba(255, 192, 203, 0.35)',   // soft pink
        borderColor: 'rgba(255, 192, 203, 0.8)',
        borderWidth: 1,
        borderRadius: 6,
        maxBarThickness: 40
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      layout: { padding: { top: 18 } },
      plugins: { legend: { display: true } },
      scales: {
        y: {
          beginAtZero: true,
          suggestedMax: suggestedMaxFrom(json.jumlah_pasien)
        },
        x: { ticks: { maxRotation: 0 } }
      }
    },
    plugins: [numberPlugin]
  });
}

async function loadRanapPerBulan(tahun){
  const json = await getJSON(`api/ranap-per-bulan.php?tahun=${encodeURIComponent(tahun)}`);

  const ctx = document.getElementById('ranapBulanChart')?.getContext('2d');
  if (!ctx) return;

  if (ranapBulanChart) ranapBulanChart.destroy();

  ranapBulanChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: json.bulan,
      datasets: [{
        label: 'Total',
        data: json.jumlah_pasien,
        backgroundColor: 'rgba(221, 160, 221, 0.35)',   // soft purple
        borderColor: 'rgba(221, 160, 221, 0.8)',
        borderWidth: 1,
        borderRadius: 6,
        maxBarThickness: 40
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      layout: { padding: { top: 18 } },
      plugins: { legend: { display: true } },
      scales: {
        y: {
          beginAtZero: true,
          suggestedMax: suggestedMaxFrom(json.jumlah_pasien)
        },
        x: { ticks: { maxRotation: 0 } }
      }
    },
    plugins: [numberPlugin]
  });
}

async function loadOperasiChart(){
  try {
    const startEl = document.getElementById('operasiStartDate');
    const endEl   = document.getElementById('operasiEndDate');
    const emptyEl = document.getElementById('operasiChartEmpty');

    let start = startEl.value || new Date().toISOString().slice(0,10);
    let end   = endEl.value || new Date().toISOString().slice(0,10);

    if (start > end) {
      endEl.value = start;
      end = start;
    }

    const url = `api/dashboard-operasi.php?start=${encodeURIComponent(start)}&end=${encodeURIComponent(end)}`;
    const res = await fetch(url, { headers:{'Accept':'application/json'} });
    const json = await res.json();

    const rows = Array.isArray(json.data) ? json.data : [];
    const labels = rows.map(r => r.nm_dokter);
    const values = rows.map(r => parseInt(r.jumlah, 10) || 0);

    const ctx = document.getElementById('operasiChart').getContext('2d');

    if (operasiChartInstance) {
      operasiChartInstance.destroy();
    }

    if (!rows.length) {
      emptyEl.style.display = '';
      operasiChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: ['Tidak ada data'],
          datasets: [{
            label: 'Jumlah Operasi',
            data: [0],
            backgroundColor: 'rgba(59, 130, 246, 0.25)',
            borderColor: 'rgba(59, 130, 246, 0.9)',
            borderWidth: 1,
            borderRadius: 8
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: { precision: 0 }
            }
          }
        }
      });
      return;
    }

    emptyEl.style.display = 'none';

    operasiChartInstance = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: 'Jumlah Operasi',
          data: values,
          backgroundColor: 'rgba(14, 165, 233, 0.35)',
          borderColor: 'rgba(2, 132, 199, 0.95)',
          borderWidth: 1,
          borderRadius: 8
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false }
        },
        scales: {
          x: {
            ticks: {
              autoSkip: false,
              maxRotation: 45,
              minRotation: 0
            }
          },
          y: {
            beginAtZero: true,
            ticks: {
              precision: 0
            }
          }
        }
      }
    });
  } catch (err) {
    console.error('Gagal load chart operasi:', err);
  }
}


/* =========================
   BUTTONS
   ========================= */
document.getElementById('backToPoli')?.addEventListener('click', () => {
  document.getElementById('dokterSection').style.display = 'none';
  document.getElementById('poliCard').style.display = 'block';
});

document.getElementById('refreshPoli')?.addEventListener('click', async () => {
  const t1 = document.getElementById('tgl1')?.value;
  const t2 = document.getElementById('tgl2')?.value;
  if (t1 && t2) await refreshPoliChart(t1, t2);
});

document.getElementById('closeBangsalAktifDetail')?.addEventListener('click', () => {
  document.getElementById('bangsalAktifDetailCard').style.display = 'none';
});

document.getElementById('closeBangsalPulangDetail')?.addEventListener('click', () => {
  document.getElementById('bangsalPulangDetailCard').style.display = 'none';
});

document.getElementById('closePoliDetail')?.addEventListener('click', () => {
  const card = document.getElementById('poliDetailCard');
  card.style.display = 'none';
});

// Tutup ranap hari detail
function closeRanapDetail() {
  const detail = document.getElementById('ranapHariDetail');
  detail.classList.remove('show');
}

// Fallback JS untuk card masuk ranap (kalau Bootstrap collapse bermasalah)
document.addEventListener('DOMContentLoaded', function() {
  const trigger = document.querySelector('[data-target="#ranapHariDetail"]');
  if (trigger) {
    trigger.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      const detail = document.getElementById('ranapHariDetail');
      detail.classList.toggle('show');
    });
  }
});

document.addEventListener('DOMContentLoaded', function(){
  const btn = document.getElementById('btnFilterOperasi');
  if (btn) {
    btn.addEventListener('click', loadOperasiChart);
  }

  const startEl = document.getElementById('operasiStartDate');
  const endEl = document.getElementById('operasiEndDate');

  if (startEl) startEl.addEventListener('change', loadOperasiChart);
  if (endEl) endEl.addEventListener('change', loadOperasiChart);

  loadOperasiChart();
});

</script>

