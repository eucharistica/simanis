<?php
declare(strict_types=1);
$p = $_GET['page'] ?? 'dashboard';
$u = $user ?? ['fullname'=>'','username'=>''];
?>
<aside id="sidebar">
  <div class="brand d-flex align-items-center justify-content-between">
    <span class="brand-text">SIMANIS</span>
    <button class="btn btn-sm btn-outline-light d-none d-md-inline" id="btnToggleSidebar" type="button" title="Toggle Sidebar"><i class="fas fa-angle-double-left"></i></button>
    <button class="btn btn-sm btn-outline-light d-md-none" id="btnCloseMobile" type="button" title="Tutup Menu"><i class="fas fa-times"></i></button>
  </div>
  <div class="sidebar-search-wrap px-3 py-2">
    <input type="text" id="sidebarMenuSearch" class="form-control form-control-sm" placeholder="Cari menu..." autocomplete="off">
  </div>
  <div class="flex-grow-1" style="overflow-y:auto;">
    <div class="px-2 mt-3 text-uppercase small menu-section" style="opacity:.65;">Home</div>
    <a class="sidebar-link menu-item <?= $p==='dashboard'?'active':'' ?>" href="index.php?page=dashboard"><i class="fas fa-calendar-day"></i><span class="menu-text">Dashboard</span></a>

    <div class="px-2 mt-3 text-uppercase small menu-section" style="opacity:.65;">Kunjungan Pasien</div>
    <a class="sidebar-link menu-item <?= $p==='rm_rajal'?'active':'' ?>" href="index.php?page=rm_rajal"><i class="fas fa-stethoscope"></i><span class="menu-text">Rawat Jalan</span></a>
    <a class="sidebar-link menu-item <?= $p==='rm_ranap'?'active':'' ?>" href="index.php?page=rm_ranap"><i class="fas fa-bed"></i><span class="menu-text">Rawat Inap</span></a>
    <a class="sidebar-link menu-item <?= $p==='igd'?'active':'' ?>" href="index.php?page=igd"><i class="fas fa-ambulance"></i><span class="menu-text">IGD</span></a>

    <div class="px-2 mt-3 text-uppercase small menu-section" style="opacity:.65;">Diagnosa &amp; Prosedur</div>
    <a class="sidebar-link menu-item <?= $p==='icd_rajal'?'active':'' ?>" href="index.php?page=icd_rajal"><i class="fas fa-stethoscope"></i><span class="menu-text">Rawat Jalan</span></a>
    <a class="sidebar-link menu-item <?= $p==='morbiditas_rajal'?'active':'' ?>" href="index.php?page=morbiditas_rajal"><i class="fas fa-chart-bar"></i><span class="menu-text">Morbiditas Rajal</span></a>
    <a class="sidebar-link menu-item <?= $p==='icd_ranap'?'active':'' ?>" href="index.php?page=icd_ranap"><i class="fas fa-bed"></i><span class="menu-text">Rawat Inap</span></a>

    <div class="px-2 mt-3 text-uppercase small menu-section" style="opacity:.65;">Laporan</div>
    <a class="sidebar-link menu-item <?= $p==='pendapatan_rajal'?'active':'' ?>" href="index.php?page=pendapatan_rajal"><i class="fas fa-money-bill-wave"></i><span class="menu-text">Pendapatan Rawat Jalan</span></a>
    <a class="sidebar-link menu-item <?= $p==='tindakan_harian'?'active':'' ?>" href="index.php?page=tindakan_harian"><i class="fas fa-user-md"></i><span class="menu-text">Tindakan Harian</span></a>
    <a class="sidebar-link menu-item <?= $p==='waktu_tunggu'?'active':'' ?>" href="index.php?page=waktu_tunggu"><i class="fas fa-stopwatch"></i><span class="menu-text">Waktu Tunggu Layanan</span></a>
    <a class="sidebar-link menu-item <?= $p==='laporan_ispa'?'active':'' ?>" href="index.php?page=laporan_ispa"><i class="fas fa-file-medical-alt"></i><span class="menu-text">Laporan ISPA</span></a>
    <a class="sidebar-link menu-item <?= $p==='lplpo'?'active':'' ?>" href="index.php?page=lplpo"><i class="fas fa-pills"></i><span class="menu-text">Farmasi - LPLPO</span></a>
    <a class="sidebar-link menu-item <?= $p==='rekap_obat_poli'?'active':'' ?>" href="index.php?page=rekap_obat_poli"><i class="fas fa-clinic-medical"></i><span class="menu-text">Farmasi - Rekap Obat</span></a>
    <a class="sidebar-link menu-item <?= $p==='rekap_bhp'?'active':'' ?>" href="index.php?page=rekap_bhp"><i class="fas fa-boxes"></i><span class="menu-text">Farmasi - Rekap BHP</span></a>
    <a class="sidebar-link menu-item <?= $p==='rekap_resep'?'active':'' ?>" href="index.php?page=rekap_resep"><i class="fas fa-prescription-bottle-alt"></i><span class="menu-text">Farmasi - Rekap Resep</span></a>
    <a class="sidebar-link menu-item <?= $p==='monitoring_expertise_radiologi'?'active':'' ?>" href="index.php?page=monitoring_expertise_radiologi"><i class="fas fa-capsules"></i><span class="menu-text">Monitoring Expertise Radiologi</span></a>
    <a class="sidebar-link menu-item <?= $p==='vaksin_internasional' || $p==='vaksin_internasional_view'?'active':'' ?>" href="index.php?page=vaksin_internasional"><i class="fas fa-syringe"></i><span class="menu-text">Vaksin Internasional</span></a>
    </a>

    <div class="px-2 mt-3 text-uppercase small menu-section" style="opacity:.65;">SIRS</div>
    <a class="sidebar-link menu-item <?= $p==='bukti_laporan_kemkes'?'active':'' ?>" href="index.php?page=bukti_laporan_kemkes"><i class="fas fa-file-upload"></i><span class="menu-text">Bukti Laporan Kemkes</span></a>
    <a class="sidebar-link menu-item <?= $p==='rl318_farmasi'?'active':'' ?>" href="index.php?page=rl318_farmasi"><i class="fas fa-file-prescription"></i><span class="menu-text">RL 3.18 Farmasi Resep</span></a>
    <a class="sidebar-link menu-item <?= $p==='rl3192_antibiotik_rajal'?'active':'' ?>" href="index.php?page=rl3192_antibiotik_rajal"><i class="fas fa-capsules"></i><span class="menu-text">RL 3.19.2 Penggunaan Antibiotik Rajal</span></a>
    <a class="sidebar-link menu-item <?= $p==='rl3192_antibiotik_ranap'?'active':'' ?>" href="index.php?page=rl3192_antibiotik_ranap"><i class="fas fa-capsules"></i><span class="menu-text">RL 3.19.2 Penggunaan Antibiotik Ranap</span></a>
    <a class="sidebar-link menu-item <?= $p==='antibiotik_ranap'?'active':'' ?>" href="index.php?page=antibiotik_ranap"><i class="fas fa-capsules"></i><span class="menu-text">RL 3.19.3 Kuantitas Penggunaan Antibiotik</span></a>
    <a class="sidebar-link menu-item <?= $p==='rl51_sirs'?'active':'' ?>" href="index.php?page=rl51_sirs"><i class="fas fa-file-medical"></i><span class="menu-text">RL 5.1 SIRS</span></a>
  </div>
  <div class="sidebar-userbox mt-auto p-3 small">
    <div style="opacity:.65;">Login:</div>
    <div class="font-weight-bold"><?= htmlspecialchars($user['fullname'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
    <div><?= htmlspecialchars($user['username'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
    <a href="logout.php" class="sidebar-footer-link mt-3"><i class="fas fa-sign-out-alt"></i><span class="menu-text">Logout</span></a>
  </div>
</aside>
<main id="content" class="p-2 p-md-3">
  <div class="d-flex align-items-center justify-content-between mb-3 d-md-none">
    <button class="btn btn-outline-secondary" id="btnMobileMenu"><i class="fas fa-bars"></i></button>
    <div class="text-muted small"><?= htmlspecialchars(($u['fullname'] ?? ''), ENT_QUOTES) ?></div>
  </div>
